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
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Menampilkan halaman checkout pemesanan pelanggan.
     */
    public function index(Request $request): View|RedirectResponse
    {
        // 1. Ambil keranjang pengguna dan pastikan tidak kosong
        $user = Auth::user();
        $cart = Cart::query()
            ->with(['items.product.images', 'items.variant'])
            ->where('user_id', $user->id)
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('products.index')
                ->with('error', 'Keranjang belanja kamu masih kosong. Pilih produk terlebih dahulu.');
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
        $initialShipping = $storeSetting->calculateShippingFee('delivery', $shippingZones->first()?->id);

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
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'scheduled_date' => ['nullable', 'date'],
        ]);

        // 2. Hitung jarak apabila metode ongkir toko adalah jarak dan koordinat tersedia
        $storeSetting = StoreSetting::current();
        $distanceKm = isset($validated['distance_km']) ? (float) $validated['distance_km'] : null;
        if (
            $distanceKm === null
            && $validated['delivery_type'] === 'delivery'
            && isset($validated['latitude'], $validated['longitude'])
        ) {
            $distanceKm = $storeSetting->calculateDistanceKm(
                (float) $validated['latitude'],
                (float) $validated['longitude']
            );
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
     * Memvalidasi checkout, membuat pesanan, dan menyiapkan sesi pembayaran Midtrans.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $storeSetting = StoreSetting::current();

        // 1. Pastikan keranjang belanja memiliki item yang aktif
        $cart = Cart::query()
            ->with(['items.product', 'items.variant'])
            ->where('user_id', $user->id)
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
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
            'delivery_latitude' => ['nullable', 'numeric'],
            'delivery_longitude' => ['nullable', 'numeric'],
            'delivery_distance_km' => ['nullable', 'numeric', 'min:0'],
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
            'scheduled_date.required' => 'Pilih tanggal pengiriman atau pengambilan.',
            'payment_method.required' => 'Pilih metode pembayaran Midtrans.',
        ]);

        // 3. Periksa aturan jadwal H-1, hari libur, jam buka, dan kuota harian
        $scheduleCheck = $storeSetting->checkScheduleDate($validated['scheduled_date']);
        if (! $scheduleCheck['available']) {
            return back()->withInput()->withErrors(['scheduled_date' => $scheduleCheck['reason']]);
        }

        // 4. Hitung subtotal produk dan ongkos kirim secara akurat di server
        $subtotal = $cart->subtotalAmount();
        $distanceKm = isset($validated['delivery_distance_km']) && $validated['delivery_distance_km'] !== ''
            ? (float) $validated['delivery_distance_km']
            : null;

        if (
            $distanceKm === null
            && $validated['delivery_type'] === 'delivery'
            && ! empty($validated['delivery_latitude'])
            && ! empty($validated['delivery_longitude'])
        ) {
            $distanceKm = $storeSetting->calculateDistanceKm(
                (float) $validated['delivery_latitude'],
                (float) $validated['delivery_longitude']
            );
        }

        $shipping = $storeSetting->calculateShippingFee(
            $validated['delivery_type'],
            isset($validated['shipping_zone_id']) ? (int) $validated['shipping_zone_id'] : null,
            $distanceKm
        );

        if (! $shipping['within_range']) {
            return back()->withInput()->withErrors([
                'delivery_address' => $shipping['label'].'. Silakan pilih metode Pickup atau alamat lain.',
            ]);
        }

        $totalAmount = $subtotal + $shipping['fee'];
        $mapsUrl = null;
        if ($validated['delivery_type'] === 'pickup' && ! empty($validated['pickup_location_id'])) {
            $pickupLocation = PickupLocation::find($validated['pickup_location_id']);
            $mapsUrl = $pickupLocation?->maps_url;
        } elseif (! empty($validated['delivery_latitude']) && ! empty($validated['delivery_longitude'])) {
            $mapsUrl = 'https://maps.google.com/?q='.$validated['delivery_latitude'].','.$validated['delivery_longitude'];
        } elseif (! empty($validated['delivery_address'])) {
            $mapsUrl = 'https://maps.google.com/?q='.urlencode($validated['delivery_address']);
        }

        // 5. Simpan pesanan, rincian item, log awal, transaksi Midtrans, dan kosongkan keranjang dalam transaksi DB
        $order = DB::transaction(function () use ($user, $cart, $validated, $storeSetting, $subtotal, $shipping, $totalAmount, $mapsUrl): Order {
            $orderNumber = Order::generateOrderNumber();
            $deadline = now()->addMinutes((int) $storeSetting->payment_timeout_minutes);

            $createdOrder = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'source' => 'website',
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?: $user->email,
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
                'shipping_zone_id' => $validated['delivery_type'] === 'delivery' && $shipping['method'] === 'zone' ? ($validated['shipping_zone_id'] ?? null) : null,
                'shipping_method_snapshot' => $shipping['method'],
                'maps_url' => $mapsUrl,
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time_slot' => $validated['scheduled_time_slot'],
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping['fee'],
                'total_amount' => $totalAmount,
                'customer_note' => trim((string) ($validated['customer_note'] ?? $cart->order_note ?? '')) ?: null,
                'order_status' => 'menunggu_pembayaran',
                'payment_status' => 'belum_dibayar',
                'payment_deadline_at' => $deadline,
            ]);

            foreach ($cart->items as $cartItem) {
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

            // Buat data transaksi Midtrans (Snap API jika server_key diisi, atau simulasi VA/QRIS lokal)
            $midtransOrderId = $orderNumber.'-'.now()->format('His');
            $channelLabels = [
                'midtrans_snap' => 'Midtrans Snap',
                'qris' => 'Midtrans · QRIS',
                'credit_card' => 'Midtrans · Kartu Kredit/Debit',
                'mandiri_va' => 'Midtrans · Mandiri Virtual Account',
                'bca_va' => 'Midtrans · BCA Virtual Account',
                'gopay' => 'Midtrans · GoPay',
            ];

            $paymentData = [
                'order_id' => $createdOrder->id,
                'gateway' => 'midtrans',
                'payment_method' => $validated['payment_method'],
                'channel_label' => $channelLabels[$validated['payment_method']] ?? 'Midtrans Snap',
                'midtrans_order_id' => $midtransOrderId,
                'amount' => $totalAmount,
                'status' => 'pending',
                'expires_at' => $deadline,
            ];

            if ($validated['payment_method'] === 'mandiri_va') {
                $numericPart = str_pad((string) $createdOrder->id, 7, '0', STR_PAD_LEFT);
                $paymentData['va_bank'] = 'mandiri';
                $paymentData['biller_code'] = '70012';
                $paymentData['bill_key'] = $numericPart.'2026';
                $paymentData['va_number'] = '70012'.$numericPart.'2026';
            } elseif ($validated['payment_method'] === 'bca_va') {
                $numericPart = str_pad((string) $createdOrder->id, 8, '0', STR_PAD_LEFT);
                $paymentData['va_bank'] = 'bca';
                $paymentData['va_number'] = '80777081'.$numericPart;
            } elseif ($validated['payment_method'] === 'qris') {
                $paymentData['qris_string'] = '00020101021226620015ID.CO.MIDTRANS.WWW0118936009120000000000520454995303360540'.strlen((string) $totalAmount).$totalAmount.'5802ID5915BROWNIES KELANA6008SIDOARJO6304BC10';
            }

            $serverKey = config('services.midtrans.server_key');
            if (! empty($serverKey)) {
                $snapBaseUrl = config('services.midtrans.is_production')
                    ? 'https://app.midtrans.com/snap/v1/transactions'
                    : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

                $snapPayload = [
                    'transaction_details' => [
                        'order_id' => $midtransOrderId,
                        'gross_amount' => $totalAmount,
                    ],
                    'customer_details' => [
                        'first_name' => $validated['customer_name'],
                        'email' => $validated['customer_email'] ?: $user->email,
                        'phone' => $validated['customer_phone'],
                    ],
                    'expiry' => [
                        'unit' => 'minutes',
                        'duration' => (int) $storeSetting->payment_timeout_minutes,
                    ],
                ];

                $response = Http::withBasicAuth($serverKey, '')
                    ->acceptJson()
                    ->post($snapBaseUrl, $snapPayload);

                if ($response->successful()) {
                    $json = $response->json();
                    $paymentData['snap_token'] = $json['token'] ?? null;
                    $paymentData['snap_redirect_url'] = $json['redirect_url'] ?? null;
                    $paymentData['raw_response'] = $json;
                }
            }

            Payment::create($paymentData);

            // Kirim notifikasi ke dashboard pelanggan & inbox admin
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

            // Kosongkan keranjang belanja setelah order tersimpan
            $cart->items()->delete();
            $cart->update(['order_note' => null]);

            return $createdOrder;
        });

        return redirect()->route('payments.show', $order)
            ->with('success', 'Pesanan '.$order->order_number.' berhasil dibuat! Silakan selesaikan pembayaran.');
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
