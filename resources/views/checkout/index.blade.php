@extends('layouts.app')

@section('title', 'Checkout Pesanan — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('cart.index') }}" aria-label="Kembali ke Keranjang"
                class="flex h-8 w-8 items-center justify-center rounded-full border border-[#8f6743]/50 bg-white text-[#593822] transition hover:bg-[#f7ebd5]">
                &larr;
            </a>
            <h1 class="font-serif text-2xl font-bold text-[#593822] sm:text-3xl">Checkout Pesanan</h1>
        </div>

        <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form" class="grid gap-6 lg:grid-cols-12">
            @csrf

            {{-- Kolom Kiri: Data Pemesan, Pengiriman/Pickup, Kalender H-1 & Alamat/Peta --}}
            <div class="space-y-6 lg:col-span-7">

                {{-- 1. Informasi Pemesan --}}
                <div class="kelana-card p-5 sm:p-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="kelana-label">Nama Pemesan *</label>
                            <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $user->name) }}" required class="kelana-input">
                        </div>
                        <div>
                            <label for="customer_email" class="kelana-label">Email *</label>
                            <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email', $user->email) }}" required class="kelana-input">
                        </div>
                        <div>
                            <label for="customer_phone" class="kelana-label">No HP/Telepon *</label>
                            <input id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone', $user->phone) }}" required class="kelana-input">
                        </div>
                        <div>
                            <label for="customer_secondary_phone" class="kelana-label">No HP/Telepon 2 (Opsional)</label>
                            <input id="customer_secondary_phone" name="customer_secondary_phone" type="text" value="{{ old('customer_secondary_phone', $user->secondary_phone) }}" placeholder="Belum diisi" class="kelana-input">
                        </div>
                    </div>
                    <p class="mt-3 text-[11px] font-medium text-[#b94a48]">
                        *Dashboard: pembaruan akun &middot; Email: kanal terpisah &middot; WhatsApp: komunikasi manual.
                    </p>
                </div>

                {{-- 2. Pilihan Metode Penerimaan + Informasi Penerima + Jadwal H-1 + Alamat/Pickup --}}
                <div class="kelana-card p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-6 border-b border-[#efe2ce] pb-4">
                        <label class="inline-flex cursor-pointer items-center gap-2.5">
                            <input type="radio" name="delivery_type" value="delivery" class="delivery-type-radio h-4 w-4 text-[#593822] focus:ring-[#a4804f]"
                                {{ old('delivery_type', 'delivery') === 'delivery' ? 'checked' : '' }}>
                            <span class="text-sm font-bold text-[#2f2118]">Kirim ke alamat</span>
                        </label>

                        <label class="inline-flex cursor-pointer items-center gap-2.5">
                            <input type="radio" name="delivery_type" value="pickup" class="delivery-type-radio h-4 w-4 text-[#593822] focus:ring-[#a4804f]"
                                {{ old('delivery_type') === 'pickup' ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-[#593822]">Ambil sendiri (pick-up)</span>
                        </label>
                    </div>

                    {{-- Informasi Penerima / Pengambil --}}
                    <div class="mt-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="font-serif text-base font-semibold text-[#2f2118]" id="recipient-heading">Informasi penerima</h2>
                            <button type="button" id="copy-customer-to-recipient" class="text-xs font-bold text-[#8f6743] hover:text-[#593822] hover:underline">
                                Samakan dengan data pemesan
                            </button>
                        </div>

                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="recipient_name" class="kelana-label">Nama Penerima *</label>
                                <input id="recipient_name" name="recipient_name" type="text" value="{{ old('recipient_name', $user->name) }}" required class="kelana-input">
                            </div>
                            <div>
                                <label for="recipient_phone" class="kelana-label">No HP/Telepon penerima *</label>
                                <input id="recipient_phone" name="recipient_phone" type="text" value="{{ old('recipient_phone', $user->phone) }}" required class="kelana-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="recipient_secondary_phone" class="kelana-label">No HP/Telepon kedua penerima (opsional)</label>
                                <input id="recipient_secondary_phone" name="recipient_secondary_phone" type="text" value="{{ old('recipient_secondary_phone', $user->secondary_phone) }}" placeholder="Belum diisi" class="kelana-input">
                            </div>
                        </div>
                    </div>

                    {{-- Jadwal Pengiriman / Pengambilan (Kalender Pre-Order H-1) --}}
                    <div class="mt-6 border-t border-[#efe2ce] pt-5">
                        <h2 class="font-serif text-base font-semibold text-[#2f2118]">Jadwal pengiriman</h2>
                        <div class="mt-2.5 flex items-start gap-2 rounded-xl bg-[#f7ebd5] px-4 py-3 text-xs text-[#593822]">
                            <span class="mt-0.5 shrink-0 font-bold">&#9432;</span>
                            <p>
                                Pre-order H-{{ $storeSetting->preorder_min_days }} &middot; Kuota maksimal <strong>{{ $storeSetting->daily_quota }} pesanan per hari</strong>.
                                {{ $storeSetting->operatingHoursSummary() }}.
                            </p>
                        </div>

                        <input type="hidden" name="scheduled_date" id="scheduled_date_input"
                            value="{{ old('scheduled_date', $defaultAvailableDay['date'] ?? '') }}" required>

                        <div class="mt-4 flex flex-wrap gap-3 text-[11px] text-[#5e4d3e]">
                            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-[#593822]"></span> Dipilih</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-[#8f6743] bg-white"></span> Tersedia</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-[#efe2ce]"></span> Tutup / Lewat</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-amber-200"></span> Libur</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-red-200"></span> Penuh</span>
                        </div>

                        <div class="mt-3 rounded-2xl border border-[#efe2ce] bg-[#fcf3e3]/50 p-3">
                            <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
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
                                            border-[#593822] bg-[#593822] text-white shadow-xs
                                        @elseif($day['available'])
                                            border-[#d9c8b4] bg-white text-[#2f2118] hover:border-[#593822] hover:bg-[#f7ebd5]
                                        @elseif($day['status'] === 'holiday')
                                            cursor-not-allowed border-amber-200 bg-amber-50 text-amber-800
                                        @elseif($day['status'] === 'full')
                                            cursor-not-allowed border-red-200 bg-red-50 text-red-700
                                        @else
                                            cursor-not-allowed border-[#e8decb] bg-[#efe2ce]/70 text-[#8f7968]
                                        @endif">
                                        <span class="text-[10px] font-semibold uppercase">{{ $day['day_short'] }}</span>
                                        <span class="text-sm font-bold">{{ $day['day_number'] }}</span>
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
                        </div>

                        <div class="mt-4 grid items-end gap-4 sm:grid-cols-2">
                            <div id="selected-date-banner" class="rounded-xl border border-[#efe2ce] bg-[#fcf3e3] px-4 py-2.5 text-xs">
                                <p class="text-[10px] font-semibold text-[#6f5a4a]">Tanggal dipilih</p>
                                <p class="mt-0.5 font-bold text-[#2f2118]" id="selected-date-label">
                                    @if($defaultAvailableDay)
                                        {{ \Carbon\Carbon::parse(old('scheduled_date', $defaultAvailableDay['date']))->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                                    @else
                                        Belum ada tanggal tersedia dipilih.
                                    @endif
                                </p>
                                <p class="mt-0.5 text-[11px] font-semibold text-emerald-700" id="selected-quota-label">
                                    @if($defaultAvailableDay)
                                        Terpakai: {{ $defaultAvailableDay['used_quota'] }} &middot; Sisa: {{ $defaultAvailableDay['remaining_quota'] }} pesanan
                                    @endif
                                </p>
                            </div>

                            <div>
                                <label for="scheduled_time_slot" class="kelana-label">Waktu pengiriman / pengambilan *</label>
                                <select id="scheduled_time_slot" name="scheduled_time_slot" required
                                    class="w-full rounded-xl border border-[#593822] bg-[#593822] px-4 py-2.5 text-xs font-bold text-white focus:outline-none">
                                    @foreach($storeSetting->delivery_slots ?? ['09.00-11.00 WIB', '13.00-15.00 WIB'] as $slot)
                                        <option value="{{ $slot }}" {{ old('scheduled_time_slot') === $slot ? 'selected' : '' }}>{{ $slot }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Panel Alamat Tujuan & Rincian Pengiriman (Tampil saat Delivery) --}}
                    <div id="delivery-section" class="mt-6 space-y-4 border-t border-[#efe2ce] pt-5 {{ old('delivery_type', 'delivery') === 'pickup' ? 'hidden' : '' }}">
                        <div>
                            <h2 class="font-serif text-base font-semibold text-[#2f2118]">Alamat pengiriman</h2>
                            <p class="mt-0.5 text-xs text-[#6f5a4a]">
                                @if($storeSetting->shipping_method === 'distance')
                                    Tarif jarak: Rp {{ number_format($storeSetting->distance_base_fee, 0, ',', '.') }} untuk {{ (float) $storeSetting->distance_base_km }} km pertama + Rp {{ number_format($storeSetting->distance_per_km_fee, 0, ',', '.') }}/km.
                                @elseif($storeSetting->shipping_method === 'zone')
                                    Pilih zona pengiriman sesuai kecamatan alamat tujuan.
                                @else
                                    Ongkir flat aktif: Rp {{ number_format($storeSetting->flat_shipping_fee, 0, ',', '.') }} untuk seluruh area layanan.
                                @endif
                            </p>
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

                        @if($userAddresses->isNotEmpty())
                            <div class="rounded-xl border border-[#efe2ce] bg-[#fcf3e3] p-3 text-xs">
                                <label for="saved_address_selector" class="kelana-label mb-1.5 font-bold text-[#593822]">Pilih dari Alamat Tersimpan:</label>
                                <select id="saved_address_selector" class="kelana-input text-xs py-2 bg-white">
                                    <option value="">-- Pilih Buku Alamat Anda --</option>
                                    @foreach($userAddresses as $addr)
                                        <option value="{{ $addr->id }}"
                                            data-address="{{ $addr->full_address }}"
                                            data-detail="{{ $addr->location_notes }}"
                                            data-maps-url="{{ $addr->maps_url }}"
                                            data-recipient="{{ $addr->recipient_name }}"
                                            data-phone="{{ $addr->recipient_phone }}"
                                            data-lat="{{ $addr->latitude }}"
                                            data-lng="{{ $addr->longitude }}">
                                            {{ $addr->label }} &middot; {{ $addr->recipient_name }} ({{ \Illuminate\Support\Str::limit($addr->full_address, 45) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label for="delivery_address" class="kelana-label">Alamat pengiriman lengkap *</label>
                            <textarea id="delivery_address" name="delivery_address" rows="3" required placeholder="Contoh: Jl. Diponegoro No. 45, RT 02 / RW 05, Kel. Darmo, Kec. Wonokromo, Surabaya" class="kelana-input">{{ old('delivery_address', $user->saved_address) }}</textarea>
                            <p class="mt-1 text-[11px] text-[#796b62]">Tuliskan alamat lengkap beserta nama jalan, nomor bangunan/rumah, RT/RW, dan kelurahan/kecamatan.</p>
                        </div>

                        <div>
                            <label for="delivery_address_detail" class="kelana-label">Detail patokan &amp; petunjuk kurir (opsional)</label>
                            <input id="delivery_address_detail" name="delivery_address_detail" type="text" value="{{ old('delivery_address_detail', $user->saved_address_detail) }}"
                                placeholder="Contoh: Pagar hitam samping warung kopi, titipkan satpam jika sedang keluar." class="kelana-input">
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-1">
                                <label for="maps_url" class="kelana-label">Link Google Maps (Opsional untuk detail titik lokasi)</label>
                                <a href="https://maps.google.com" target="_blank" rel="noopener" class="text-xs font-bold text-[#593822] hover:underline inline-flex items-center gap-1">
                                    <span>Buka Google Maps</span> &nearr;
                                </a>
                            </div>
                            <input id="maps_url" name="maps_url" type="url" value="{{ old('maps_url', $user->saved_maps_url) }}"
                                placeholder="Contoh: https://maps.app.goo.gl/... atau https://goo.gl/maps/..." class="kelana-input">
                            <p class="mt-1 text-[11px] text-[#796b62]">
                                Tempel link titik lokasi dari Google Maps untuk memudahkan kurir mengantar pesanan dengan lebih presisi.
                            </p>
                        </div>

                        <input type="hidden" name="delivery_latitude" id="delivery_latitude" value="{{ old('delivery_latitude', $user->saved_latitude) }}">
                        <input type="hidden" name="delivery_longitude" id="delivery_longitude" value="{{ old('delivery_longitude', $user->saved_longitude) }}">
                        <input type="hidden" name="delivery_distance_km" id="delivery_distance_km" value="{{ old('delivery_distance_km') }}">

                        {{-- Rincian Transparan Estimasi Ongkir --}}
                        <div id="shipping-breakdown-card" class="rounded-xl border border-[#efe2ce] bg-[#fcf3e3]/70 p-3.5 text-xs space-y-2">
                            <div class="flex items-center justify-between border-b border-[#efe2ce] pb-2">
                                <span class="font-bold text-[#593822] flex items-center gap-1.5">
                                    <span>🚚</span> <span id="breakdown-service-name">{{ $storeSetting->shipping_service_name ?? 'Kurir Kelana / GoSend' }}</span>
                                </span>
                                <span id="breakdown-source-badge" class="rounded-full bg-[#f4ece1] px-2.5 py-0.5 text-[10px] font-semibold text-[#593822]">
                                    Pengiriman Langsung
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-1 pt-1 text-[11px]">
                                <span class="text-[#796b62]">Estimasi Ongkos Kirim:</span>
                                <strong id="breakdown-fee" class="text-sm font-bold text-[#593822]">
                                    Rp {{ number_format($initialShipping['fee'], 0, ',', '.') }}
                                </strong>
                            </div>
                        </div>

                        <p id="out-of-range-warning" class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"></p>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="save_to_profile" name="save_to_profile" value="1" class="h-4 w-4 rounded text-[#593822] focus:ring-[#8f6743]" checked>
                            <label for="save_to_profile" class="text-xs text-[#593822]">Simpan alamat ini ke profil saya untuk pesanan berikutnya</label>
                        </div>
                    </div>

                    {{-- Panel Pilihan 3 Lokasi Pickup Resmi (Tampil saat Pickup) --}}
                    <div id="pickup-section" class="mt-6 space-y-3 border-t border-[#efe2ce] pt-5 {{ old('delivery_type', 'delivery') === 'pickup' ? '' : 'hidden' }}">
                        <h3 class="font-serif text-base font-semibold text-[#2f2118]">Pilih Lokasi Pengambilan (Pick-up Gratis Ongkir) *</h3>
                        <div class="grid gap-3">
                            @foreach($pickupLocations as $index => $location)
                                <label class="cursor-pointer">
                                    <input type="radio" name="pickup_location_id" value="{{ $location->id }}" class="peer sr-only"
                                        {{ old('pickup_location_id') == $location->id || (!old('pickup_location_id') && $index === 0) ? 'checked' : '' }}>
                                    <div class="flex flex-col justify-between gap-2 rounded-xl border-2 border-[#e8decb] bg-[#fcf3e3]/60 p-4 transition peer-checked:border-[#593822] peer-checked:bg-[#f7ebd5] sm:flex-row sm:items-center">
                                        <div>
                                            <p class="text-xs font-bold text-[#593822]">{{ $location->name }}</p>
                                            <p class="mt-0.5 text-[11px] text-[#5e4d3e]">{{ $location->address }}</p>
                                            @if($location->operating_info)
                                                <p class="mt-1 text-[10px] font-semibold text-emerald-700">{{ $location->operating_info }}</p>
                                            @endif
                                        </div>
                                        @if($location->maps_url)
                                            <a href="{{ $location->maps_url }}" target="_blank" rel="noopener" onclick="event.stopPropagation();"
                                                class="inline-flex shrink-0 items-center gap-1 text-[11px] font-bold text-[#593822] underline">
                                                <span>Google Maps</span>
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Ringkasan Pemesanan, Catatan & Pilih Metode Pembayaran 100% Midtrans --}}
            <div class="space-y-6 lg:col-span-5">
                {{-- Kartu 1: Ringkasan Pemesanan --}}
                <div class="kelana-card">
                    <div class="kelana-card-header">Ringkasan Pemesanan</div>
                    <div class="p-5">
                        <div class="space-y-3 border-b border-[#efe2ce] pb-4 text-xs">
                            @foreach($cart->items as $item)
                                <div class="flex justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-[#2f2118]">{{ $item->quantity }}&times; {{ $item->product->name }}</p>
                                        <p class="text-[11px] text-[#796b62]">{{ $item->variant?->name ?? 'Reguler' }}</p>
                                        @if($item->custom_note)
                                            <p class="text-[10px] text-[#8f6743]">Tulisan: "{{ $item->custom_note }}"</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 font-bold text-[#593822]">Rp {{ number_format($item->lineSubtotal(), 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 space-y-2 border-b border-[#efe2ce] pb-4 text-xs">
                            <div class="flex justify-between text-[#5e4d3e]">
                                <span>Total Harga</span>
                                <span class="font-bold text-[#2f2118]">Rp {{ number_format($cart->subtotalAmount(), 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-[#5e4d3e]">
                                <span id="summary-shipping-label">{{ $initialShipping['label'] }}</span>
                                <span class="font-bold text-[#2f2118]" id="summary-shipping-fee">Rp {{ number_format($initialShipping['fee'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-sm font-semibold text-[#2f2118]">Total Bayar</span>
                            <span id="summary-total-amount" class="text-xl font-bold text-[#593822]">
                                Rp {{ number_format($cart->subtotalAmount() + $initialShipping['fee'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Kartu 2: Catatan --}}
                <div class="kelana-card">
                    <div class="kelana-card-header">Catatan</div>
                    <div class="p-5">
                        <label for="customer_note" class="kelana-label">Catatan pesanan (opsional)</label>
                        <textarea id="customer_note" name="customer_note" rows="2"
                            placeholder="Tanpa kartu ucapan. Terima kasih."
                            class="kelana-input">{{ old('customer_note', $cart->order_note) }}</textarea>
                    </div>
                </div>

                {{-- Kartu 3: Metode Pembayaran Resmi QRIS Brownies Kelana --}}
                <div class="kelana-card">
                    <div class="kelana-card-header">Metode Pembayaran</div>
                    <div class="p-5 space-y-3">
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border-2 border-[#593822] bg-[#fcf3e3]/80 px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="qris" checked
                                    class="h-4 w-4 text-[#593822] focus:ring-[#a4804f]">
                                <img src="{{ asset('images/kelana/pay-qris.png') }}" alt="QRIS Brownies Kelana" class="h-6 w-12 shrink-0 object-contain">
                                <div>
                                    <p class="text-xs font-bold text-[#2f2118]">QRIS Brownies Kelana</p>
                                    <p class="text-[11px] text-[#6f5a4a]">Menerima semua bank (BCA, Mandiri, BRI, BNI, dll.) &amp; e-wallet (GoPay, OVO, DANA, ShopeePay)</p>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full bg-[#593822] px-2.5 py-0.5 text-[10px] font-bold text-white">Aktif</span>
                        </label>

                        <div class="rounded-xl border border-[#efe2ce] bg-[#fdfbf6] p-3 text-[11px] leading-relaxed text-[#5e4d3e]">
                            <p class="font-bold text-[#593822]">NMID: ID1025429266147 &middot; a.n. BROWNIES KELANA</p>
                            <p class="mt-1">
                                Setelah menekan tombol <strong>BAYAR</strong>, kode QRIS resmi akan tampil di halaman berikutnya. Scan QRIS, lampirkan bukti pembayaran (atau hubungi penjual via WhatsApp), lalu admin akan memverifikasi pesananmu.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Info Batas Bayar & Tombol Bayar Emas Karamel ala UI/UX --}}
                <div class="rounded-2xl border border-[#d9c8b4] bg-[#f7ebd5] px-4 py-3 text-xs text-[#593822]">
                    &#9432; Batas pembayaran <strong>{{ $storeSetting->payment_timeout_minutes }} menit</strong> setelah pesanan dibuat. Pesanan dibatalkan otomatis jika pembayaran melewati batas waktu.
                </div>

                <button type="submit" id="checkout-submit-btn"
                    class="w-full rounded-xl bg-[#a4804f] py-3.5 text-center text-sm font-bold text-white shadow-xs transition hover:bg-[#8d6c40] disabled:cursor-not-allowed disabled:opacity-50">
                    BAYAR &middot; <span id="submit-btn-total">Rp {{ number_format($cart->subtotalAmount() + $initialShipping['fee'], 0, ',', '.') }}</span>
                </button>
                <p class="text-center text-[10px] text-[#796b62]">
                    Dengan melanjutkan, kamu menyetujui jadwal dan rincian pesanan di atas. Pemesanan dilakukan melalui checkout website.
                </p>
            </div>
        </form>

        <div class="mt-10">
            @include('partials.customer-help-banner')
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const subtotal = {{ (int) $cart->subtotalAmount() }};
                const checkShippingUrl = @json(route('checkout.check-shipping'));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const deliverySection = document.getElementById('delivery-section');
                const pickupSection = document.getElementById('pickup-section');
                const shippingLabelEl = document.getElementById('summary-shipping-label');
                const shippingFeeEl = document.getElementById('summary-shipping-fee');
                const totalAmountEl = document.getElementById('summary-total-amount');
                const submitBtnTotalEl = document.getElementById('submit-btn-total');
                const submitBtn = document.getElementById('checkout-submit-btn');
                const outOfRangeWarn = document.getElementById('out-of-range-warning');
                const latInput = document.getElementById('delivery_latitude');
                const lngInput = document.getElementById('delivery_longitude');
                const distInput = document.getElementById('delivery_distance_km');
                const addressInput = document.getElementById('delivery_address');
                const addressDetailInput = document.getElementById('delivery_address_detail');
                const mapsUrlInput = document.getElementById('maps_url');

                const breakdownServiceName = document.getElementById('breakdown-service-name');
                const breakdownSourceBadge = document.getElementById('breakdown-source-badge');
                const breakdownFee = document.getElementById('breakdown-fee');

                const formatRp = (num) => 'Rp ' + parseInt(num || 0, 10).toLocaleString('id-ID');

                // 1. Fungsi Cek & Perhitungan Ongkir Otoritatif via Server
                const updateShippingSummary = async () => {
                    const deliveryType = document.querySelector('.delivery-type-radio:checked')?.value || 'delivery';
                    const zoneSelect = document.getElementById('shipping_zone_id');
                    const selectedPickupRadio = document.querySelector('input[name="pickup_location_id"]:checked');
                    const pickupLocationId = selectedPickupRadio ? selectedPickupRadio.value : null;

                    const payload = {
                        delivery_type: deliveryType,
                        pickup_location_id: pickupLocationId,
                        shipping_zone_id: zoneSelect ? zoneSelect.value : null,
                        maps_url: mapsUrlInput ? mapsUrlInput.value.trim() : null,
                        latitude: latInput ? latInput.value : null,
                        longitude: lngInput ? lngInput.value : null,
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

                        const shipping = data.shipping;

                        if (distInput) {
                            distInput.value = shipping.distance_km ?? '';
                        }

                        // Update rincian breakdown kartu
                        if (breakdownServiceName && shipping.service_name) {
                            breakdownServiceName.textContent = shipping.service_name;
                        }
                        if (breakdownSourceBadge) {
                            if (shipping.method === 'pickup') {
                                breakdownSourceBadge.textContent = 'Ambil Sendiri';
                            } else {
                                breakdownSourceBadge.textContent = 'Pengiriman Langsung';
                            }
                        }
                        if (breakdownFee) {
                            breakdownFee.textContent = formatRp(shipping.fee);
                        }

                        const formattedTotal = formatRp(subtotal + shipping.fee);
                        if (shippingLabelEl) shippingLabelEl.textContent = shipping.label;
                        if (shippingFeeEl) shippingFeeEl.textContent = formatRp(shipping.fee);
                        if (totalAmountEl) totalAmountEl.textContent = formattedTotal;
                        if (submitBtnTotalEl) {
                            submitBtnTotalEl.textContent = formattedTotal;
                        }

                        if (shipping.within_range) {
                            if (outOfRangeWarn) outOfRangeWarn.classList.add('hidden');
                            submitBtn.disabled = false;
                        } else {
                            if (outOfRangeWarn) {
                                outOfRangeWarn.textContent = shipping.label + '. Silakan pilih titik lokasi lebih dekat atau pilih Ambil Sendiri (Pick-up).';
                                outOfRangeWarn.classList.remove('hidden');
                            }
                            submitBtn.disabled = true;
                        }
                    } catch (err) {
                        console.error('Gagal memperbarui kalkulasi ongkir:', err);
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
                        }
                        updateShippingSummary();
                    });
                });

                document.querySelectorAll('input[name="pickup_location_id"]').forEach((radio) => {
                    radio.addEventListener('change', () => {
                        updateShippingSummary();
                    });
                });

                document.getElementById('shipping_zone_id')?.addEventListener('change', () => updateShippingSummary());

                // Update shipping summary jika link gmaps diubah
                mapsUrlInput?.addEventListener('change', () => updateShippingSummary());

                // Salin data pemesan ke penerima
                document.getElementById('copy-customer-to-recipient')?.addEventListener('click', () => {
                    document.getElementById('recipient_name').value = document.getElementById('customer_name').value;
                    document.getElementById('recipient_phone').value = document.getElementById('customer_phone').value;
                });

                // Pilih dari Buku Alamat Tersimpan
                const savedAddressSelector = document.getElementById('saved_address_selector');
                if (savedAddressSelector) {
                    savedAddressSelector.addEventListener('change', () => {
                        const opt = savedAddressSelector.options[savedAddressSelector.selectedIndex];
                        if (opt && opt.value) {
                            if (addressInput) addressInput.value = opt.dataset.address || '';
                            if (addressDetailInput && opt.dataset.detail) addressDetailInput.value = opt.dataset.detail;
                            if (mapsUrlInput && opt.dataset.mapsUrl) mapsUrlInput.value = opt.dataset.mapsUrl;
                            if (opt.dataset.recipient) document.getElementById('recipient_name').value = opt.dataset.recipient;
                            if (opt.dataset.phone) document.getElementById('recipient_phone').value = opt.dataset.phone;

                            if (latInput) latInput.value = opt.dataset.lat || '';
                            if (lngInput) lngInput.value = opt.dataset.lng || '';

                            updateShippingSummary();
                        }
                    });
                }

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
                            other.classList.remove('border-[#593822]', 'bg-[#593822]', 'text-white', 'shadow-xs');
                            other.classList.add('border-[#d9c8b4]', 'bg-white', 'text-[#2f2118]');
                        });
                        btn.classList.remove('border-[#d9c8b4]', 'bg-white', 'text-[#2f2118]');
                        btn.classList.add('border-[#593822]', 'bg-[#593822]', 'text-white', 'shadow-xs');

                        scheduledDateInput.value = btn.dataset.date;
                        const parsed = new Date(btn.dataset.date + 'T00:00:00');
                        selectedDateLabel.textContent = parsed.toLocaleDateString('id-ID', {
                            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
                        });
                        selectedQuotaLabel.textContent = 'Terpakai: ' + btn.dataset.used + ' · Sisa: ' + btn.dataset.remaining + ' pesanan';
                    });
                });
            });
        </script>
    @endpush
@endsection
