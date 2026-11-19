@extends('layouts.app')

@section('title', 'Checkout Pesanan — Brownies Kelana')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

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

                    {{-- Panel Alamat Tujuan & Peta / Zona Ongkir (Tampil saat Delivery) --}}
                    <div id="delivery-section" class="mt-6 space-y-4 border-t border-[#efe2ce] pt-5 {{ old('delivery_type', 'delivery') === 'pickup' ? 'hidden' : '' }}">
                        <div>
                            <h2 class="font-serif text-base font-semibold text-[#2f2118]">Alamat pengiriman</h2>
                            <p class="mt-0.5 text-xs text-[#6f5a4a]">
                                @if($storeSetting->shipping_method === 'distance')
                                    Tarif jarak: Rp {{ number_format($storeSetting->distance_base_fee, 0, ',', '.') }} untuk {{ (float) $storeSetting->distance_base_km }} km pertama + Rp {{ number_format($storeSetting->distance_per_km_fee, 0, ',', '.') }}/km (maks {{ (float) $storeSetting->distance_max_km }} km).
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

                        {{-- Peta Interaktif Leaflet.js dengan Pencarian Real & Sinkronisasi Alamat Dua Arah --}}
                        <div class="rounded-2xl border border-[#efe2ce] bg-[#fcf3e3]/70 p-4">
                            <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#593822] px-3 py-1.5 text-xs font-bold text-white">
                                    Pilih Titik Lokasi Antar di Peta
                                </span>
                                <span id="distance-badge" class="rounded-full bg-[#e8decb] px-3 py-1 text-[11px] font-bold text-[#593822]">
                                    Menghitung rute...
                                </span>
                            </div>

                            {{-- Tombol Gunakan Lokasi Saya (GPS) & Pencarian Alamat --}}
                            <div class="mb-3 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" id="btn-use-gps"
                                        class="inline-flex items-center gap-2 rounded-xl bg-[#593822] px-3.5 py-2 text-xs font-bold text-white transition hover:bg-[#432917] shadow-xs shrink-0">
                                        <svg id="gps-icon" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v3m0 14v3m10-10h-3M5 12H2m15 0a5 5 0 11-10 0 5 5 0 0110 0z"/>
                                        </svg>
                                        <span id="gps-spinner" class="hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                        <span id="gps-btn-text">Gunakan Lokasi Saya (GPS)</span>
                                    </button>
                                    <span class="text-[11px] text-[#796b62]">atau cari titik jalan/patokan di bawah</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" id="map-search-query"
                                            placeholder="Cari jalan, patokan, toko, apotek, atau tempel link/koordinat Google Maps..."
                                            value="{{ old('delivery_address', $user->saved_address) }}"
                                            class="kelana-input text-xs py-2 pr-8">
                                        <span id="map-search-spinner" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 animate-spin rounded-full border-2 border-[#593822] border-t-transparent"></span>
                                    </div>
                                    <button type="button" id="map-search-btn" class="btn-kelana-primary shrink-0 px-4 py-2 text-xs font-bold">
                                        Cari Titik
                                    </button>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-1 text-[11px] text-[#796b62]">
                                    <span>Tip: Anda juga dapat menggeser pin merah di peta ke titik rumah Anda secara manual</span>
                                    <a id="open-gmaps-link" href="https://maps.google.com" target="_blank" rel="noopener" class="font-bold text-[#593822] hover:underline inline-flex items-center gap-1">
                                        <span>Buka Google Maps</span> &nearr;
                                    </a>
                                </div>
                            </div>

                            <div id="map-search-message" class="hidden mb-2 rounded-xl p-2.5 text-xs"></div>
                            <div id="map-search-results" class="hidden mb-2 max-h-52 overflow-y-auto rounded-xl border border-[#d9c8b4] bg-white p-1 text-xs shadow-md space-y-1"></div>

                            <div id="checkout-map" class="h-64 w-full rounded-xl border border-[#d9c8b4]"></div>

                            <input type="hidden" name="delivery_latitude" id="delivery_latitude" value="{{ old('delivery_latitude', $user->saved_latitude ?? -7.3318500) }}">
                            <input type="hidden" name="delivery_longitude" id="delivery_longitude" value="{{ old('delivery_longitude', $user->saved_longitude ?? 112.7244100) }}">
                            <input type="hidden" name="delivery_distance_km" id="delivery_distance_km" value="{{ old('delivery_distance_km') }}">

                            {{-- Rincian Transparan Estimator Ongkos Kirim Rute Jalan --}}
                            <div id="shipping-breakdown-card" class="mt-3 rounded-xl border border-[#d9c8b4] bg-[#fdfbf6] p-3 text-xs space-y-2">
                                <div class="flex items-center justify-between border-b border-[#efe2ce] pb-2">
                                    <span class="font-bold text-[#593822] flex items-center gap-1.5">
                                        <span>🚚</span> <span id="breakdown-service-name">{{ $storeSetting->shipping_service_name ?? 'Kurir Kelana / GoSend' }}</span>
                                    </span>
                                    <span id="breakdown-source-badge" class="rounded-full bg-[#f4ece1] px-2.5 py-0.5 text-[10px] font-semibold text-[#593822]">
                                        Rute Jalan Nyata (ORS)
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-[11px] text-[#6f5a4a]">
                                    <div>Jarak rute: <strong id="breakdown-distance" class="text-[#2f2118]">{{ $initialShipping['distance_km'] ? number_format($initialShipping['distance_km'], 1, ',', '.') . ' km' : '- km' }}</strong></div>
                                    <div>Estimasi waktu: <strong id="breakdown-duration" class="text-[#2f2118]">{{ $initialShipping['duration_minutes'] ? '~' . $initialShipping['duration_minutes'] . ' menit' : '- menit' }}</strong></div>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-1 pt-1.5 border-t border-[#f1e7d9] text-[11px]">
                                    <span class="text-[#796b62]">Tarif ongkir otomatis:</span>
                                    <strong id="breakdown-fee" class="text-sm font-bold text-[#593822]">
                                        Rp {{ number_format($initialShipping['fee'], 0, ',', '.') }}
                                    </strong>
                                </div>
                            </div>

                            <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-[11px] text-[#6f5a4a]">
                                <span id="coord-preview">Koordinat terpilih siap dikirim ke kurir.</span>
                                <a id="google-maps-preview-link" href="https://maps.google.com/?q={{ old('delivery_latitude', $user->saved_latitude ?? -7.3318500) }},{{ old('delivery_longitude', $user->saved_longitude ?? 112.7244100) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-bold text-[#593822] hover:underline">
                                    <span>Lihat titik di Google Maps</span>
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                    </svg>
                                </a>
                            </div>

                            <p id="out-of-range-warning" class="mt-2 hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"></p>
                        </div>

                        <div>
                            <label for="delivery_address" class="kelana-label">Alamat tujuan lengkap *</label>
                            <textarea id="delivery_address" name="delivery_address" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan/kecamatan..." class="kelana-input">{{ old('delivery_address', $user->saved_address) }}</textarea>
                        </div>

                        <div>
                            <label for="delivery_address_detail" class="kelana-label">Detail patokan &amp; petunjuk kurir (opsional)</label>
                            <input id="delivery_address_detail" name="delivery_address_detail" type="text" value="{{ old('delivery_address_detail', $user->saved_address_detail) }}"
                                placeholder="Pagar hitam nomor 18, seberang masjid. Mohon hubungi penerima saat tiba." class="kelana-input">
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="save_to_profile" name="save_to_profile" value="1" class="h-4 w-4 rounded text-[#593822] focus:ring-[#8f6743]" checked>
                            <label for="save_to_profile" class="text-xs text-[#593822]">Simpan titik lokasi dan alamat ini sebagai alamat utama saya</label>
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
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const subtotal = {{ (int) $cart->subtotalAmount() }};
                const storeLat = {{ (float) $storeSetting->store_latitude }};
                const storeLng = {{ (float) $storeSetting->store_longitude }};
                const shippingMethod = @json($storeSetting->shipping_method);
                const checkShippingUrl = @json(route('checkout.check-shipping'));
                const geocodeUrl = @json(route('checkout.geocode'));
                const reverseGeocodeUrl = @json(route('checkout.reverse-geocode'));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const deliverySection = document.getElementById('delivery-section');
                const pickupSection = document.getElementById('pickup-section');
                const shippingLabelEl = document.getElementById('summary-shipping-label');
                const shippingFeeEl = document.getElementById('summary-shipping-fee');
                const totalAmountEl = document.getElementById('summary-total-amount');
                const submitBtnTotalEl = document.getElementById('submit-btn-total');
                const submitBtn = document.getElementById('checkout-submit-btn');
                const outOfRangeWarn = document.getElementById('out-of-range-warning');
                const distanceBadge = document.getElementById('distance-badge');

                const latInput = document.getElementById('delivery_latitude');
                const lngInput = document.getElementById('delivery_longitude');
                const distInput = document.getElementById('delivery_distance_km');
                const addressInput = document.getElementById('delivery_address');
                const addressDetailInput = document.getElementById('delivery_address_detail');
                const mapsPreviewLink = document.getElementById('google-maps-preview-link');
                const coordPreview = document.getElementById('coord-preview');

                const breakdownServiceName = document.getElementById('breakdown-service-name');
                const breakdownSourceBadge = document.getElementById('breakdown-source-badge');
                const breakdownDistance = document.getElementById('breakdown-distance');
                const breakdownDuration = document.getElementById('breakdown-duration');
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
                            } else if (shipping.source === 'openrouteservice') {
                                breakdownSourceBadge.textContent = 'Rute Nyata (ORS)';
                            } else {
                                breakdownSourceBadge.textContent = 'Rute Jalan Raya';
                            }
                        }
                        if (breakdownDistance) {
                            breakdownDistance.textContent = shipping.distance_km
                                ? `${shipping.distance_km} km`
                                : (shipping.method === 'pickup' ? '0 km (Pickup)' : '- km');
                        }
                        if (breakdownDuration) {
                            breakdownDuration.textContent = shipping.duration_minutes
                                ? `~${shipping.duration_minutes} menit`
                                : (shipping.method === 'pickup' ? 'Langsung di outlet' : '- menit');
                        }
                        if (breakdownFee) {
                            breakdownFee.textContent = formatRp(shipping.fee);
                        }

                        if (distanceBadge) {
                            if (shipping.method === 'pickup') {
                                distanceBadge.textContent = 'Ambil di outlet · Gratis Ongkir';
                            } else if (shipping.distance_km) {
                                distanceBadge.textContent = `Jarak rute: ${shipping.distance_km} km`;
                            } else {
                                distanceBadge.textContent = 'Titik lokasi tersimpan';
                            }
                        }

                        const formattedTotal = formatRp(subtotal + shipping.fee);
                        shippingLabelEl.textContent = shipping.label;
                        shippingFeeEl.textContent = formatRp(shipping.fee);
                        totalAmountEl.textContent = formattedTotal;
                        if (submitBtnTotalEl) {
                            submitBtnTotalEl.textContent = formattedTotal;
                        }

                        if (shipping.within_range) {
                            outOfRangeWarn.classList.add('hidden');
                            submitBtn.disabled = false;
                        } else {
                            outOfRangeWarn.textContent = shipping.label + '. Silakan pilih titik lokasi lebih dekat atau pilih Ambil Sendiri (Pick-up).';
                            outOfRangeWarn.classList.remove('hidden');
                            submitBtn.disabled = true;
                        }
                    } catch (err) {
                        console.error('Gagal memperbarui kalkulasi ongkir:', err);
                    }
                };

                // 2. Daftar Lokasi Pickup Resmi untuk Peta
                const pickupLocationsData = @json($pickupLocations);

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

                document.querySelectorAll('input[name="pickup_location_id"]').forEach((radio) => {
                    radio.addEventListener('change', () => {
                        updateShippingSummary();
                        const selectedLoc = pickupLocationsData.find(l => l.id == radio.value);
                        if (selectedLoc && window.kelanaMap && selectedLoc.latitude && selectedLoc.longitude) {
                            window.kelanaMap.panTo([selectedLoc.latitude, selectedLoc.longitude]);
                        }
                    });
                });

                document.getElementById('shipping_zone_id')?.addEventListener('change', () => updateShippingSummary());

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
                            if (opt.dataset.recipient) document.getElementById('recipient_name').value = opt.dataset.recipient;
                            if (opt.dataset.phone) document.getElementById('recipient_phone').value = opt.dataset.phone;

                            const lat = parseFloat(opt.dataset.lat);
                            const lng = parseFloat(opt.dataset.lng);
                            if (!isNaN(lat) && !isNaN(lng) && window.kelanaMap && window.kelanaMarker) {
                                window.kelanaMap.setView([lat, lng], 15);
                                window.kelanaMarker.setLatLng([lat, lng]);
                                onCoordinateChanged(lat, lng, false);
                            }
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

                // 3. Peta Interaktif Leaflet.js dengan OpenStreetMap
                const mapEl = document.getElementById('checkout-map');
                if (mapEl && typeof L !== 'undefined') {
                    const startLat = parseFloat(latInput.value || -7.33185);
                    const startLng = parseFloat(lngInput.value || 112.72441);

                    const map = L.map('checkout-map').setView([startLat, startLng], 14);
                    window.kelanaMap = map;

                    // Tile OpenStreetMap Resmi
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
                    }).addTo(map);

                    // Marker Dapur Utama Brownies Kelana (Grand Royal Regency)
                    const storeIcon = L.divIcon({
                        className: 'kelana-store-icon',
                        html: '<div style="background:#593822;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:12px;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.35);">🎂</div>',
                        iconSize: [26, 26],
                        iconAnchor: [13, 13],
                    });

                    L.marker([storeLat, storeLng], { icon: storeIcon }).addTo(map)
                        .bindPopup('<b>Dapur Utama Brownies Kelana</b><br>Grand Royal Regency, Taman, Sidoarjo (Titik Asal Kurir)');

                    // Marker Titik Pickup Tambahan
                    pickupLocationsData.forEach(loc => {
                        if (loc.latitude && loc.longitude && (Math.abs(loc.latitude - storeLat) > 0.001 || Math.abs(loc.longitude - storeLng) > 0.001)) {
                            const pickupIcon = L.divIcon({
                                className: 'kelana-pickup-icon',
                                html: '<div style="background:#a4804f;color:#fff;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:11px;border:2px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,0.3);">🏪</div>',
                                iconSize: [22, 22],
                                iconAnchor: [11, 11],
                            });
                            L.marker([loc.latitude, loc.longitude], { icon: pickupIcon }).addTo(map)
                                .bindPopup(`<b>Pickup: ${loc.name}</b><br>${loc.address}`);
                        }
                    });

                    // Marker Tujuan Pengiriman Pelanggan (Draggable Pin)
                    const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
                    window.kelanaMarker = marker;

                    let reverseGeoTimeout = null;
                    const triggerReverseGeocode = (lat, lng) => {
                        clearTimeout(reverseGeoTimeout);
                        reverseGeoTimeout = setTimeout(async () => {
                            try {
                                const res = await fetch(reverseGeocodeUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                    body: JSON.stringify({ latitude: lat, longitude: lng }),
                                });
                                if (res.ok) {
                                    const json = await res.json();
                                    const data = json.data;
                                    if (data && data.display_name) {
                                        if (addressInput && (!addressInput.value || addressInput.value.includes('Titik Koordinat') || addressInput.dataset.autoFilled === '1')) {
                                            addressInput.value = data.display_name;
                                            addressInput.dataset.autoFilled = '1';
                                        }
                                        const searchInput = document.getElementById('map-search-query');
                                        if (searchInput && (!searchInput.value || searchInput.dataset.autoFilled === '1')) {
                                            searchInput.value = data.display_name;
                                            searchInput.dataset.autoFilled = '1';
                                        }
                                    }
                                }
                            } catch (e) {
                                console.warn('Reverse geocode error:', e);
                            }
                        }, 350);
                    };

                    const onCoordinateChanged = (lat, lng, doReverse = true) => {
                        const roundedLat = parseFloat(lat).toFixed(6);
                        const roundedLng = parseFloat(lng).toFixed(6);
                        latInput.value = roundedLat;
                        lngInput.value = roundedLng;
                        coordPreview.textContent = `Koordinat: ${roundedLat}, ${roundedLng}`;
                        mapsPreviewLink.href = `https://maps.google.com/?q=${roundedLat},${roundedLng}`;
                        updateShippingSummary();
                        if (doReverse) {
                            triggerReverseGeocode(roundedLat, roundedLng);
                        }
                    };

                    marker.on('dragend', () => {
                        const pos = marker.getLatLng();
                        onCoordinateChanged(pos.lat, pos.lng, true);
                    });

                    map.on('click', (event) => {
                        marker.setLatLng(event.latlng);
                        onCoordinateChanged(event.latlng.lat, event.latlng.lng, true);
                    });

                    // 4. Tombol "Gunakan Lokasi Saya" (GPS Browser Geolocation API)
                    const btnUseGps = document.getElementById('btn-use-gps');
                    const gpsIcon = document.getElementById('gps-icon');
                    const gpsSpinner = document.getElementById('gps-spinner');
                    const gpsBtnText = document.getElementById('gps-btn-text');

                    const setGpsLoading = (isLoading) => {
                        if (!btnUseGps) return;
                        btnUseGps.disabled = isLoading;
                        if (isLoading) {
                            gpsIcon?.classList.add('hidden');
                            gpsSpinner?.classList.remove('hidden');
                            if (gpsBtnText) gpsBtnText.textContent = 'Mendeteksi GPS...';
                        } else {
                            gpsIcon?.classList.remove('hidden');
                            gpsSpinner?.classList.add('hidden');
                            if (gpsBtnText) gpsBtnText.textContent = 'Gunakan Lokasi Saya (GPS)';
                        }
                    };

                    btnUseGps?.addEventListener('click', () => {
                        if (!navigator.geolocation) {
                            showSearchMessage('Perangkat/browser Anda tidak mendukung fitur deteksi lokasi GPS. Silakan ketik nama jalan atau geser pin di peta.', 'warning');
                            return;
                        }

                        setGpsLoading(true);
                        hideSearchMessage();

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                setGpsLoading(false);
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                map.setView([lat, lng], 16);
                                marker.setLatLng([lat, lng]);
                                onCoordinateChanged(lat, lng, true);
                                showSearchMessage('✓ Lokasi Anda berhasil dideteksi via GPS. Silakan lengkapi nomor rumah atau detail patokan alamat.', 'success');
                            },
                            (error) => {
                                setGpsLoading(false);
                                let errorMsg = 'Gagal mendeteksi lokasi GPS.';
                                switch (error.code) {
                                    case error.PERMISSION_DENIED:
                                        errorMsg = 'Izin akses lokasi (GPS) ditolak browser. Anda dapat mengizinkannya di setelan browser, atau cukup cari nama jalan / geser pin di peta.';
                                        break;
                                    case error.POSITION_UNAVAILABLE:
                                        errorMsg = 'Sinyal GPS / informasi lokasi tidak tersedia saat ini. Silakan cari nama jalan atau geser pin di peta.';
                                        break;
                                    case error.TIMEOUT:
                                        errorMsg = 'Waktu permintaan lokasi GPS habis. Silakan coba lagi atau gunakan kolom pencarian.';
                                        break;
                                }
                                showSearchMessage(errorMsg, 'warning');
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0,
                            }
                        );
                    });

                    // 5. Integrasi Pencarian Titik Peta Cerdas (Server Proxy OpenRouteService + Google Maps Coords)
                    const searchInput = document.getElementById('map-search-query');
                    const searchBtn = document.getElementById('map-search-btn');
                    const searchSpinner = document.getElementById('map-search-spinner');
                    const searchMessage = document.getElementById('map-search-message');
                    const resultsBox = document.getElementById('map-search-results');
                    const openGmapsLink = document.getElementById('open-gmaps-link');

                    if (openGmapsLink) {
                        openGmapsLink.href = `https://www.google.com/maps/@${startLat},${startLng},16z`;
                    }

                    const showSearchMessage = (text, type = 'info') => {
                        if (!searchMessage) return;
                        searchMessage.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'border-red-200', 'bg-emerald-50', 'text-emerald-800', 'border-emerald-200', 'bg-amber-50', 'text-amber-800', 'border-amber-200');
                        if (type === 'error') {
                            searchMessage.classList.add('bg-red-50', 'text-red-700', 'border', 'border-red-200');
                        } else if (type === 'success') {
                            searchMessage.classList.add('bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200');
                        } else {
                            searchMessage.classList.add('bg-amber-50', 'text-amber-800', 'border', 'border-amber-200');
                        }
                        searchMessage.innerHTML = text;
                    };

                    const hideSearchMessage = () => {
                        if (searchMessage) searchMessage.classList.add('hidden');
                    };

                    const executeMapSearch = async () => {
                        const rawQuery = searchInput?.value.trim();
                        if (!rawQuery) return;

                        hideSearchMessage();
                        resultsBox.innerHTML = '';
                        resultsBox.classList.add('hidden');

                        if (searchBtn) searchBtn.disabled = true;
                        if (searchSpinner) searchSpinner.classList.remove('hidden');

                        try {
                            // Deteksi langsung koordinat numerik atau tautan Google Maps
                            const coordRegex = /(-?\d{1,2}\.\d+)[,\s/]+(\d{2,3}\.\d+)/;
                            const coordMatch = rawQuery.match(coordRegex);
                            if (coordMatch) {
                                const lat = parseFloat(coordMatch[1]);
                                const lng = parseFloat(coordMatch[2]);
                                if (lat >= -11 && lat <= 6 && lng >= 95 && lng <= 141) {
                                    map.setView([lat, lng], 16);
                                    marker.setLatLng([lat, lng]);
                                    onCoordinateChanged(lat, lng, true);
                                    showSearchMessage(`✓ Titik berhasil diset dari koordinat: <strong>${lat}, ${lng}</strong>`, 'success');
                                    return;
                                }
                            }

                            // Panggil server-side geocoding proxy
                            const res = await fetch(geocodeUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify({ query: rawQuery }),
                            });

                            const json = await res.json();
                            const places = json.results || [];

                            if (places.length > 0) {
                                resultsBox.innerHTML = '';
                                resultsBox.classList.remove('hidden');

                                places.forEach((place) => {
                                    const itemEl = document.createElement('div');
                                    itemEl.className = 'cursor-pointer rounded-lg p-2.5 transition hover:bg-[#f7ebd5] border-b border-[#f3ece2] last:border-0 flex items-start gap-2';
                                    itemEl.innerHTML = `
                                        <span class="text-[#593822] text-sm mt-0.5">📍</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-[#593822] text-xs truncate">${place.title}</p>
                                            <p class="text-[11px] text-[#796b62] truncate">${place.description}</p>
                                        </div>
                                    `;
                                    itemEl.addEventListener('click', () => {
                                        map.setView([place.lat, place.lng], 16);
                                        marker.setLatLng([place.lat, place.lng]);
                                        onCoordinateChanged(place.lat, place.lng, false);
                                        if (addressInput) addressInput.value = place.title + ', ' + place.description;
                                        if (searchInput) searchInput.value = place.title;
                                        resultsBox.classList.add('hidden');
                                        showSearchMessage(`✓ Titik dipindahkan ke: <strong>${place.title}</strong>`, 'success');
                                    });
                                    resultsBox.appendChild(itemEl);
                                });

                                // Geser otomatis ke hasil pertama
                                const top = places[0];
                                map.setView([top.lat, top.lng], 15);
                                marker.setLatLng([top.lat, top.lng]);
                                onCoordinateChanged(top.lat, top.lng, false);
                            } else {
                                showSearchMessage(`
                                    <p class="font-semibold text-amber-800">Nama tempat spesifik tidak ditemukan di database peta.</p>
                                    <p class="mt-1 text-[11px] text-amber-700">
                                        💡 <strong>Saran:</strong> Coba cari nama jalan utama atau kelurahan terdekat (misal: <em>Pulosari Surabaya</em>), atau geser pin merah langsung di atas peta ke lokasi Anda.
                                    </p>
                                `, 'warning');
                            }
                        } catch (err) {
                            console.error('Pencarian peta gagal:', err);
                            showSearchMessage('Terjadi gangguan jaringan saat mencari alamat. Anda tetap dapat menggeser pin peta secara manual.', 'error');
                        } finally {
                            if (searchBtn) searchBtn.disabled = false;
                            if (searchSpinner) searchSpinner.classList.add('hidden');
                        }
                    };

                    searchBtn?.addEventListener('click', executeMapSearch);
                    searchInput?.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            executeMapSearch();
                        }
                    });

                    onCoordinateChanged(startLat, startLng, false);
                }
            });
        </script>
    @endpush
@endsection
