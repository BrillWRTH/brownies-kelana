@extends('layouts.app')

@section('title', 'Pengiriman dan Pengambilan — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs sm:p-10">
            <h1 class="font-serif text-3xl font-bold text-[#52311a]">Pengiriman dan Pengambilan</h1>
            <p class="mt-2 text-sm text-[#796b62]">Pilih metode penerimaan yang sesuai saat checkout. Periksa lokasi dan jadwal sebelum menyelesaikan pembayaran.</p>

            <div class="mt-8 grid gap-6 sm:grid-cols-2">
                <div class="rounded-2xl border border-[#efe4d4] bg-[#fdfbf6] p-5">
                    <h2 class="text-base font-bold text-[#52311a]">Pengiriman ke Alamat</h2>
                    <p class="mt-2 text-xs leading-relaxed text-[#5e524a]">
                        Lengkapi nama dan nomor telepon penerima, alamat tujuan, titik koordinat peta agar kurir mudah menemukan alamat, serta detail patokan rumah.
                    </p>
                    <p class="mt-2 text-xs text-[#796b62]">
                        Tarif aktif saat ini:
                        @if($storeSetting->shipping_method === 'flat')
                            <strong>Ongkir Flat Rp {{ number_format($storeSetting->flat_shipping_fee, 0, ',', '.') }}</strong>.
                        @elseif($storeSetting->shipping_method === 'zone')
                            <strong>Tarif Zona Wilayah</strong> (dihitung otomatis saat memilih zona di checkout).
                        @else
                            <strong>Tarif Jarak (Rp {{ number_format($storeSetting->distance_base_fee, 0, ',', '.') }} untuk {{ (float) $storeSetting->distance_base_km }} km pertama + Rp {{ number_format($storeSetting->distance_per_km_fee, 0, ',', '.') }}/km, maks {{ (float) $storeSetting->distance_max_km }} km)</strong>.
                        @endif
                    </p>
                </div>

                <div class="rounded-2xl border border-[#efe4d4] bg-[#fdfbf6] p-5">
                    <h2 class="text-base font-bold text-[#52311a]">Ambil Sendiri (Pick-up)</h2>
                    <p class="mt-2 text-xs leading-relaxed text-[#5e524a]">
                        Pengambilan langsung di titik resmi Brownies Kelana <strong>bebas biaya ongkir (Rp 0)</strong>. Tunjukkan nomor pesanan kepada petugas saat pengambilan sesuai jadwal.
                    </p>
                </div>
            </div>

            <div class="mt-8">
                <h2 class="text-base font-bold text-[#52311a]">Lokasi Pengambilan Resmi</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach($pickupLocations as $location)
                        <div class="flex flex-col justify-between rounded-2xl border border-[#e3d5c3] bg-[#f9eddd]/60 p-4">
                            <div>
                                <h3 class="text-sm font-bold text-[#52311a]">{{ $location->name }}</h3>
                                <p class="mt-1 text-xs text-[#5e524a]">{{ $location->address }}</p>
                            </div>
                            @if($location->maps_url)
                                <a href="{{ $location->maps_url }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-[#52311a] hover:underline">
                                    <span>Lihat di Google Maps</span>
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 rounded-2xl border border-[#efe4d4] bg-[#fdfbf6] p-5">
                <h2 class="text-base font-bold text-[#52311a]">Jadwal dan Kuota Harian</h2>
                <p class="mt-1 text-xs text-[#5e524a]">
                    Pre-order minimal H-{{ $storeSetting->preorder_min_days }} dengan kuota maksimal <strong>{{ $storeSetting->daily_quota }} pesanan/hari</strong>.
                    Jam operasional: {{ $storeSetting->operatingHoursSummary() }}.
                </p>
                @if($holidays->isNotEmpty())
                    <p class="mt-2 text-xs text-amber-800">
                        Hari libur terjadwal terdekat:
                        {{ $holidays->map(fn ($h) => $h->holiday_date->locale('id')->isoFormat('D MMM YYYY').' ('.$h->name.')')->implode(', ') }}.
                    </p>
                @endif
            </div>

            @include('partials.customer-help-banner')
        </div>
    </section>
@endsection

