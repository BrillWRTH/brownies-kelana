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

                        {{-- Peta Interaktif Leaflet.js untuk Menentukan Titik Pin & Jarak Rute --}}
                        <div class="rounded-2xl border border-[#efe2ce] bg-[#fcf3e3]/70 p-4">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#593822] px-3 py-1.5 text-xs font-bold text-white">
                                    Pilih Lokasi Tujuan di Peta
                                </span>
                                <span id="distance-badge" class="rounded-full bg-[#e8decb] px-3 py-1 text-[11px] font-bold text-[#593822]">
                                    Menghitung titik...
                                </span>
                            </div>

                            <div id="checkout-map" class="h-64 w-full rounded-xl border border-[#d9c8b4]"></div>

                            <input type="hidden" name="delivery_latitude" id="delivery_latitude" value="{{ old('delivery_latitude', $user->saved_latitude ?? -7.3318500) }}">
                            <input type="hidden" name="delivery_longitude" id="delivery_longitude" value="{{ old('delivery_longitude', $user->saved_longitude ?? 112.7244100) }}">
                            <input type="hidden" name="delivery_distance_km" id="delivery_distance_km" value="{{ old('delivery_distance_km') }}">

                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-[#6f5a4a]">
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
                            <label for="delivery_address" class="kelana-label">Alamat tujuan *</label>
                            <textarea id="delivery_address" name="delivery_address" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan/kecamatan..." class="kelana-input">{{ old('delivery_address', $user->saved_address) }}</textarea>
                        </div>

                        <div>
                            <label for="delivery_address_detail" class="kelana-label">Detail alamat pengiriman</label>
                            <input id="delivery_address_detail" name="delivery_address_detail" type="text" value="{{ old('delivery_address_detail', $user->saved_address_detail) }}"
                                placeholder="Rumah nomor 18, pagar putih. Mohon hubungi penerima saat tiba." class="kelana-input">
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

                {{-- Kartu 3: Pilih Metode Pembayaran (100% Midtrans) --}}
                <div class="kelana-card">
                    <div class="kelana-card-header">Pilih Metode Pembayaran</div>
                    <div class="p-5">
                        <p class="mb-3 text-xs font-bold text-[#593822]">Midtrans Snap &amp; Gateway Resmi</p>

                        @php
                            $midtransChannels = [
                                ['midtrans_snap', 'Midtrans Snap', 'Semua metode instan dalam popup Snap', null],
                                ['qris', 'QRIS', 'Scan kode QR dari semua e-wallet & bank', 'images/kelana/pay-qris.png'],
                                ['credit_card', 'Kartu kredit/debit', 'Visa, Mastercard, JCB & Amex', 'images/kelana/pay-cards.png'],
                                ['mandiri_va', 'VA Mandiri', 'Mandiri Bill Payment & Livin’', null],
                                ['bca_va', 'VA BCA', 'Transfer cepat BCA Mobile & ATM', 'images/kelana/pay-bca.png'],
                                ['gopay', 'GoPay', 'Konfirmasi otomatis aplikasi GoPay', 'images/kelana/pay-gopay.png'],
                            ];
                        @endphp

                        <div class="space-y-2.5">
                            @foreach($midtransChannels as [$key, $title, $subtitle, $iconPath])
                                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[#efe2ce] bg-white px-3.5 py-2.5 transition hover:border-[#8f6743]">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="payment_method" value="{{ $key }}"
                                            class="h-4 w-4 text-[#593822] focus:ring-[#a4804f]"
                                            {{ old('payment_method', 'midtrans_snap') === $key ? 'checked' : '' }}>
                                        @if($iconPath)
                                            <img src="{{ asset($iconPath) }}" alt="{{ $title }}" class="h-5 w-10 shrink-0 object-contain">
                                        @else
                                            <span class="flex h-5 w-10 shrink-0 items-center justify-center rounded bg-[#f7ebd5] text-[9px] font-bold text-[#593822]">MID</span>
                                        @endif
                                        <div>
                                            <p class="text-xs font-bold text-[#2f2118]">{{ $title }}</p>
                                            <p class="text-[10px] text-[#796b62]">{{ $subtitle }}</p>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
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
                const mapsPreviewLink = document.getElementById('google-maps-preview-link');
                const coordPreview = document.getElementById('coord-preview');

                const formatRp = (num) => 'Rp ' + parseInt(num || 0, 10).toLocaleString('id-ID');

                const updateShippingSummary = async () => {
                    const deliveryType = document.querySelector('.delivery-type-radio:checked')?.value || 'delivery';
                    const zoneSelect = document.getElementById('shipping_zone_id');
                    const payload = {
                        delivery_type: deliveryType,
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

                        if (distInput) {
                            distInput.value = data.shipping.distance_km ?? '';
                        }
                        if (distanceBadge) {
                            distanceBadge.textContent = data.shipping.distance_km
                                ? `Jarak estimasi server: ${data.shipping.distance_km} km`
                                : (shippingMethod === 'distance' ? 'Estimasi jarak otomatis' : 'Titik lokasi tersimpan');
                        }

                        const formattedTotal = formatRp(subtotal + data.shipping.fee);
                        shippingLabelEl.textContent = data.shipping.label;
                        shippingFeeEl.textContent = formatRp(data.shipping.fee);
                        totalAmountEl.textContent = formattedTotal;
                        if (submitBtnTotalEl) {
                            submitBtnTotalEl.textContent = formattedTotal;
                        }

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
                        radius: 8, color: '#593822', fillColor: '#a4804f', fillOpacity: 0.9,
                    }).addTo(map).bindPopup('Dapur Brownies Kelana (Titik Asal)');

                    // Marker Tujuan Pengiriman (Draggable)
                    const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

                    const onCoordinateChanged = (lat, lng) => {
                        const roundedLat = parseFloat(lat).toFixed(6);
                        const roundedLng = parseFloat(lng).toFixed(6);
                        latInput.value = roundedLat;
                        lngInput.value = roundedLng;
                        coordPreview.textContent = `Koordinat: ${roundedLat}, ${roundedLng}`;
                        mapsPreviewLink.href = `https://maps.google.com/?q=${roundedLat},${roundedLng}`;
                        updateShippingSummary();
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
