<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Menampilkan daftar seluruh pesanan beserta pencarian dan filter operasional.
     */
    public function index(Request $request): View
    {
        // 1. Periksa batas pembayaran pesanan tertunda terlebih dahulu
        $pendingOrders = Order::query()
            ->where('order_status', 'menunggu_pembayaran')
            ->get();

        foreach ($pendingOrders as $pendingOrder) {
            $pendingOrder->expireIfPastDeadline();
        }

        // 2. Ambil parameter filter dari query string
        $searchKeyword = trim((string) $request->query('q', ''));
        $sourceFilter = trim((string) $request->query('source', ''));
        $deliveryFilter = trim((string) $request->query('delivery_type', ''));
        $paymentFilter = trim((string) $request->query('payment_status', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $dateFilter = trim((string) $request->query('date', ''));

        // 3. Bangun query daftar pesanan terfilter
        $ordersQuery = Order::query()
            ->with(['items', 'pickupLocation', 'latestPayment'])
            ->latest();

        if ($searchKeyword !== '') {
            $ordersQuery->where(function ($query) use ($searchKeyword): void {
                $query->where('order_number', 'like', '%'.$searchKeyword.'%')
                    ->orWhere('customer_name', 'like', '%'.$searchKeyword.'%')
                    ->orWhere('customer_phone', 'like', '%'.$searchKeyword.'%');
            });
        }

        if ($sourceFilter !== '') {
            $ordersQuery->where('source', $sourceFilter);
        }

        if ($deliveryFilter !== '') {
            $ordersQuery->where('delivery_type', $deliveryFilter);
        }

        if ($paymentFilter !== '') {
            $ordersQuery->where('payment_status', $paymentFilter);
        }

        if ($statusFilter !== '') {
            $ordersQuery->where('order_status', $statusFilter);
        }

        if ($dateFilter !== '') {
            $ordersQuery->whereDate('scheduled_date', $dateFilter);
        }

        $orders = $ordersQuery->paginate(15)->withQueryString();
        $unpaidCount = Order::query()->where('payment_status', 'belum_dibayar')->count();
        $processingCount = Order::query()->whereIn('order_status', ['diproses', 'dikemas'])->count();

        return view('admin.orders.index', compact(
            'orders',
            'searchKeyword',
            'sourceFilter',
            'deliveryFilter',
            'paymentFilter',
            'statusFilter',
            'dateFilter',
            'unpaidCount',
            'processingCount'
        ));
    }

    /**
     * Menampilkan formulir pembuatan pesanan manual dari chat WhatsApp pelanggan.
     */
    public function create(): View
    {
        // 1. Muat produk aktif beserta varian, lokasi pickup, zona ongkir, dan pelanggan terdaftar
        $storeSetting = StoreSetting::current();
        $products = Product::query()
            ->with('variants')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $pickupLocations = PickupLocation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $shippingZones = ShippingZone::query()
            ->where('is_active', true)
            ->orderBy('fee')
            ->get();
        $customers = User::query()
            ->where('role', 'customer')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.orders.create', compact(
            'storeSetting',
            'products',
            'pickupLocations',
            'shippingZones',
            'customers'
        ));
    }

    /**
     * Menyimpan pesanan manual WhatsApp yang diinputkan Admin ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $storeSetting = StoreSetting::current();

        // 1. Validasi data pemesan, metode penerimaan, jadwal, item produk, dan status bayar awal
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:25'],
            'delivery_type' => ['required', 'in:delivery,pickup'],
            'pickup_location_id' => ['required_if:delivery_type,pickup', 'nullable', 'exists:pickup_locations,id'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:25'],
            'delivery_address' => ['required_if:delivery_type,delivery', 'nullable', 'string', 'max:1000'],
            'delivery_address_detail' => ['nullable', 'string', 'max:500'],
            'shipping_zone_id' => ['nullable', 'exists:shipping_zones,id'],
            'manual_shipping_fee' => ['nullable', 'integer', 'min:0'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time_slot' => ['required', 'string', 'max:50'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'payment_action' => ['required', 'in:belum_dibayar,lunas'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.custom_note' => ['nullable', 'string', 'max:100'],
        ], [
            'customer_name.required' => 'Nama pemesan wajib diisi.',
            'customer_phone.required' => 'Nomor WhatsApp pemesan wajib diisi.',
            'pickup_location_id.required_if' => 'Pilih lokasi pickup untuk pengambilan di toko.',
            'delivery_address.required_if' => 'Alamat pengiriman wajib diisi.',
            'items.required' => 'Tambahkan minimal satu produk ke pesanan.',
        ]);

        // 2. Cocokkan akun pelanggan jika belum dipilih eksplisit tetapi nomor WhatsApp cocok
        $matchedUserId = $validated['user_id'] ?? null;
        if (! $matchedUserId) {
            $normalizedPhone = User::normalizePhoneNumber($validated['customer_phone']);
            $matchedCustomer = User::query()
                ->where('role', 'customer')
                ->get()
                ->first(fn (User $customer): bool => User::normalizePhoneNumber($customer->phone) === $normalizedPhone);

            $matchedUserId = $matchedCustomer?->id;
        }

        // 3. Hitung subtotal dari daftar item produk dan tarif ongkos kirim
        $preparedItems = [];
        $subtotal = 0;

        foreach ($validated['items'] as $rawItem) {
            $product = Product::query()->with('variants')->findOrFail($rawItem['product_id']);
            $variant = null;
            if (! empty($rawItem['product_variant_id'])) {
                $variant = $product->variants->firstWhere('id', (int) $rawItem['product_variant_id']);
            }
            if (! $variant) {
                $variant = $product->variants->first();
            }

            $unitPrice = $variant ? (int) $variant->price : (int) $product->base_price;
            $qty = (int) $rawItem['quantity'];
            $lineSubtotal = $unitPrice * $qty;
            $subtotal += $lineSubtotal;

            $preparedItems[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name' => $product->name,
                'variant_name' => $variant?->name,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'subtotal' => $lineSubtotal,
                'custom_note' => trim((string) ($rawItem['custom_note'] ?? '')) ?: null,
            ];
        }

        if ($validated['delivery_type'] === 'pickup') {
            $shippingFee = 0;
            $shippingMethod = 'pickup';
        } elseif ($request->filled('manual_shipping_fee')) {
            $shippingFee = (int) $validated['manual_shipping_fee'];
            $shippingMethod = 'manual';
        } else {
            $calc = $storeSetting->calculateShippingFee(
                'delivery',
                isset($validated['shipping_zone_id']) ? (int) $validated['shipping_zone_id'] : null
            );
            $shippingFee = $calc['fee'];
            $shippingMethod = $calc['method'];
        }

        $totalAmount = $subtotal + $shippingFee;
        $isPaid = $validated['payment_action'] === 'lunas';

        // 4. Simpan pesanan manual, item, pembayaran awal, dan log audit ke database
        $createdOrder = DB::transaction(function () use ($validated, $matchedUserId, $preparedItems, $subtotal, $shippingFee, $shippingMethod, $totalAmount, $isPaid, $storeSetting): Order {
            $orderNumber = Order::generateOrderNumber();
            $deadline = $isPaid ? null : now()->addMinutes((int) $storeSetting->payment_timeout_minutes);

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $matchedUserId,
                'source' => 'whatsapp',
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'],
                'delivery_type' => $validated['delivery_type'],
                'pickup_location_id' => $validated['delivery_type'] === 'pickup' ? $validated['pickup_location_id'] : null,
                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'],
                'delivery_address' => $validated['delivery_type'] === 'delivery' ? $validated['delivery_address'] : null,
                'delivery_address_detail' => $validated['delivery_type'] === 'delivery' ? ($validated['delivery_address_detail'] ?? null) : null,
                'shipping_zone_id' => $validated['delivery_type'] === 'delivery' ? ($validated['shipping_zone_id'] ?? null) : null,
                'shipping_method_snapshot' => $shippingMethod,
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time_slot' => $validated['scheduled_time_slot'],
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'customer_note' => trim((string) ($validated['customer_note'] ?? '')) ?: null,
                'order_status' => $isPaid ? 'diproses' : 'menunggu_pembayaran',
                'payment_status' => $isPaid ? 'lunas' : 'belum_dibayar',
                'payment_deadline_at' => $deadline,
                'paid_at' => $isPaid ? now() : null,
            ]);

            foreach ($preparedItems as $itemData) {
                OrderItem::create(array_merge(['order_id' => $order->id], $itemData));
            }

            Payment::create([
                'order_id' => $order->id,
                'gateway' => 'manual_whatsapp',
                'payment_method' => 'whatsapp_manual',
                'channel_label' => 'WhatsApp Manual · Admin',
                'amount' => $totalAmount,
                'status' => $isPaid ? 'settlement' : 'pending',
                'paid_at' => $isPaid ? now() : null,
                'expires_at' => $deadline,
            ]);

            $order->logs()->create([
                'user_id' => Auth::id(),
                'actor_name' => Auth::user()->name,
                'event_type' => 'created',
                'title' => 'Pesanan Manual WhatsApp Dibuat',
                'description' => 'Dicatat oleh Admin ('.$order->paymentStatusLabel().').',
            ]);

            if ($matchedUserId) {
                UserNotification::create([
                    'user_id' => $matchedUserId,
                    'order_id' => $order->id,
                    'audience' => 'customer',
                    'category' => 'pesanan',
                    'channel' => 'dashboard',
                    'title' => 'Pesanan Baru Tercatat',
                    'message' => 'Pesanan manual WhatsApp '.$order->order_number.' sebesar Rp '.number_format($totalAmount, 0, ',', '.').' telah ditambahkan ke akunmu.',
                    'delivery_status' => 'terkirim',
                    'action_url' => route('orders.show', $order->order_number),
                ]);
            }

            return $order;
        });

        return redirect()->route('admin.orders.show', $createdOrder)
            ->with('success', 'Pesanan manual WhatsApp '.$createdOrder->order_number.' berhasil dibuat.');
    }

    /**
     * Menampilkan halaman detail lengkap pesanan untuk kebutuhan operasional Admin.
     */
    public function show(Order $order): View
    {
        // 1. Periksa batas kedaluwarsa dan muat seluruh relasi pesanan
        $order->expireIfPastDeadline();
        $order->refresh();
        $order->load(['user', 'pickupLocation', 'shippingZone', 'items.product', 'logs.actor', 'payments', 'latestPayment', 'review']);

        // 2. Hitung riwayat pesanan pelanggan untuk menandai pelanggan baru vs berulang
        $customerOrderCount = $order->user_id
            ? Order::query()->where('user_id', $order->user_id)->count()
            : Order::query()->where('customer_phone', $order->customer_phone)->count();

        // 3. Siapkan daftar status valid sesuai tipe delivery atau pickup
        $availableStatuses = $order->delivery_type === 'pickup'
            ? [
                'menunggu_pembayaran' => 'Menunggu Pembayaran',
                'diproses' => 'Diproses',
                'dikemas' => 'Dikemas',
                'siap_diambil' => 'Siap Diambil',
                'sudah_diambil' => 'Sudah Diambil',
            ]
            : [
                'menunggu_pembayaran' => 'Menunggu Pembayaran',
                'diproses' => 'Diproses',
                'dikemas' => 'Dikemas',
                'dalam_pengiriman' => 'Dalam Pengiriman',
                'sampai_tujuan' => 'Sampai Tujuan',
            ];

        // 4. Siapkan tautan WhatsApp langsung menuju nomor pelanggan
        $digits = preg_replace('/[^0-9]/', '', $order->customer_phone) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }
        $waText = 'Halo Kak '.$order->customer_name.', kami dari Brownies Kelana ingin mengonfirmasi pesanan '.$order->order_number.' ('.$order->orderStatusLabel().').';
        $customerWhatsappUrl = $digits !== '' ? 'https://wa.me/'.$digits.'?text='.urlencode($waText) : StoreSetting::current()->whatsappUrl($waText);

        $storeSetting = StoreSetting::current();

        return view('admin.orders.show', compact(
            'order',
            'customerOrderCount',
            'availableStatuses',
            'customerWhatsappUrl',
            'storeSetting'
        ));
    }

    /**
     * Menampilkan formulir ubah data penerima, alamat, metode pickup, dan jadwal pesanan.
     */
    public function edit(Order $order): View
    {
        // 1. Muat pilihan lokasi pickup dan slot jam pengiriman untuk editing
        $order->load(['items', 'pickupLocation']);
        $pickupLocations = PickupLocation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $storeSetting = StoreSetting::current();

        return view('admin.orders.edit', compact('order', 'pickupLocations', 'storeSetting'));
    }

    /**
     * Menyimpan perubahan data penerima/jadwal pesanan oleh Admin dan mencatat riwayat audit.
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        // 1. Validasi data perubahan penerima, jadwal, alamat, dan biaya kirim
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:25'],
            'delivery_type' => ['required', 'in:delivery,pickup'],
            'pickup_location_id' => ['required_if:delivery_type,pickup', 'nullable', 'exists:pickup_locations,id'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:25'],
            'recipient_secondary_phone' => ['nullable', 'string', 'max:25'],
            'delivery_address' => ['required_if:delivery_type,delivery', 'nullable', 'string', 'max:1000'],
            'delivery_address_detail' => ['nullable', 'string', 'max:500'],
            'maps_url' => ['nullable', 'string', 'max:500'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time_slot' => ['required', 'string', 'max:50'],
            'shipping_fee' => ['required', 'integer', 'min:0'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'edit_reason' => ['nullable', 'string', 'max:255'],
        ]);

        // 2. Bandingkan perubahan agar tercetak jelas di riwayat log pesanan
        $changes = [];
        if ($order->recipient_name !== $validated['recipient_name']) {
            $changes[] = 'Penerima: '.$order->recipient_name.' -> '.$validated['recipient_name'];
        }
        if ($order->scheduled_date?->toDateString() !== $validated['scheduled_date'] || $order->scheduled_time_slot !== $validated['scheduled_time_slot']) {
            $changes[] = 'Jadwal: '.$validated['scheduled_date'].' ('.$validated['scheduled_time_slot'].')';
        }
        if ($order->delivery_type !== $validated['delivery_type']) {
            $changes[] = 'Metode: '.$validated['delivery_type'];
        }
        if ($validated['delivery_type'] === 'delivery' && $order->delivery_address !== ($validated['delivery_address'] ?? null)) {
            $changes[] = 'Alamat tujuan diperbarui';
        }
        if ((int) $order->shipping_fee !== (int) $validated['shipping_fee']) {
            $changes[] = 'Ongkir: Rp '.number_format((int) $validated['shipping_fee'], 0, ',', '.');
        }

        // 3. Perbarui data pesanan dan hitung ulang total tagihan
        $newTotal = (int) $order->subtotal + (int) $validated['shipping_fee'];
        $order->update([
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'delivery_type' => $validated['delivery_type'],
            'pickup_location_id' => $validated['delivery_type'] === 'pickup' ? $validated['pickup_location_id'] : null,
            'recipient_name' => $validated['recipient_name'],
            'recipient_phone' => $validated['recipient_phone'],
            'recipient_secondary_phone' => $validated['recipient_secondary_phone'] ?? null,
            'delivery_address' => $validated['delivery_type'] === 'delivery' ? $validated['delivery_address'] : null,
            'delivery_address_detail' => $validated['delivery_type'] === 'delivery' ? ($validated['delivery_address_detail'] ?? null) : null,
            'maps_url' => $validated['maps_url'] ?? $order->maps_url,
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time_slot' => $validated['scheduled_time_slot'],
            'shipping_fee' => (int) $validated['shipping_fee'],
            'total_amount' => $newTotal,
            'customer_note' => trim((string) ($validated['customer_note'] ?? '')) ?: null,
        ]);

        // 4. Catat aktivitas pembaruan data ke log pesanan dan beritahu pelanggan
        $descriptionParts = ! empty($changes) ? implode(' · ', $changes) : 'Detail informasi pesanan diperbarui Admin.';
        if (! empty($validated['edit_reason'])) {
            $descriptionParts .= ' Catatan: '.$validated['edit_reason'];
        }

        $order->logs()->create([
            'user_id' => Auth::id(),
            'actor_name' => Auth::user()->name,
            'event_type' => 'edited',
            'title' => 'Data Pesanan Diperbarui Admin',
            'description' => $descriptionParts,
        ]);

        if ($order->user_id) {
            UserNotification::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'audience' => 'customer',
                'category' => 'pesanan',
                'channel' => 'dashboard',
                'title' => 'Data Pesanan Diperbarui',
                'message' => 'Admin memperbarui rincian pesanan '.$order->order_number.' sesuai konfirmasi Anda.',
                'delivery_status' => 'terkirim',
                'action_url' => route('orders.show', $order->order_number),
            ]);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Data pesanan '.$order->order_number.' berhasil diperbarui.');
    }

    /**
     * Menghapus pesanan yang sudah dibatalkan atau kedaluwarsa dari arsip aktif.
     */
    public function destroy(Order $order): RedirectResponse
    {
        // 1. Cegah penghapusan pesanan aktif atau sudah lunas demi integritas laporan
        if (! in_array($order->order_status, ['dibatalkan', 'kedaluwarsa'], true)) {
            return back()->with('error', 'Hanya pesanan berstatus Dibatalkan atau Kedaluwarsa yang dapat dihapus.');
        }

        $orderNumber = $order->order_number;
        $order->delete();

        return redirect()->route('admin.orders.index')
            ->with('success', 'Pesanan '.$orderNumber.' berhasil dihapus.');
    }

    /**
     * Memperbarui status progres pesanan (Diproses, Dikemas, Dikirim/Siap Diambil, Selesai).
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        // 1. Validasi status target yang dikirim admin
        $validated = $request->validate([
            'order_status' => ['required', 'in:menunggu_pembayaran,diproses,dikemas,dalam_pengiriman,siap_diambil,sampai_tujuan,sudah_diambil'],
            'status_note' => ['nullable', 'string', 'max:500'],
            'received_by' => ['nullable', 'string', 'max:150'],
        ]);

        $newStatus = $validated['order_status'];

        // 2. Pastikan alur status sesuai dengan metode penerimaan (delivery vs pickup)
        if ($order->delivery_type === 'pickup' && in_array($newStatus, ['dalam_pengiriman', 'sampai_tujuan'], true)) {
            return back()->with('error', 'Pesanan pickup tidak dapat menggunakan status pengiriman kurir.');
        }

        if ($order->delivery_type === 'delivery' && in_array($newStatus, ['siap_diambil', 'sudah_diambil'], true)) {
            return back()->with('error', 'Pesanan delivery tidak dapat menggunakan status pengambilan pickup.');
        }

        if ($newStatus !== 'menunggu_pembayaran' && $order->payment_status !== 'lunas') {
            return back()->with('error', 'Pesanan belum lunas. Tunggu konfirmasi Midtrans atau gunakan tombol Tandai Lunas untuk pesanan WhatsApp.');
        }

        // 3. Simpan pembaruan status beserta waktu selesai apabila mencapai tahap akhir
        $updatePayload = [
            'order_status' => $newStatus,
        ];

        if (in_array($newStatus, ['sampai_tujuan', 'sudah_diambil'], true)) {
            $updatePayload['delivered_at'] = $order->delivered_at ?? now();
            $updatePayload['received_by'] = trim((string) ($validated['received_by'] ?? '')) ?: ($order->received_by ?: $order->recipient_name);
        }

        $order->update($updatePayload);
        $statusLabel = $order->orderStatusLabel();

        // 4. Catat riwayat perubahan status ke OrderLog dan kirim notifikasi ke pelanggan
        $note = trim((string) ($validated['status_note'] ?? ''));
        $logDescription = $note !== '' ? $note : 'Status pesanan diperbarui menjadi '.$statusLabel.'.';

        $order->logs()->create([
            'user_id' => Auth::id(),
            'actor_name' => Auth::user()->name,
            'event_type' => 'status_changed',
            'title' => $statusLabel,
            'description' => $logDescription,
        ]);

        $this->notifyCustomerFromTemplate($order, $newStatus, $statusLabel, $logDescription);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Status pesanan '.$order->order_number.' diperbarui menjadi '.$statusLabel.'.');
    }

    /**
     * Memperbarui data kurir pengiriman dan mengunggah foto bukti pengiriman.
     */
    public function updateCourier(Request $request, Order $order): RedirectResponse
    {
        // 1. Validasi data pengemudi, penerima, dan file foto bukti pengiriman
        $validated = $request->validate([
            'courier_name' => ['required', 'string', 'max:150'],
            'courier_phone' => ['nullable', 'string', 'max:25'],
            'courier_vehicle_plate' => ['nullable', 'string', 'max:40'],
            'received_by' => ['nullable', 'string', 'max:150'],
            'delivery_proof' => ['nullable', 'image', 'max:2048'],
            'mark_in_delivery' => ['nullable', 'boolean'],
            'mark_delivered' => ['nullable', 'boolean'],
        ], [
            'courier_name.required' => 'Nama kurir/driver wajib diisi.',
            'delivery_proof.image' => 'Foto bukti pengiriman harus berupa gambar.',
            'delivery_proof.max' => 'Ukuran foto bukti maksimal 2MB.',
        ]);

        // 2. Unggah foto bukti pengiriman ke disk publik apabila dilampirkan
        $proofPath = $order->delivery_proof_path;
        if ($request->hasFile('delivery_proof')) {
            if ($proofPath && str_starts_with($proofPath, 'storage/')) {
                Storage::disk('public')->delete(substr($proofPath, 8));
            }
            $storedPath = $request->file('delivery_proof')->store('proofs', 'public');
            $proofPath = 'storage/'.$storedPath;
        }

        // 3. Tentukan pembaruan status pengiriman jika admin memilih transisi langsung
        $newStatus = $order->order_status;
        if ($request->boolean('mark_delivered') && $order->delivery_type === 'delivery') {
            $newStatus = 'sampai_tujuan';
        } elseif ($request->boolean('mark_in_delivery') && $order->delivery_type === 'delivery') {
            $newStatus = 'dalam_pengiriman';
        }

        $order->update([
            'courier_name' => $validated['courier_name'],
            'courier_phone' => $validated['courier_phone'] ?? null,
            'courier_vehicle_plate' => $validated['courier_vehicle_plate'] ?? null,
            'received_by' => $validated['received_by'] ?? $order->received_by,
            'delivery_proof_path' => $proofPath,
            'order_status' => $newStatus,
            'delivered_at' => $newStatus === 'sampai_tujuan' ? ($order->delivered_at ?? now()) : $order->delivered_at,
        ]);

        // 4. Simpan log operasional kurir dan beri notifikasi pelanggan
        $courierSummary = 'Kurir '.$validated['courier_name']
            .($validated['courier_vehicle_plate'] ? ' ('.$validated['courier_vehicle_plate'].')' : '')
            .($validated['courier_phone'] ? ' · HP '.$validated['courier_phone'] : '');

        $order->logs()->create([
            'user_id' => Auth::id(),
            'actor_name' => Auth::user()->name,
            'event_type' => 'courier_updated',
            'title' => $newStatus === 'sampai_tujuan' ? 'Sampai Tujuan · Bukti Diunggah' : 'Informasi Kurir Diperbarui',
            'description' => $courierSummary.($validated['received_by'] ? ' · Diterima oleh: '.$validated['received_by'] : ''),
        ]);

        if (in_array($newStatus, ['dalam_pengiriman', 'sampai_tujuan'], true)) {
            $this->notifyCustomerFromTemplate($order, $newStatus, $order->orderStatusLabel(), $courierSummary);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Data kurir dan bukti pengiriman berhasil disimpan.');
    }

    /**
     * Membatalkan pesanan oleh Admin dengan alasan yang wajib dicatat.
     */
    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        // 1. Validasi alasan pembatalan wajib dan opsi penandaan refund
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:4', 'max:255'],
            'mark_refund' => ['nullable', 'boolean'],
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        // 2. Perbarui status pesanan dan status pembayaran terkait
        $newPaymentStatus = $order->payment_status;
        if ($order->payment_status === 'belum_dibayar') {
            $newPaymentStatus = 'dibatalkan';
        } elseif ($order->payment_status === 'lunas' && $request->boolean('mark_refund')) {
            $newPaymentStatus = 'refund';
        }

        $order->update([
            'order_status' => 'dibatalkan',
            'payment_status' => $newPaymentStatus,
            'cancelled_at' => now(),
            'cancelled_by' => 'admin',
            'cancellation_reason' => trim($validated['cancellation_reason']),
        ]);

        if ($order->latestPayment && $newPaymentStatus === 'dibatalkan') {
            $order->latestPayment->update(['status' => 'cancel']);
        }

        // 3. Catat log dan kirim pemberitahuan ke dashboard pelanggan
        $order->logs()->create([
            'user_id' => Auth::id(),
            'actor_name' => Auth::user()->name,
            'event_type' => 'cancelled',
            'title' => 'Pesanan Dibatalkan oleh Admin',
            'description' => 'Alasan: '.trim($validated['cancellation_reason']),
        ]);

        $this->notifyCustomerFromTemplate(
            $order,
            'dibatalkan',
            'Pesanan Dibatalkan',
            'Pesanan '.$order->order_number.' dibatalkan oleh admin. Alasan: '.trim($validated['cancellation_reason'])
        );

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Pesanan '.$order->order_number.' berhasil dibatalkan.');
    }

    /**
     * Menandai pesanan sebagai Lunas secara manual (untuk pesanan WhatsApp atau rekonsiliasi admin).
     */
    public function markManualPaid(Order $order): RedirectResponse
    {
        // 1. Cegah aksi berulang apabila pesanan sudah berstatus lunas
        if ($order->payment_status === 'lunas') {
            return back()->with('error', 'Pesanan ini sudah tercatat Lunas.');
        }

        // 2. Tandai pesanan lunas dan majukan status menunggu pembayaran menjadi diproses
        $order->update([
            'payment_status' => 'lunas',
            'order_status' => $order->order_status === 'menunggu_pembayaran' ? 'diproses' : $order->order_status,
            'paid_at' => now(),
        ]);

        if ($order->latestPayment) {
            $order->latestPayment->update([
                'status' => 'settlement',
                'paid_at' => now(),
            ]);
        } else {
            Payment::create([
                'order_id' => $order->id,
                'gateway' => $order->source === 'whatsapp' ? 'manual_whatsapp' : 'midtrans',
                'payment_method' => $order->source === 'whatsapp' ? 'whatsapp_manual' : 'midtrans_snap',
                'channel_label' => 'Dikonfirmasi Admin · '.$order->order_number,
                'amount' => $order->total_amount,
                'status' => 'settlement',
                'paid_at' => now(),
            ]);
        }

        // 3. Catat log pelunasan admin dan kabari pelanggan melalui notifikasi dashboard
        $order->logs()->create([
            'user_id' => Auth::id(),
            'actor_name' => Auth::user()->name,
            'event_type' => 'payment_settled',
            'title' => 'Pembayaran Ditandai Lunas oleh Admin',
            'description' => 'Pelunasan dikonfirmasi Admin. Pesanan mulai masuk antrean produksi.',
        ]);

        $this->notifyCustomerFromTemplate(
            $order,
            'lunas',
            'Pembayaran Dikonfirmasi',
            'Pembayaran pesanan '.$order->order_number.' telah dikonfirmasi lunas dan sedang diproses.'
        );

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Pesanan '.$order->order_number.' ditandai Lunas dan siap diproses.');
    }

    /**
     * Menampilkan tampilan Nota Pesanan yang siap dicetak untuk dapur & paket pengiriman.
     */
    public function printReceipt(Order $order): View
    {
        $order->load(['items', 'pickupLocation', 'latestPayment']);
        $storeSetting = StoreSetting::current();

        return view('admin.orders.receipt', compact('order', 'storeSetting'));
    }

    /**
     * Menampilkan halaman Rekap Produksi dikelompokkan berdasarkan tanggal jadwal, produk, dan slot jam.
     */
    public function productionRecap(Request $request): View
    {
        // 1. Tentukan rentang tanggal rekap produksi (default: hari ini hingga 7 hari ke depan)
        $startDate = $request->query('start_date', Carbon::today()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->addDays(7)->toDateString());

        $storeSetting = StoreSetting::current();

        // 2. Muat pesanan aktif dalam rentang jadwal (kecuali dibatalkan atau kedaluwarsa)
        $orders = Order::query()
            ->with(['items.product', 'pickupLocation'])
            ->whereBetween('scheduled_date', [$startDate, $endDate])
            ->whereNotIn('order_status', ['dibatalkan', 'kedaluwarsa'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time_slot')
            ->get();

        // 3. Kelompokkan pesanan per tanggal jadwal beserta agregasi item produksi
        $dailyGroups = $orders->groupBy(fn (Order $order): string => $order->scheduled_date->toDateString())
            ->map(function ($dayOrders, string $dateString) use ($storeSetting): array {
                $itemSummary = [];
                $customNotes = [];
                $deliveryCount = 0;
                $pickupCount = 0;
                $totalPcs = 0;

                foreach ($dayOrders as $dayOrder) {
                    if ($dayOrder->delivery_type === 'pickup') {
                        $pickupCount++;
                    } else {
                        $deliveryCount++;
                    }

                    foreach ($dayOrder->items as $item) {
                        $key = $item->product_name.' — '.($item->variant_name ?? 'Standar');
                        $itemSummary[$key] = ($itemSummary[$key] ?? 0) + $item->quantity;
                        $totalPcs += $item->quantity;

                        if ($item->custom_note) {
                            $customNotes[] = [
                                'order_number' => $dayOrder->order_number,
                                'product_name' => $item->product_name,
                                'note' => $item->custom_note,
                            ];
                        }
                    }
                }

                arsort($itemSummary);

                return [
                    'date' => $dateString,
                    'date_formatted' => Carbon::parse($dateString)->locale('id')->isoFormat('dddd, D MMMM YYYY'),
                    'orders' => $dayOrders,
                    'orders_count' => $dayOrders->count(),
                    'daily_quota' => (int) $storeSetting->daily_quota,
                    'delivery_count' => $deliveryCount,
                    'pickup_count' => $pickupCount,
                    'total_pcs' => $totalPcs,
                    'item_summary' => $itemSummary,
                    'custom_notes' => $customNotes,
                ];
            });

        return view('admin.orders.recap', compact(
            'storeSetting',
            'startDate',
            'endDate',
            'dailyGroups'
        ));
    }

    /**
     * Mengirim notifikasi dashboard pelanggan menggunakan template notifikasi aktif.
     */
    protected function notifyCustomerFromTemplate(Order $order, string $triggerKey, string $fallbackTitle, string $fallbackMessage): void
    {
        if (! $order->user_id) {
            return;
        }

        $template = NotificationTemplate::query()
            ->where('trigger_key', $triggerKey)
            ->where('is_active', true)
            ->first();

        $title = $template?->title ?? $fallbackTitle;
        $message = $template
            ? $template->renderMessage([
                'nama_pelanggan' => $order->customer_name,
                'nomor_pesanan' => $order->order_number,
                'jadwal' => ($order->scheduled_date?->locale('id')->isoFormat('D MMM YYYY') ?? '').' · '.$order->scheduled_time_slot,
                'alasan_batal' => $order->cancellation_reason ?? '-',
            ])
            : $fallbackMessage;

        UserNotification::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'audience' => 'customer',
            'category' => 'pesanan',
            'channel' => 'dashboard',
            'title' => $title,
            'message' => $message,
            'delivery_status' => 'terkirim',
            'action_url' => route('orders.show', $order->order_number),
        ]);
    }
}
