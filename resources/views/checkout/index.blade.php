@extends('layouts.app')

@section('title', 'Checkout Pesanan — Brownies Kelana')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="{{ route('cart.index') }}" class="text-xs font-semibold text-[#52311a] hover:underline">&larr; Kembali ke Keranjang</a>
                <h1 class="mt-1 font-serif text-3xl font-bold text-[#52311a]">Checkout Pesanan</h1>
            </div>
        </div>

        <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form" class="grid gap-8 lg:grid-cols-12">
            @csrf

            {{-- Kolom Kiri: Data Pemesan, Pengiriman/Pickup, Kalender H-1, Alamat/Peta & Metode Midtrans --}}
            <div class="space-y-6 lg:col-span-8">

                {{-- 1. Informasi Pemesan --}}
                <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs sm:p-6">
                    <h2 class="text-base font-bold text-[#52311a]">1. Informasi Pemesan</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="kelana-label">Nama Pemesan *</label>
                            <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $user->name) }}" required class="kelana-input">
                        </div>
                        <div>
                            <label for="customer_email" class="kelana-label">Email *</label>
                            <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email', $user->email) }}" required class="kelana-input">
                        </div>
                        <div>
                            <label for="customer_phone" class="kelana-label">No. HP / WhatsApp *</label>
                            <input id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone', $user->phone) }}" required class="kelana-input">
                        </div>
                        <div>
                            <label for="customer_secondary_phone" class="kelana-label">No. HP / Telepon 2 (Opsional)</label>
                            <input id="customer_secondary_phone" name="customer_secondary_phone" type="text" value="{{ old('customer_secondary_phone', $user->secondary_phone) }}" placeholder="Opsional" class="kelana-input">
                        </div>
                    </div>
                    <p class="mt-3 text-[11px] text-[#796b62]">
                        *Pembaruan status otomatis muncul di Dashboard akun. WhatsApp digunakan untuk komunikasi kurir &amp; layanan pelanggan.
                    </p>
                </div>

                {{-- 2. Pilihan Metode Penerimaan: Kirim ke Alamat vs Ambil Sendiri (Pickup) --}}
                <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs sm:p-6">
                    <h2 class="text-base font-bold text-[#52311a]">2. Metode Penerimaan Pesanan</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="delivery_type" value="delivery" class="peer sr-only delivery-type-radio"
                                {{ old('delivery_type', 'delivery') === 'delivery' ? 'checked' : '' }}>
                            <div class="rounded-xl border-2 border-[#e3d5c3] bg-[#fdfbf6] p-4 transition peer-checked:border-[#52311a] peer-checked:bg-[#f9eddd]">
                                <p class="text-sm font-bold text-[#52311a]">Kirim ke Alamat</p>
                                <p class="mt-0.5 text-[11px] text-[#796b62]">Diantar kurir kelokasi tujuan sesuai jadwal.</p>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="delivery_type" value="pickup" class="peer sr-only delivery-type-radio"
                                {{ old('delivery_type') === 'pickup' ? 'checked' : '' }}>
                            <div class="rounded-xl border-2 border-[#e3d5c3] bg-[#fdfbf6] p-4 transition peer-checked:border-[#52311a] peer-checked:bg-[#f9eddd]">
                                <p class="text-sm font-bold text-[#52311a]">Ambil Sendiri (Pick-up)</p>
                                <p class="mt-0.5 text-[11px] text-emerald-700 font-semibold">Gratis Ongkir (Rp 0) &bull; {{ $pickupLocations->count() }} lokasi resmi</p>
                            </div>
                        </label>
                    </div>

                    {{-- Informasi Penerima / Pengambil --}}
                    <div class="mt-6 border-t border-[#f0e5d6] pt-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-bold text-[#28211c]" id="recipient-heading">Informasi Penerima</h3>
                            <button type="button" id="copy-customer-to-recipient" class="text-xs font-semibold text-[#936b49] hover:text-[#52311a] hover:underline">
                                Samakan dengan data pemesan
                            </button>
                        </div>

                        <div class="mt-3 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="recipient_name" class="kelana-label">Nama Penerima / Pengambil *</label>
                                <input id="recipient_name" name="recipient_name" type="text" value="{{ old('recipient_name', $user->name) }}" required class="kelana-input">
                            </div>
                            <div>
                                <label for="recipient_phone" class="kelana-label">No. HP Penerima *</label>
                                <input id="recipient_phone" name="recipient_phone" type="text" value="{{ old('recipient_phone', $user->phone) }}" required class="kelana-input">
                            </div>
                            <div>
                                <label for="recipient_secondary_phone" class="kelana-label">No. HP Kedua (Opsional)</label>
                                <input id="recipient_secondary_phone" name="recipient_secondary_phone" type="text" value="{{ old('recipient_secondary_phone', $user->secondary_phone) }}" placeholder="Opsional" class="kelana-input">
                            </div>
                        </div>
                    </div>

                    {{-- Panel Alamat Tujuan & Peta / Zona Ongkir (Tampil saat Delivery) --}}
                    <div id="delivery-section" class="mt-6 space-y-4 border-t border-[#f0e5d6] pt-5 {{ old('delivery_type', 'delivery') === 'pickup' ? 'hidden' : '' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 class="text-sm font-bold text-[#28211c]">Alamat Tujuan Pengiriman</h3>
                                <p class="text-[11px] text-[#796b62]">
                                    @if($storeSetting->shipping_method === 'distance')
                                        Tarif jarak: Rp {{ number_format($storeSetting->distance_base_fee, 0, ',', '.') }} untuk {{ (float) $storeSetting->distance_base_km }} km pertama + Rp {{ number_format($storeSetting->distance_per_km_fee, 0, ',', '.') }}/km (maks {{ (float) $storeSetting->distance_max_km }} km).
                                    @elseif($storeSetting->shipping_method === 'zone')
                                        Pilih zona pengiriman sesuai kecamatan alamat tujuan.
                                    @else
                                        Ongkir flat aktif: Rp {{ number_format($storeSetting->flat_shipping_fee, 0, ',', '.') }} untuk seluruh area layanan.
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($storeSetting->shipping_method === 'zone')
                            <div>
                                <label for="shipping_zone_id" class="kelana-label">Zona Pengiriman *</label>
                                <select id="shipping_zone_id" name="shipping_zone_id" class="kelana-input">
                                    @foreach($shippingZones as $zone)
                                        <option value="{{ $zone->id }}" data-fee="{{ $zone->fee }}" {{ old('shipping_zone_id') == $zone->id ? 'selected' : '' }}>
                                            {{ $zone->name }} ({{ $zone->area_description }}) — Rp {{ number_format($zone->fee, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label for="delivery_address" class="kelana-label">Alamat Lengkap Tujuan *</label>
                            <textarea id="delivery_address" name="delivery_address" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan/kecamatan..." class="kelana-input">{{ old('delivery_address', $user->saved_address) }}</textarea>
                        </div>

                        <div>
                            <label for="delivery_address_detail" class="kelana-label">Detail Patokan Alamat (Opsional)</label>
                            <input id="delivery_address_detail" name="delivery_address_detail" type="text" value="{{ old('delivery_address_detail', $user->saved_address_detail) }}"
                                placeholder="Contoh: Rumah pagar putih sebelah taman, hubungi saat tiba" class="kelana-input">
                        </div>

                        {{-- Peta Interaktif Leaflet.js untuk Menentukan Titik Pin & Jarak Rute --}}
                        <div class="rounded-2xl border border-[#e3d5c3] bg-[#fdfbf6] p-4">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs font-bold text-[#52311a]">Pilih Titik Pin Peta Pengiriman</p>
                                    <p class="text-[11px] text-[#796b62]">Klik atau geser penanda pada peta agar kurir tepat lokasi &amp; estimasi jarak akurat.</p>
                                </div>
                                <span id="distance-badge" class="rounded-full bg-[#f2e4cd] px-3 py-1 text-[11px] font-bold text-[#52311a]">
                                    Menghitung titik...
                                </span>
                            </div>

                            <div id="checkout-map" class="h-64 w-full rounded-xl border border-[#dfd2c0]"></div>

                            <input type="hidden" name="delivery_latitude" id="delivery_latitude" value="{{ old('delivery_latitude', $user->saved_latitude ?? -7.3318500) }}">
                            <input type="hidden" name="delivery_longitude" id="delivery_longitude" value="{{ old('delivery_longitude', $user->saved_longitude ?? 112.7244100) }}">
                            <input type="hidden" name="delivery_distance_km" id="delivery_distance_km" value="{{ old('delivery_distance_km') }}">

                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-[#796b62]">
                                <span id="coord-preview">Koordinat terpilih siap dikirim ke kurir.</span>
                                <a id="google-maps-preview-link" href="https://maps.google.com/?q={{ old('delivery_latitude', $user->saved_latitude ?? -7.3318500) }},{{ old('delivery_longitude', $user->saved_longitude ?? 112.7244100) }}" target="_blank" rel="noopener" class="font-bold text-[#52311a] hover:underline">
                                    Lihat titik di Google Maps &nearr;
                                </a>
                            </div>

                            <p id="out-of-range-warning" class="mt-2 hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"></p>
                        </div>
                    </div>

                    {{-- Panel Pilihan 3 Lokasi Pickup Resmi (Tampil saat Pickup) --}}
                    <div id="pickup-section" class="mt-6 space-y-3 border-t border-[#f0e5d6] pt-5 {{ old('delivery_type', 'delivery') === 'pickup' ? '' : 'hidden' }}">
                        <h3 class="text-sm font-bold text-[#28211c]">Pilih Lokasi Pengambilan (Pick-up Gratis Ongkir) *</h3>
                        <div class="grid gap-3">
                            @foreach($pickupLocations as $index => $location)
                                <label class="cursor-pointer">
                                    <input type="radio" name="pickup_location_id" value="{{ $location->id }}" class="peer sr-only"
                                        {{ old('pickup_location_id') == $location->id || (!old('pickup_location_id') && $index === 0) ? 'checked' : '' }}>
                                    <div class="flex flex-col justify-between gap-2 rounded-xl border-2 border-[#e3d5c3] bg-[#fdfbf6] p-4 transition peer-checked:border-[#52311a] peer-checked:bg-[#f9eddd] sm:flex-row sm:items-center">
                                        <div>
                                            <p class="text-xs font-bold text-[#52311a]">{{ $location->name }}</p>
                                            <p class="mt-0.5 text-[11px] text-[#5e524a]">{{ $location->address }}</p>
                                            @if($location->operating_info)
                                                <p class="mt-1 text-[10px] font-semibold text-emerald-700">{{ $location->operating_info }}</p>
                                            @endif
                                        </div>
                                        @if($location->maps_url)
                                            <a href="{{ $location->maps_url }}" target="_blank" rel="noopener" onclick="event.stopPropagation();"
                                                class="shrink-0 text-[11px] font-bold text-[#52311a] underline">
                                                Google Maps &nearr;
                                            </a>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 3. Kalender Jadwal Pre-order H-1 & Slot Jam --}}
                <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs sm:p-6">
                    <h2 class="text-base font-bold text-[#52311a]">3. Jadwal Pengiriman / Pengambilan (Pre-order H-{{ $storeSetting->preorder_min_days }})</h2>
                    <p class="mt-1 text-xs text-[#796b62]">
                        Kuota maksimal <strong>{{ $storeSetting->daily_quota }} pesanan/hari</strong>. {{ $storeSetting->operatingHoursSummary() }}.
                    </p>

                    <input type="hidden" name="scheduled_date" id="scheduled_date_input"
                        value="{{ old('scheduled_date', $defaultAvailableDay['date'] ?? '') }}" required>

                    <div class="mt-4 flex flex-wrap gap-3 text-[11px]">
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-[#52311a]"></span> Dipilih</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-[#52311a] bg-white"></span> Tersedia</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-[#e5ddd2]"></span> Tutup / Lewat</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-amber-200"></span> Libur</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-red-200"></span> Penuh</span>
                    </div>

                    <div class="mt-4 grid grid-cols-4 gap-2 sm:grid-cols-7">
                        @foreach($calendarDays as $day)
                            @php
                                $selectedDate = old('scheduled_date', $defaultAvailableDay['date'] ?? '');
                                $isSelected = $selectedDate === $day['date'];
                            @endphp
                            <button type="button"
                                data-date="{{ $day['date'] }}"
                                data-available="{{ $day['available'] ? '1' : '0' }}"
                                data-reason="{{ $day['reason'] }}"
                                data-used="{{ $day['used_quota'] }}"
                                data-remaining="{{ $day['remaining_quota'] }}"
                                {{ $day['available'] ? '' : 'disabled' }}
                                class="calendar-day-btn flex flex-col items-center rounded-xl border p-2 text-center transition
                                @if($isSelected && $day['available'])
                                    border-[#52311a] bg-[#52311a] text-white shadow-xs
                                @elseif($day['available'])
                                    border-[#dfd2c0] bg-white text-[#28211c] hover:border-[#52311a] hover:bg-[#f9eddd]
                                @elseif($day['status'] === 'holiday')
                                    cursor-not-allowed border-amber-200 bg-amber-50 text-amber-800
                                @elseif($day['status'] === 'full')
                                    cursor-not-allowed border-red-200 bg-red-50 text-red-700
                                @else
                                    cursor-not-allowed border-[#eee5d8] bg-[#f5efe6] text-[#a5968a]
                                @endif">
                                <span class="text-[10px] font-semibold uppercase">{{ $day['day_short'] }}</span>
                                <span class="text-sm font-extrabold">{{ $day['day_number'] }}</span>
                                <span class="mt-0.5 text-[9px]">
                                    @if($day['is_today'])
                                        Hari ini
                                    @elseif($day['status'] === 'holiday')
                                        Libur
                                    @elseif($day['status'] === 'full')
                                        Penuh
                                    @elseif($day['status'] === 'closed')
                                        Tutup
                                    @elseif($day['available'])
                                        Sisa {{ $day['remaining_quota'] }}
                                    @else
                                        &mdash;
                                    @endif
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <div id="selected-date-banner" class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-[#f9eddd] px-4 py-3 text-xs">
                        <span class="font-semibold text-[#52311a]" id="selected-date-label">
                            @if($defaultAvailableDay)
                                Tanggal dipilih: {{ \Carbon\Carbon::parse(old('scheduled_date', $defaultAvailableDay['date']))->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                            @else
                                Belum ada tanggal tersedia dipilih.
                            @endif
                        </span>
                        <span class="font-bold text-[#796b62]" id="selected-quota-label">
                            @if($defaultAvailableDay)
                                Terpakai: {{ $defaultAvailableDay['used_quota'] }} &middot; Sisa: {{ $defaultAvailableDay['remaining_quota'] }} pesanan
                            @endif
                        </span>
                    </div>

                    <div class="mt-4">
                        <label for="scheduled_time_slot" class="kelana-label">Slot Jam Pengiriman / Pengambilan *</label>
                        <select id="scheduled_time_slot" name="scheduled_time_slot" required class="kelana-input">
                            @foreach($storeSetting->delivery_slots ?? ['09.00-11.00 WIB', '13.00-15.00 WIB'] as $slot)
                                <option value="{{ $slot }}" {{ old('scheduled_time_slot') === $slot ? 'selected' : '' }}>{{ $slot }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- 4. Catatan & Metode Pembayaran 100% Midtrans --}}
                <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs sm:p-6">
                    <h2 class="text-base font-bold text-[#52311a]">4. Catatan &amp; Metode Pembayaran (Midtrans)</h2>

                    <div class="mt-4">
                        <label for="customer_note" class="kelana-label">Catatan Pesanan (Opsional)</label>
                        <input id="customer_note" name="customer_note" type="text" value="{{ old('customer_note', $cart->order_note) }}"
                            placeholder="Contoh: Tanpa kartu ucapan. Terima kasih." class="kelana-input">
                    </div>

                    <div class="mt-5">
                        <label class="kelana-label uppercase">Pilih Kanal Pembayaran Midtrans *</label>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            @php
                                $midtransChannels = [
                                    ['midtrans_snap', 'Midtrans Snap', 'Semua metode instan dalam popup Snap', null],
                                    ['qris', 'QRIS', 'Scan kode QR dari semua e-wallet & bank', 'images/kelana/pay-qris.png'],
                                    ['credit_card', 'Kartu Kredit / Debit', 'Visa, Mastercard, JCB & Amex', 'images/kelana/pay-cards.png'],
                                    ['mandiri_va', 'Mandiri Virtual Account', 'Mandiri Bill Payment & Livin’', null],
                                    ['bca_va', 'BCA Virtual Account', 'Transfer cepat BCA Mobile & ATM', 'images/kelana/pay-bca.png'],
                                    ['gopay', 'GoPay', 'Konfirmasi otomatis aplikasi GoPay', 'images/kelana/pay-gopay.png'],
                                ];
                            @endphp

                            @foreach($midtransChannels as [$key, $title, $subtitle, $iconPath])
                                <label class="cursor-pointer">
                                    <input type="radio" name="payment_method" value="{{ $key }}" class="peer sr-only"
                                        {{ old('payment_method', 'midtrans_snap') === $key ? 'checked' : '' }}>
                                    <div class="flex items-center justify-between gap-3 rounded-xl border-2 border-[#e3d5c3] bg-[#fdfbf6] p-3.5 transition peer-checked:border-[#52311a] peer-checked:bg-[#f9eddd]">
                                        <div>
                                            <p class="text-xs font-bold text-[#28211c]">{{ $title }}</p>
                                            <p class="text-[10px] text-[#796b62]">{{ $subtitle }}</p>
                                        </div>
                                        @if($iconPath)
                                            <img src="{{ asset($iconPath) }}" alt="{{ $title }}" class="h-6 w-auto object-contain">
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-3 rounded-xl bg-[#f9eddd]/70 px-3.5 py-2 text-[11px] text-[#5e524a]">
                            Batas waktu penyelesaian pembayaran adalah <strong>{{ $storeSetting->payment_timeout_minutes }} menit</strong> setelah pesanan dibuat. Sistem memverifikasi pembayaran otomatis.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Ringkasan Pemesanan Sticky --}}
            <div class="lg:col-span-4">
                <div class="sticky top-24 rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs sm:p-6">
                    <h2 class="text-base font-bold text-[#52311a]">Ringkasan Pemesanan</h2>

                    <div class="mt-4 space-y-3 border-b border-[#f0e5d6] pb-4 text-xs">
                        @foreach($cart->items as $item)
                            <div class="flex justify-between gap-3">
                                <div>
                                    <p class="font-bold text-[#28211c]">{{ $item->quantity }}&times; {{ $item->product->name }}</p>
                                    <p class="text-[11px] text-[#796b62]">{{ $item->variant?->name ?? 'Reguler' }}</p>
                                    @if($item->custom_note)
                                        <p class="text-[10px] text-[#936b49]">Tulisan: "{{ $item->custom_note }}"</p>
                                    @endif
                                </div>
                                <span class="shrink-0 font-bold text-[#52311a]">Rp {{ number_format($item->lineSubtotal(), 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 space-y-2 border-b border-[#f0e5d6] pb-4 text-xs">
                        <div class="flex justify-between text-[#5e524a]">
                            <span>Subtotal &middot; {{ $cart->totalQuantity() }} item</span>
                            <span class="font-bold text-[#28211c]">Rp {{ number_format($cart->subtotalAmount(), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-[#5e524a]">
                            <span id="summary-shipping-label">{{ $initialShipping['label'] }}</span>
                            <span class="font-bold text-[#28211c]" id="summary-shipping-fee">Rp {{ number_format($initialShipping['fee'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-sm font-bold text-[#52311a]">Total Bayar</span>
                        <span id="summary-total-amount" class="text-xl font-extrabold text-[#52311a]">
                            Rp {{ number_format($cart->subtotalAmount() + $initialShipping['fee'], 0, ',', '.') }}
                        </span>
                    </div>

                    <button type="submit" id="checkout-submit-btn" class="btn-kelana-primary mt-6 w-full rounded-full py-3 text-xs tracking-wide uppercase">
                        Lanjut Pembayaran Midtrans &rarr;
                    </button>
                    <p class="mt-3 text-center text-[10px] text-[#8d7d70]">
                        Dengan melanjutkan, kamu menyetujui jadwal dan rincian pesanan di atas.
                    </p>
                </div>
            </div>
        </form>
    </section>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const subtotal = {{ (int) $cart->subtotalAmount() }};
                const storeLat = {{ (float) $storeSetting->store_latitude }};
                const storeLng = {{ (float) $storeSetting->store_longitude }};
                const shippingMethod = @json($storeSetting->shipping_method);
                const checkShippingUrl = @json(route('checkout.check-shipping'));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const deliverySection = document.getElementById('delivery-section');
                const pickupSection = document.getElementById('pickup-section');
                const shippingLabelEl = document.getElementById('summary-shipping-label');
                const shippingFeeEl = document.getElementById('summary-shipping-fee');
                const totalAmountEl = document.getElementById('summary-total-amount');
                const submitBtn = document.getElementById('checkout-submit-btn');
                const outOfRangeWarn = document.getElementById('out-of-range-warning');
                const distanceBadge = document.getElementById('distance-badge');

                const latInput = document.getElementById('delivery_latitude');
                const lngInput = document.getElementById('delivery_longitude');
                const distInput = document.getElementById('delivery_distance_km');
                const mapsPreviewLink = document.getElementById('google-maps-preview-link');
                const coordPreview = document.getElementById('coord-preview');

                const formatRp = (num) => 'Rp ' + parseInt(num || 0, 10).toLocaleString('id-ID');

                const updateShippingSummary = async (overrideDistanceKm = null) => {
                    const deliveryType = document.querySelector('.delivery-type-radio:checked')?.value || 'delivery';
                    const zoneSelect = document.getElementById('shipping_zone_id');
                    const payload = {
                        delivery_type: deliveryType,
                        shipping_zone_id: zoneSelect ? zoneSelect.value : null,
                        latitude: latInput ? latInput.value : null,
                        longitude: lngInput ? lngInput.value : null,
                        distance_km: overrideDistanceKm !== null ? overrideDistanceKm : (distInput.value || null),
                    };

                    try {
                        const res = await fetch(checkShippingUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(payload),
                        });
                        const data = await res.json();
                        if (!data.shipping) {
                            return;
                        }

                        shippingLabelEl.textContent = data.shipping.label;
                        shippingFeeEl.textContent = formatRp(data.shipping.fee);
                        totalAmountEl.textContent = formatRp(subtotal + data.shipping.fee);

                        if (data.shipping.within_range) {
                            outOfRangeWarn.classList.add('hidden');
                            submitBtn.disabled = false;
                        } else {
                            outOfRangeWarn.textContent = data.shipping.label + '. Silakan pilih titik lokasi lebih dekat atau pilih Ambil Sendiri (Pick-up).';
                            outOfRangeWarn.classList.remove('hidden');
                            submitBtn.disabled = true;
                        }
                    } catch (err) {
                        console.error(err);
                    }
                };

                // Toggle Kirim ke Alamat vs Pickup
                document.querySelectorAll('.delivery-type-radio').forEach((radio) => {
                    radio.addEventListener('change', () => {
                        if (radio.value === 'pickup' && radio.checked) {
                            deliverySection.classList.add('hidden');
                            pickupSection.classList.remove('hidden');
                        } else if (radio.value === 'delivery' && radio.checked) {
                            deliverySection.classList.remove('hidden');
                            pickupSection.classList.add('hidden');
                            if (window.kelanaMap) {
                                setTimeout(() => window.kelanaMap.invalidateSize(), 150);
                            }
                        }
                        updateShippingSummary();
                    });
                });

                document.getElementById('shipping_zone_id')?.addEventListener('change', () => updateShippingSummary());

                // Salin data pemesan ke penerima
                document.getElementById('copy-customer-to-recipient')?.addEventListener('click', () => {
                    document.getElementById('recipient_name').value = document.getElementById('customer_name').value;
                    document.getElementById('recipient_phone').value = document.getElementById('customer_phone').value;
                });

                // Kalender interaktif H-1
                const scheduledDateInput = document.getElementById('scheduled_date_input');
                const selectedDateLabel = document.getElementById('selected-date-label');
                const selectedQuotaLabel = document.getElementById('selected-quota-label');

                document.querySelectorAll('.calendar-day-btn').forEach((btn) => {
                    if (btn.dataset.available !== '1') {
                        return;
                    }
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('.calendar-day-btn[data-available="1"]').forEach((other) => {
                            other.classList.remove('border-[#52311a]', 'bg-[#52311a]', 'text-white', 'shadow-xs');
                            other.classList.add('border-[#dfd2c0]', 'bg-white', 'text-[#28211c]');
                        });
                        btn.classList.remove('border-[#dfd2c0]', 'bg-white', 'text-[#28211c]');
                        btn.classList.add('border-[#52311a]', 'bg-[#52311a]', 'text-white', 'shadow-xs');

                        scheduledDateInput.value = btn.dataset.date;
                        const parsed = new Date(btn.dataset.date + 'T00:00:00');
                        selectedDateLabel.textContent = 'Tanggal dipilih: ' + parsed.toLocaleDateString('id-ID', {
                            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
                        });
                        selectedQuotaLabel.textContent = 'Terpakai: ' + btn.dataset.used + ' · Sisa: ' + btn.dataset.remaining + ' pesanan';
                    });
                });

                // Peta Interaktif Leaflet.js + OSRM Route Calculation
                const mapEl = document.getElementById('checkout-map');
                if (mapEl && typeof L !== 'undefined') {
                    const startLat = parseFloat(latInput.value || -7.33185);
                    const startLng = parseFloat(lngInput.value || 112.72441);

                    const map = L.map('checkout-map').setView([startLat, startLng], 13);
                    window.kelanaMap = map;

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(map);

                    // Marker Dapur Brownies Kelana
                    L.circleMarker([storeLat, storeLng], {
                        radius: 8, color: '#52311a', fillColor: '#b18950', fillOpacity: 0.9,
                    }).addTo(map).bindPopup('Dapur Brownies Kelana (Titik Asal)');

                    // Marker Tujuan Pengiriman (Draggable)
                    const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

                    const onCoordinateChanged = async (lat, lng) => {
                        const roundedLat = parseFloat(lat).toFixed(6);
                        const roundedLng = parseFloat(lng).toFixed(6);
                        latInput.value = roundedLat;
                        lngInput.value = roundedLng;
                        coordPreview.textContent = `Koordinat: ${roundedLat}, ${roundedLng}`;
                        mapsPreviewLink.href = `https://maps.google.com/?q=${roundedLat},${roundedLng}`;

                        let roadKm = null;
                        if (shippingMethod === 'distance') {
                            distanceBadge.textContent = 'Menghitung rute OSRM...';
                            try {
                                const osrmRes = await fetch(`https://router.project-osrm.org/route/v1/driving/${storeLng},${storeLat};${roundedLng},${roundedLat}?overview=false`);
                                const osrmJson = await osrmRes.json();
                                if (osrmJson.routes && osrmJson.routes.length > 0) {
                                    roadKm = (osrmJson.routes[0].distance / 1000).toFixed(2);
                                }
                            } catch (e) {
                                roadKm = null;
                            }
                        }

                        if (roadKm !== null) {
                            distInput.value = roadKm;
                            distanceBadge.textContent = `Estimasi jarak rute: ${roadKm} km`;
                            updateShippingSummary(roadKm);
                        } else {
                            distInput.value = '';
                            distanceBadge.textContent = shippingMethod === 'distance' ? 'Estimasi jarak otomatis' : 'Titik tersimpan';
                            updateShippingSummary(null);
                        }
                    };

                    marker.on('dragend', () => {
                        const pos = marker.getLatLng();
                        onCoordinateChanged(pos.lat, pos.lng);
                    });

                    map.on('click', (event) => {
                        marker.setLatLng(event.latlng);
                        onCoordinateChanged(event.latlng.lat, event.latlng.lng);
                    });

                    onCoordinateChanged(startLat, startLng);
                }
            });
        </script>
    @endpush
@endsection

