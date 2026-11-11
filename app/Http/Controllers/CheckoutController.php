<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PickupLocation;
use App\Models\ShippingZone;
use App\Models\StoreSetting;
use App\Models\UserNotification;
use App\Notifications\OrderLifecycleNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CheckoutController extends Controller
{
    /**
     * Menampilkan halaman checkout pemesanan pelanggan.
     */
    public function index(Request $request): View|RedirectResponse
    {
        // 1. Ambil keranjang pengguna dan pastikan tidak kosong serta item masih tersedia
        $user = Auth::user();
        $cart = Cart::query()
            ->with(['items.product.images', 'items.variant'])
            ->where('user_id', $user->id)
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('products.index')
                ->with('error', 'Keranjang belanja kamu masih kosong. Pilih produk terlebih dahulu.');
        }

        foreach ($cart->items as $cartItem) {
            if (
                ! $cartItem->product
                || ! $cartItem->product->is_active
                || ! $cartItem->product->is_available
                || ($cartItem->product_variant_id && (! $cartItem->variant || ! $cartItem->variant->is_available))
            ) {
                $productTitle = $cartItem->product?->name ?? 'Salah satu produk';

                return redirect()->route('cart.index')
                    ->with('error', $productTitle.' di keranjangmu sedang habis atau dinonaktifkan. Perbarui keranjang sebelum checkout.');
            }
        }

        // 2. Muat pengaturan toko, lokasi pickup aktif, zona ongkir, dan kalender H-1
        $storeSetting = StoreSetting::current();
        $pickupLocations = PickupLocation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $shippingZones = ShippingZone::query()
            ->where('is_active', true)
            ->orderBy('fee')
            ->get();

        // 3. Susun data kalender untuk bulan berjalan dan bulan depan beserta kuota H-1
        $calendarDays = $this->buildCalendarDays($storeSetting);
        $defaultAvailableDay = collect($calendarDays)->firstWhere('available', true);
        $defaultLat = (float) ($user->saved_latitude ?? -7.3318500);
        $defaultLng = (float) ($user->saved_longitude ?? 112.7244100);
        $defaultDistance = $storeSetting->shipping_method === 'distance'
            ? $storeSetting->calculateDistanceKm($defaultLat, $defaultLng)
            : null;

        $initialShipping = $storeSetting->calculateShippingFee('delivery', $shippingZones->first()?->id, $defaultDistance);

        return view('checkout.index', compact(
            'user',
            'cart',
            'storeSetting',
            'pickupLocations',
            'shippingZones',
            'calendarDays',
            'defaultAvailableDay',
            'initialShipping'
        ));
    }

    /**
     * Menghitung estimasi ongkos kirim dan ketersediaan kuota tanggal via AJAX.
     */
    public function checkShipping(Request $request): JsonResponse
    {
        // 1. Validasi parameter cek ongkir dan tanggal dari form checkout
        $validated = $request->validate([
            'delivery_type' => ['required', 'in:delivery,pickup'],
            'shipping_zone_id' => ['nullable', 'integer'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'scheduled_date' => ['nullable', 'date'],
        ]);

        // 2. Hitung jarak otoritatif dari koordinat lat/lng apabila tersedia (fallback distance_km hanya jika koordinat tidak dikirim)
        $storeSetting = StoreSetting::current();
        $distanceKm = null;
        if (
            $validated['delivery_type'] === 'delivery'
            && isset($validated['latitude'], $validated['longitude'])
            && $validated['latitude'] !== ''
            && $validated['longitude'] !== ''
        ) {
            $distanceKm = $storeSetting->calculateDistanceKm(
                (float) $validated['latitude'],
                (float) $validated['longitude']
            );
        } elseif (isset($validated['distance_km']) && $validated['distance_km'] !== '') {
            $distanceKm = (float) $validated['distance_km'];
        }

        // 3. Hitung ongkos kirim dan ketersediaan jadwal tanggal
        $shipping = $storeSetting->calculateShippingFee(
            $validated['delivery_type'],
            $validated['shipping_zone_id'] ?? null,
            $distanceKm
        );

        $scheduleInfo = null;
        if (! empty($validated['scheduled_date'])) {
            $scheduleInfo = $storeSetting->checkScheduleDate($validated['scheduled_date']);
        }

        return response()->json([
            'shipping' => $shipping,
            'schedule' => $scheduleInfo,
        ]);
    }

    /**
     * Memvalidasi checkout, menghitung tarif otoritatif di server, membuat pesanan, dan meminta token Midtrans Snap asli.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $storeSetting = StoreSetting::current();

        // 1. Pastikan keranjang belanja memiliki item
        $cartExists = Cart::query()
            ->where('user_id', $user->id)
            ->whereHas('items')
            ->exists();

        if (! $cartExists) {
            return redirect()->route('products.index')
                ->with('error', 'Keranjang belanja kamu kosong.');
        }

        // 2. Validasi seluruh input pemesan, penerima/pengambil, jadwal, dan metode Midtrans
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:25'],
            'customer_secondary_phone' => ['nullable', 'string', 'max:25'],
            'delivery_type' => ['required', 'in:delivery,pickup'],
            'pickup_location_id' => ['required_if:delivery_type,pickup', 'nullable', 'exists:pickup_locations,id'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:25'],
            'recipient_secondary_phone' => ['nullable', 'string', 'max:25'],
            'delivery_address' => ['required_if:delivery_type,delivery', 'nullable', 'string', 'max:1000'],
            'delivery_address_detail' => ['nullable', 'string', 'max:500'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'shipping_zone_id' => ['nullable', 'exists:shipping_zones,id'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time_slot' => ['required', 'string', 'max:50'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:midtrans_snap,qris,credit_card,mandiri_va,bca_va,gopay'],
        ], [
            'customer_name.required' => 'Nama pemesan wajib diisi.',
            'customer_phone.required' => 'Nomor telepon pemesan wajib diisi.',
            'pickup_location_id.required_if' => 'Pilih lokasi pickup yang tersedia.',
            'recipient_name.required' => 'Nama penerima atau pengambil wajib diisi.',
            'recipient_phone.required' => 'Nomor telepon penerima/pengambil wajib diisi.',
            'delivery_address.required_if' => 'Alamat pengiriman wajib diisi.',
            'delivery_latitude.between' => 'Koordinat lintang pengiriman tidak valid.',
            'delivery_longitude.between' => 'Koordinat bujur pengiriman tidak valid.',
            'scheduled_date.required' => 'Pilih tanggal pengiriman atau pengambilan.',
            'payment_method.required' => 'Pilih metode pembayaran Midtrans.',
        ]);

        // 3. Periksa kredensial Midtrans Snap sebelum memproses transaksi
        $serverKey = (string) config('services.midtrans.server_key');
        if (trim($serverKey) === '') {
            return back()->withInput()->with('error', 'Konfigurasi gateway pembayaran Midtrans belum aktif. Hubungi admin toko.');
        }

        // 4. Periksa lokasi pickup aktif, zona ongkir aktif, serta slot waktu operasional
        $pickupLocation = null;
        if ($validated['delivery_type'] === 'pickup') {
            $pickupLocation = PickupLocation::query()
                ->where('is_active', true)
                ->find($validated['pickup_location_id'] ?? 0);

            if (! $pickupLocation) {
                return back()->withInput()->withErrors([
                    'pickup_location_id' => 'Lokasi pengambilan yang dipilih sudah tidak aktif.',
                ]);
            }
        }

        $selectedZoneId = null;
        if ($validated['delivery_type'] === 'delivery' && $storeSetting->shipping_method === 'zone') {
            if (! empty($validated['shipping_zone_id'])) {
                $zone = ShippingZone::query()
                    ->where('is_active', true)
                    ->find((int) $validated['shipping_zone_id']);

                if (! $zone) {
                    return back()->withInput()->withErrors([
                        'shipping_zone_id' => 'Zona pengiriman yang dipilih sudah tidak aktif.',
                    ]);
                }
                $selectedZoneId = $zone->id;
            } else {
                $defaultZone = ShippingZone::query()->where('is_active', true)->orderBy('fee')->first();
                if (! $defaultZone) {
                    return back()->withInput()->withErrors([
                        'shipping_zone_id' => 'Belum ada zona pengiriman aktif yang tersedia.',
                    ]);
                }
                $selectedZoneId = $defaultZone->id;
            }
        }

        $allowedSlots = $storeSetting->delivery_slots ?? ['09.00-11.00 WIB', '13.00-15.00 WIB'];
        if (! in_array($validated['scheduled_time_slot'], $allowedSlots, true)) {
            return back()->withInput()->withErrors([
                'scheduled_time_slot' => 'Slot waktu pengiriman/pengambilan yang dipilih tidak tersedia.',
            ]);
        }

        // 5. Hitung jarak otoritatif di backend (abaikan delivery_distance_km dari frontend agar tidak dapat dimanipulasi)
        $distanceKm = null;
        if ($validated['delivery_type'] === 'delivery' && $storeSetting->shipping_method === 'distance') {
            if (
                ! isset($validated['delivery_latitude'], $validated['delivery_longitude'])
                || $validated['delivery_latitude'] === ''
                || $validated['delivery_longitude'] === ''
            ) {
                return back()->withInput()->withErrors([
                    'delivery_address' => 'Tentukan titik koordinat alamat di peta terlebih dahulu untuk menghitung ongkir jarak.',
                ]);
            }

            $distanceKm = $storeSetting->calculateDistanceKm(
                (float) $validated['delivery_latitude'],
                (float) $validated['delivery_longitude']
            );
        }

        $shipping = $storeSetting->calculateShippingFee(
            $validated['delivery_type'],
            $selectedZoneId,
            $distanceKm
        );

        if (! $shipping['within_range']) {
            return back()->withInput()->withErrors([
                'delivery_address' => $shipping['label'].'. Silakan pilih metode Pickup atau alamat lain.',
            ]);
        }

        $mapsUrl = null;
        if ($validated['delivery_type'] === 'pickup' && $pickupLocation) {
            $mapsUrl = $pickupLocation->maps_url;
        } elseif (! empty($validated['delivery_latitude']) && ! empty($validated['delivery_longitude'])) {
            $mapsUrl = 'https://maps.google.com/?q='.$validated['delivery_latitude'].','.$validated['delivery_longitude'];
        } elseif (! empty($validated['delivery_address'])) {
            $mapsUrl = 'https://maps.google.com/?q='.urlencode($validated['delivery_address']);
        }

        // 6. Simpan pesanan, rincian item, log awal, panggil API Midtrans Snap asli, dan kosongkan keranjang secara atomik
        try {
            $order = DB::transaction(function () use (
                $user,
                $validated,
                $storeSetting,
                $shipping,
                $selectedZoneId,
                $mapsUrl,
                $serverKey
            ): Order {
                $lockedCart = Cart::query()
                    ->with(['items.product', 'items.variant'])
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedCart || $lockedCart->items->isEmpty()) {
                    throw new RuntimeException('CART_EMPTY');
                }

                // Periksa ulang ketersediaan setiap item & varian secara langsung dari database
                $subtotal = 0;
                $itemDetails = [];
                foreach ($lockedCart->items as $cartItem) {
                    $product = $cartItem->product;
                    $variant = $cartItem->variant;

                    if (
                        ! $product
                        || ! $product->is_active
                        || ! $product->is_available
                        || ($cartItem->product_variant_id && (! $variant || $variant->product_id !== $product->id || ! $variant->is_available))
                    ) {
                        $itemLabel = $product?->name ?? 'Produk';
                        throw new RuntimeException('UNAVAILABLE_ITEM:'.$itemLabel);
                    }

                    $unitPrice = $cartItem->unitPrice();
                    $lineTotal = $unitPrice * (int) $cartItem->quantity;
                    $subtotal += $lineTotal;

                    $itemDetails[] = [
                        'id' => $variant ? 'PRD-'.$product->id.'-V'.$variant->id : 'PRD-'.$product->id,
                        'price' => $unitPrice,
                        'quantity' => (int) $cartItem->quantity,
                        'name' => mb_substr($product->name.($variant ? ' - '.$variant->name : ''), 0, 50),
                    ];
                }

                // Periksa kembali aturan H-1 dan kuota harian di dalam transaksi agar bebas kondisi balapan
                $scheduleCheck = $storeSetting->checkScheduleDate($validated['scheduled_date']);
                if (! $scheduleCheck['available']) {
                    throw new RuntimeException('SCHEDULE_INVALID:'.$scheduleCheck['reason']);
                }

                if ($shipping['fee'] > 0) {
                    $itemDetails[] = [
                        'id' => 'SHIPPING',
                        'price' => (int) $shipping['fee'],
                        'quantity' => 1,
                        'name' => mb_substr($shipping['label'], 0, 50),
                    ];
                }

                $totalAmount = $subtotal + (int) $shipping['fee'];
                $orderNumber = Order::generateOrderNumber();
                $deadline = now()->addMinutes((int) $storeSetting->payment_timeout_minutes);
                $customerEmail = trim((string) ($validated['customer_email'] ?: $user->email));

                $createdOrder = Order::create([
                    'order_number' => $orderNumber,
                    'user_id' => $user->id,
                    'source' => 'website',
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $customerEmail,
                    'customer_phone' => $validated['customer_phone'],
                    'customer_secondary_phone' => $validated['customer_secondary_phone'] ?? null,
                    'delivery_type' => $validated['delivery_type'],
                    'pickup_location_id' => $validated['delivery_type'] === 'pickup' ? $validated['pickup_location_id'] : null,
                    'recipient_name' => $validated['recipient_name'],
                    'recipient_phone' => $validated['recipient_phone'],
                    'recipient_secondary_phone' => $validated['recipient_secondary_phone'] ?? null,
                    'delivery_address' => $validated['delivery_type'] === 'delivery' ? $validated['delivery_address'] : null,
                    'delivery_address_detail' => $validated['delivery_type'] === 'delivery' ? ($validated['delivery_address_detail'] ?? null) : null,
                    'delivery_latitude' => $validated['delivery_latitude'] ?? null,
                    'delivery_longitude' => $validated['delivery_longitude'] ?? null,
                    'delivery_distance_km' => $shipping['distance_km'],
                    'shipping_zone_id' => $validated['delivery_type'] === 'delivery' && $shipping['method'] === 'zone' ? $selectedZoneId : null,
                    'shipping_method_snapshot' => $shipping['method'],
                    'maps_url' => $mapsUrl,
                    'scheduled_date' => $validated['scheduled_date'],
                    'scheduled_time_slot' => $validated['scheduled_time_slot'],
                    'subtotal' => $subtotal,
                    'shipping_fee' => (int) $shipping['fee'],
                    'total_amount' => $totalAmount,
                    'customer_note' => trim((string) ($validated['customer_note'] ?? $lockedCart->order_note ?? '')) ?: null,
                    'order_status' => 'menunggu_pembayaran',
                    'payment_status' => 'belum_dibayar',
                    'payment_deadline_at' => $deadline,
                ]);

                foreach ($lockedCart->items as $cartItem) {
                    $unitPrice = $cartItem->unitPrice();
                    OrderItem::create([
                        'order_id' => $createdOrder->id,
                        'product_id' => $cartItem->product_id,
                        'product_variant_id' => $cartItem->product_variant_id,
                        'product_name' => $cartItem->product->name,
                        'variant_name' => $cartItem->variant?->name,
                        'unit_price' => $unitPrice,
                        'quantity' => $cartItem->quantity,
                        'subtotal' => $unitPrice * $cartItem->quantity,
                        'custom_note' => $cartItem->custom_note,
                    ]);
                }

                $createdOrder->logs()->create([
                    'user_id' => $user->id,
                    'actor_name' => $user->name,
                    'event_type' => 'created',
                    'title' => 'Pesanan Dibuat',
                    'description' => 'Pesanan dibuat melalui checkout website. Batas pembayaran '.(int) $storeSetting->payment_timeout_minutes.' menit.',
                ]);

                // 7. Minta Snap Token resmi langsung dari Midtrans Snap API (tanpa simulasi/generator lokal)
                $midtransOrderId = $orderNumber.'-'.now()->format('His');
                $channelLabels = [
                    'midtrans_snap' => 'Midtrans Snap',
                    'qris' => 'Midtrans · QRIS',
                    'credit_card' => 'Midtrans · Kartu Kredit/Debit',
                    'mandiri_va' => 'Midtrans · Mandiri Virtual Account',
                    'bca_va' => 'Midtrans · BCA Virtual Account',
                    'gopay' => 'Midtrans · GoPay',
                ];

                $enabledPaymentsMap = [
                    'qris' => ['other_qris', 'gopay'],
                    'credit_card' => ['credit_card'],
                    'mandiri_va' => ['echannel'],
                    'bca_va' => ['bca_va'],
                    'gopay' => ['gopay'],
                ];

                $snapBaseUrl = config('services.midtrans.is_production')
                    ? 'https://app.midtrans.com/snap/v1/transactions'
                    : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

                $snapPayload = [
                    'transaction_details' => [
                        'order_id' => $midtransOrderId,
                        'gross_amount' => $totalAmount,
                    ],
                    'item_details' => $itemDetails,
                    'customer_details' => [
                        'first_name' => $validated['customer_name'],
                        'email' => $customerEmail,
                        'phone' => $validated['customer_phone'],
                    ],
                    'credit_card' => [
                        'secure' => (bool) config('services.midtrans.is_3ds', true),
                    ],
                    'expiry' => [
                        'unit' => 'minutes',
                        'duration' => (int) $storeSetting->payment_timeout_minutes,
                    ],
                    'callbacks' => [
                        'finish' => route('payments.result', ['order' => $createdOrder, 'state' => 'pending']),
                    ],
                ];

                if (isset($enabledPaymentsMap[$validated['payment_method']])) {
                    $snapPayload['enabled_payments'] = $enabledPaymentsMap[$validated['payment_method']];
                }

                $response = Http::timeout(15)
                    ->withBasicAuth($serverKey, '')
                    ->acceptJson()
                    ->post($snapBaseUrl, $snapPayload);

                $json = $response->json();
                if (! $response->successful() || empty($json['token'])) {
                    Log::error('Midtrans Snap gagal membuat transaksi.', [
                        'order_number' => $orderNumber,
                        'midtrans_order_id' => $midtransOrderId,
                        'http_status' => $response->status(),
                        'error_messages' => $json['error_messages'] ?? null,
                    ]);
                    throw new RuntimeException('MIDTRANS_FAILED');
                }

                Payment::create([
                    'order_id' => $createdOrder->id,
                    'gateway' => 'midtrans',
                    'payment_method' => $validated['payment_method'],
                    'channel_label' => $channelLabels[$validated['payment_method']] ?? 'Midtrans Snap',
                    'midtrans_order_id' => $midtransOrderId,
                    'snap_token' => $json['token'],
                    'snap_redirect_url' => $json['redirect_url'] ?? null,
                    'amount' => $totalAmount,
                    'status' => 'pending',
                    'expires_at' => $deadline,
                    'raw_response' => $json,
                ]);

                // 8. Kirim notifikasi ke dashboard pelanggan & inbox admin
                UserNotification::create([
                    'user_id' => $user->id,
                    'order_id' => $createdOrder->id,
                    'audience' => 'customer',
                    'category' => 'pesanan',
                    'channel' => 'dashboard',
                    'title' => 'Menunggu Pembayaran',
                    'message' => 'Pesanan '.$orderNumber.' berhasil dibuat. Selesaikan pembayaran Midtrans dalam '.(int) $storeSetting->payment_timeout_minutes.' menit.',
                    'delivery_status' => 'terkirim',
                    'action_url' => route('payments.show', $createdOrder),
                ]);

                UserNotification::create([
                    'user_id' => null,
                    'order_id' => $createdOrder->id,
                    'audience' => 'admin',
                    'category' => 'pesanan',
                    'channel' => 'dashboard',
                    'title' => 'Pesanan Baru Masuk',
                    'message' => $orderNumber.' · '.$createdOrder->customer_name.' · Rp '.number_format($totalAmount, 0, ',', '.').' via Checkout Website.',
                    'delivery_status' => 'terkirim',
                    'action_url' => route('admin.orders.show', $createdOrder),
                ]);

                // 9. Kosongkan keranjang belanja setelah order dan token Snap tersimpan
                $lockedCart->items()->delete();
                $lockedCart->update(['order_note' => null]);

                return $createdOrder;
            });
        } catch (RuntimeException $exception) {
            $msg = $exception->getMessage();

            if ($msg === 'CART_EMPTY') {
                return redirect()->route('products.index')
                    ->with('error', 'Keranjang belanja kamu kosong.');
            }

            if (str_starts_with($msg, 'UNAVAILABLE_ITEM:')) {
                $itemName = substr($msg, strlen('UNAVAILABLE_ITEM:'));

                return redirect()->route('cart.index')
                    ->with('error', $itemName.' atau variannya sedang habis/nonaktif. Perbarui keranjang sebelum checkout.');
            }

            if (str_starts_with($msg, 'SCHEDULE_INVALID:')) {
                return back()->withInput()->withErrors([
                    'scheduled_date' => substr($msg, strlen('SCHEDULE_INVALID:')),
                ]);
            }

            if ($msg === 'MIDTRANS_FAILED') {
                return back()->withInput()->with('error', 'Gagal memulai sesi pembayaran Midtrans. Periksa koneksi atau coba beberapa saat lagi.');
            }

            throw $exception;
        }

        // 10. Kirim email konfirmasi pesanan dibuat tanpa mengganggu redirect checkout
        if (! empty($order->customer_email)) {
            try {
                Notification::route('mail', $order->customer_email)->notify(
                    new OrderLifecycleNotification(
                        $order,
                        'Pesanan '.$order->order_number.' Menunggu Pembayaran — Brownies Kelana',
                        'Pesanan Anda berhasil dibuat dan menunggu pembayaran melalui Midtrans.',
                        'Silakan selesaikan pembayaran dalam '.(int) $storeSetting->payment_timeout_minutes.' menit sebelum pesanan kedaluwarsa otomatis.',
                        'Lanjutkan Pembayaran Midtrans',
                        route('payments.show', $order)
                    )
                );
            } catch (Throwable $exception) {
                Log::warning('Gagal mengirim email konfirmasi checkout '.$order->order_number.': '.$exception->getMessage());
            }
        }

        return redirect()->route('payments.show', $order)
            ->with('success', 'Pesanan '.$order->order_number.' berhasil dibuat! Silakan selesaikan pembayaran melalui Midtrans.');
    }

    /**
     * Menyusun daftar tanggal 35 hari ke depan beserta status ketersediaan dan sisa kuota.
     *
     * @return list<array<string, mixed>>
     */
    protected function buildCalendarDays(StoreSetting $storeSetting): array
    {
        $days = [];
        $today = Carbon::today();
        for ($offset = 0; $offset < 35; $offset++) {
            $date = $today->copy()->addDays($offset);
            $check = $storeSetting->checkScheduleDate($date->toDateString());
            $days[] = [
                'date' => $date->toDateString(),
                'day_number' => $date->day,
                'day_short' => $date->locale('id')->isoFormat('ddd'),
                'month_label' => $date->locale('id')->isoFormat('MMMM YYYY'),
                'is_today' => $offset === 0,
                'available' => $check['available'],
                'status' => $check['status'],
                'used_quota' => $check['used_quota'],
                'remaining_quota' => $check['remaining_quota'],
                'reason' => $check['reason'],
            ];
        }

        return $days;
    }
}
