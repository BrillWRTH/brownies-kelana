@extends('layouts.admin')

@section('title', 'Detail Pesanan '.$order->order_number.' — Panel Admin')
@section('kicker', 'DETAIL & OPERASIONAL PESANAN')

@section('content')
    @php
        $payBadge = match($order->payment_status) {
            'lunas' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'belum_dibayar' => 'border-amber-200 bg-amber-50 text-amber-800',
            default => 'border-red-200 bg-red-50 text-red-800',
        };
        $orderBadge = match($order->order_status) {
            'sampai_tujuan', 'sudah_diambil' => 'bg-emerald-50 text-emerald-800',
            'menunggu_pembayaran' => 'bg-amber-50 text-amber-800',
            'diproses', 'dikemas' => 'bg-blue-50 text-blue-800',
            'dalam_pengiriman', 'siap_diambil' => 'bg-violet-50 text-violet-800',
            default => 'bg-red-50 text-red-800',
        };
    @endphp

    <div class="space-y-6">
        {{-- Header Order & Aksi Utama --}}
        <div class="flex flex-col gap-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="font-serif text-xl font-bold lining-nums text-[#28211c]">{{ $order->order_number }}</h1>
                    <span class="rounded-full px-3 py-0.5 text-xs font-bold {{ $orderBadge }}">
                        {{ $order->orderStatusLabel() }}
                    </span>
                    <span class="rounded-full border px-3 py-0.5 text-xs font-bold {{ $payBadge }}">
                        {{ $order->paymentStatusLabel() }}
                    </span>
                    <span class="rounded bg-[#f9eddd] px-2 py-0.5 text-[11px] font-bold text-[#52311a]">
                        {{ $order->source === 'whatsapp' ? 'WhatsApp Manual' : 'Website' }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-[#796b62]">
                    Dibuat pada {{ $order->created_at?->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }} WIB
                    &bull; Jadwal: <strong class="text-[#52311a]">{{ $order->scheduled_date?->locale('id')->isoFormat('dddd, D MMM YYYY') }} ({{ $order->scheduled_time_slot }})</strong>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.orders.index') }}" class="btn-kelana-outline px-3 py-2 text-xs">&larr; Daftar</a>
                <a href="{{ route('admin.orders.edit', $order) }}" class="btn-kelana-outline px-3 py-2 text-xs">Edit Detail</a>
                <a href="{{ route('admin.orders.receipt', $order) }}" class="btn-kelana-outline px-3 py-2 text-xs">Cetak Nota</a>
                <a href="{{ $customerWhatsappUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-700 px-3.5 py-2 text-xs font-bold text-white hover:bg-emerald-800">
                    <span>Chat WhatsApp</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                    </svg>
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            {{-- Kolom Kiri (8 kolom): Rincian Item, Pembayaran, Informasi Pengiriman/Pickup & Log --}}
            <div class="space-y-6 lg:col-span-8">
                {{-- Rincian Item & Tagihan --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Rincian Item &amp; Pembayaran</h2>
                    <div class="mt-4 divide-y divide-[#efe7dc]">
                        @foreach($order->items as $item)
                            <div class="flex items-start justify-between gap-4 py-3 text-xs">
                                <div>
                                    <p class="font-bold text-[#28211c]">
                                        {{ $item->quantity }}x {{ $item->product_name }}
                                        @if($item->variant_name)
                                            <span class="font-normal text-[#796b62]">({{ $item->variant_name }})</span>
                                        @endif
                                    </p>
                                    <p class="text-[11px] text-[#8d7d70]">@ Rp {{ number_format($item->unit_price, 0, ',', '.') }}</p>
                                    @if($item->custom_note)
                                        <p class="mt-1 inline-block rounded bg-[#fdfbf6] px-2 py-0.5 text-[11px] font-medium text-[#52311a]">
                                            Tulisan Kue: "{{ $item->custom_note }}"
                                        </p>
                                    @endif
                                </div>
                                <p class="font-bold text-[#28211c]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if($order->customer_note)
                        <div class="mt-3 rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-3 text-xs text-[#5e524a]">
                            <strong class="text-[#52311a]">Catatan Pesanan:</strong> {{ $order->customer_note }}
                        </div>
                    @endif

                    <div class="mt-4 space-y-1.5 border-t border-[#efe7dc] pt-4 text-xs">
                        <div class="flex justify-between text-[#5e524a]">
                            <span>Subtotal Produk</span>
                            <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-[#5e524a]">
                            <span>Ongkos Kirim ({{ $order->delivery_type === 'pickup' ? 'Pickup' : ($order->shipping_method_snapshot ?? 'Delivery') }})</span>
                            <span>Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between pt-1 text-sm font-bold text-[#52311a]">
                            <span>Total Pembayaran</span>
                            <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- Info Pembayaran Midtrans / Manual --}}
                    @if($order->latestPayment)
                        <div class="mt-4 rounded-xl border border-[#e8dfd3] bg-[#fdfbf6] p-3.5 text-xs">
                            <p class="font-bold text-[#52311a]">Informasi Transaksi Pembayaran</p>
                            <div class="mt-1 grid gap-1 text-[#5e524a] sm:grid-cols-2">
                                <p>Kanal: <strong>{{ $order->latestPayment->channel_label }}</strong></p>
                                <p>Gateway: <strong>{{ strtoupper($order->latestPayment->gateway) }}</strong></p>
                                <p>Status: <strong>{{ strtoupper($order->latestPayment->status) }}</strong></p>
                                <p>Waktu Lunas: <strong>{{ $order->paid_at ? $order->paid_at->format('d M Y H:i').' WIB' : 'Belum lunas' }}</strong></p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Data Pelanggan & Penerimaan --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-[#52311a]">Pemesan &amp; Informasi Penerimaan</h2>
                        <span class="rounded-full bg-[#f9eddd] px-3 py-0.5 text-[11px] font-bold text-[#52311a]">
                            {{ $customerOrderCount > 1 ? 'Pelanggan Berulang · '.$customerOrderCount.' order' : 'Pelanggan Baru' }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-4 text-xs sm:grid-cols-2">
                        <div class="rounded-xl bg-[#fdfbf6] p-3.5">
                            <p class="text-[11px] font-bold text-[#936b49] uppercase">Pemesan</p>
                            <p class="mt-1 font-bold text-[#28211c]">{{ $order->customer_name }}</p>
                            <p class="text-[#5e524a]">WA: {{ $order->customer_phone }}</p>
                            @if($order->customer_email)
                                <p class="text-[#5e524a]">Email: {{ $order->customer_email }}</p>
                            @endif
                            @if($order->user)
                                <a href="{{ route('admin.customers.show', $order->user) }}" class="mt-2 inline-block font-bold text-[#52311a] hover:underline">
                                    Lihat Profil Pelanggan ({{ $order->user->customer_code }}) &rarr;
                                </a>
                            @endif
                        </div>

                        <div class="rounded-xl bg-[#fdfbf6] p-3.5">
                            <p class="text-[11px] font-bold text-[#936b49] uppercase">
                                {{ $order->delivery_type === 'pickup' ? 'Pengambilan (Pickup)' : 'Penerima & Alamat' }}
                            </p>
                            <p class="mt-1 font-bold text-[#28211c]">{{ $order->recipient_name }} ({{ $order->recipient_phone }})</p>
                            @if($order->delivery_type === 'pickup')
                                <p class="mt-1 font-semibold text-[#52311a]">{{ $order->pickupLocation?->name }}</p>
                                <p class="text-[#5e524a]">{{ $order->pickupLocation?->address }}</p>
                            @else
                                <p class="mt-1 text-[#5e524a]">{{ $order->delivery_address }}</p>
                                @if($order->delivery_address_detail)
                                    <p class="text-[#796b62]">Patokan: {{ $order->delivery_address_detail }}</p>
                                @endif
                                @if($order->delivery_distance_km)
                                    <p class="mt-1 text-[11px] font-semibold text-[#52311a]">
                                        Jarak dari Dapur: {{ number_format($order->delivery_distance_km, 1, ',', '.') }} km
                                    </p>
                                @endif
                                @if($order->maps_url)
                                    <a href="{{ $order->maps_url }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 font-bold text-[#52311a] hover:underline">
                                        <span>Buka Titik di Maps</span>
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                        </svg>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Form Kurir & Bukti Pengiriman (Khusus Delivery) --}}
                @if($order->delivery_type === 'delivery' && !in_array($order->order_status, ['dibatalkan', 'kedaluwarsa']))
                    <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                        <h2 class="text-sm font-semibold text-[#52311a]">Kurir Pengiriman &amp; Foto Bukti Sampai</h2>
                        <form method="POST" action="{{ route('admin.orders.update-courier', $order) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="courier_name" class="kelana-label">Nama Kurir / Driver *</label>
                                    <input id="courier_name" name="courier_name" type="text" value="{{ old('courier_name', $order->courier_name) }}"
                                        required placeholder="Contoh: Pak Budi (Kurir Internal / Lalamove)" class="kelana-input text-xs">
                                </div>
                                <div>
                                    <label for="courier_phone" class="kelana-label">Nomor HP Kurir (Hanya Angka) *</label>
                                    <input id="courier_phone" name="courier_phone" type="tel" inputmode="numeric" pattern="[0-9]*"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        value="{{ old('courier_phone', $order->courier_phone) }}"
                                        placeholder="081234567890" class="kelana-input text-xs">
                                    @if($order->courier_phone)
                                        <a href="https://wa.me/{{ \App\Models\StoreSetting::normalizePhone($order->courier_phone) }}" target="_blank" rel="noopener"
                                            class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:underline">
                                            <span>Hubungi WA Kurir</span>
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                                <div>
                                    <label for="courier_vehicle_plate" class="kelana-label">Plat Nomor Kendaraan</label>
                                    <input id="courier_vehicle_plate" name="courier_vehicle_plate" type="text" value="{{ old('courier_vehicle_plate', $order->courier_vehicle_plate) }}"
                                        placeholder="W 1234 AB" class="kelana-input text-xs">
                                </div>
                                <div>
                                    <label for="received_by" class="kelana-label">Nama Penerima Paket</label>
                                    <input id="received_by" name="received_by" type="text" value="{{ old('received_by', $order->received_by) }}"
                                        placeholder="Nama penerima di lokasi" class="kelana-input text-xs">
                                </div>
                            </div>

                            <div>
                                <label for="delivery_proof" class="kelana-label">Foto Bukti Pengiriman (Maks 2MB)</label>
                                <input id="delivery_proof" name="delivery_proof" type="file" accept="image/*" class="kelana-input py-1.5 text-xs">
                                @if($order->delivery_proof_path)
                                    <div class="mt-3">
                                        <p class="mb-1 text-[11px] font-semibold text-emerald-700">Foto bukti tersimpan saat ini:</p>
                                        <img src="{{ asset($order->delivery_proof_path) }}" alt="Bukti Pengiriman"
                                            class="h-36 rounded-xl border border-[#e8dfd3] object-cover">
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-4 text-xs">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="mark_in_delivery" value="1" class="rounded border-[#b18950] text-[#52311a]">
                                    <span>Sekaligus ubah status ke <strong>Dalam Pengiriman</strong></span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="mark_delivered" value="1" class="rounded border-[#b18950] text-emerald-700">
                                    <span>Sekaligus tandai <strong>Sampai Tujuan</strong></span>
                                </label>
                            </div>

                            <button type="submit" class="btn-kelana-primary px-4 py-2 text-xs">
                                Simpan Info Kurir &amp; Bukti
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Riwayat Log Aktivitas Pesanan --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Riwayat Aktivitas &amp; Audit Pesanan</h2>
                    <div class="mt-4 space-y-3">
                        @forelse($order->logs as $log)
                            <div class="flex gap-3 border-l-2 border-[#b18950] pl-3 text-xs">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-bold text-[#28211c]">{{ $log->title }}</p>
                                        <span class="text-[10px] text-[#8d7d70]">{{ $log->created_at?->format('d M Y H:i') }} WIB</span>
                                    </div>
                                    <p class="mt-0.5 text-[#5e524a]">{{ $log->description }}</p>
                                    <p class="mt-0.5 text-[10px] text-[#936b49]">Oleh: {{ $log->actor_name }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-[#796b62]">Belum ada catatan riwayat.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan (4 kolom): Kontrol Status, Pelunasan Manual, Pembatalan & Ulasan --}}
            <div class="space-y-5 lg:col-span-4">
                {{-- Konfirmasi Pembayaran Manual (Khusus Belum Dibayar) --}}
                @if($order->payment_status !== 'lunas' && !in_array($order->order_status, ['dibatalkan', 'kedaluwarsa']))
                    <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-xs">
                        <h3 class="text-sm font-semibold text-amber-900">Pelunasan Pembayaran</h3>
                        <p class="mt-1 text-xs text-amber-800">
                            Pesanan website biasanya lunas otomatis lewat webhook Midtrans. Untuk pesanan WhatsApp manual atau rekonsiliasi, tandai lunas di sini.
                        </p>
                        <form method="POST" action="{{ route('admin.orders.mark-paid', $order) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800">
                                Tandai Lunas &amp; Proses Produksi
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Perbarui Progres Status Pesanan --}}
                @if(!in_array($order->order_status, ['dibatalkan', 'kedaluwarsa']))
                    <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                        <h3 class="text-sm font-semibold text-[#52311a]">Perbarui Status Pesanan</h3>
                        <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <label for="order_status" class="kelana-label">Status Progres</label>
                                <select id="order_status" name="order_status" class="kelana-input text-xs">
                                    @foreach($availableStatuses as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" @selected($order->order_status === $statusKey)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="status_received_by" class="kelana-label">Diterima / Diambil Oleh (Opsional)</label>
                                <input id="status_received_by" name="received_by" type="text" value="{{ old('received_by', $order->received_by) }}"
                                    placeholder="{{ $order->recipient_name }}" class="kelana-input text-xs">
                            </div>
                            <div>
                                <label for="status_note" class="kelana-label">Catatan untuk Pelanggan / Log</label>
                                <textarea id="status_note" name="status_note" rows="2" placeholder="Contoh: Brownies selesai dipanggang dan sedang dikemas." class="kelana-input text-xs"></textarea>
                            </div>
                            <button type="submit" class="btn-kelana-primary w-full py-2 text-xs">
                                Simpan Perubahan Status
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Ulasan Pelanggan jika Sudah Ada --}}
                @if($order->review)
                    <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                        <h3 class="text-sm font-semibold text-[#52311a]">Ulasan Pelanggan</h3>
                        <p class="mt-2 text-xs font-bold text-amber-600">{{ str_repeat('★', $order->review->rating) }} ({{ $order->review->rating }}/5)</p>
                        <p class="mt-1 text-xs text-[#5e524a]">"{{ $order->review->comment }}"</p>
                        <a href="{{ route('admin.reviews.show', $order->review) }}" class="mt-2 inline-block text-xs font-bold text-[#52311a] hover:underline">
                            Moderasi &amp; Balas Ulasan &rarr;
                        </a>
                    </div>
                @endif

                {{-- Pembatalan / Hapus Arsip Pesanan --}}
                @if(!in_array($order->order_status, ['dibatalkan', 'kedaluwarsa']))
                    <div class="rounded-2xl border border-red-200 bg-white p-5 shadow-xs">
                        <h3 class="text-sm font-semibold text-red-800">Batalkan Pesanan</h3>
                        <p class="mt-1 text-[11px] text-[#796b62]">
                            Pembatalan wajib menyertakan alasan agar terekam di riwayat pelanggan.
                        </p>
                        <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="mt-3 space-y-3">
                            @csrf
                            <div>
                                <label for="cancellation_reason" class="kelana-label text-red-800">Alasan Pembatalan *</label>
                                <input id="cancellation_reason" name="cancellation_reason" type="text" required
                                    placeholder="Contoh: Dibatalkan atas permintaan pelanggan via WA" class="kelana-input text-xs">
                            </div>
                            @if($order->payment_status === 'lunas')
                                <label class="inline-flex items-center gap-2 text-xs text-[#5e524a]">
                                    <input type="checkbox" name="mark_refund" value="1" class="rounded border-red-300 text-red-700">
                                    <span>Tandai status pembayaran menjadi Refund</span>
                                </label>
                            @endif
                            <button type="submit" class="w-full rounded-lg bg-red-700 px-4 py-2 text-xs font-bold text-white hover:bg-red-800">
                                Batalkan Pesanan Ini
                            </button>
                        </form>
                    </div>
                @else
                    <div class="rounded-2xl border border-red-200 bg-red-50/60 p-5 shadow-xs">
                        <h3 class="text-sm font-semibold text-red-800">Pesanan Ditutup ({{ $order->orderStatusLabel() }})</h3>
                        <p class="mt-1 text-xs text-red-700">Alasan: {{ $order->cancellation_reason ?? '-' }}</p>
                        <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" class="mt-3"
                            onsubmit="return confirm('Yakin ingin menghapus arsip pesanan {{ $order->order_number }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-lg border border-red-300 bg-white px-4 py-2 text-xs font-bold text-red-700 hover:bg-red-100">
                                Hapus Arsip Pesanan
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

