@extends('layouts.app')

@section('title', 'Tentang Kami — Brownies Kelana')

@section('content')
    {{-- 1. Hero Cerita Brand Brownies Kelana --}}
    <section class="relative overflow-hidden bg-[#916d47] text-white">
        <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-14 sm:px-6 md:grid-cols-12 lg:px-8 lg:py-16">
            <div class="md:col-span-7">
                <span class="inline-block rounded-full bg-[#52311a]/80 px-3 py-1 text-[10px] font-bold tracking-widest uppercase">Sejak 2018</span>
                <h1 class="mt-4 font-serif text-3xl font-bold leading-tight sm:text-4xl">
                    Selamat Datang di {{ $storeSetting->store_name }}
                </h1>
                <p class="mt-4 text-sm leading-relaxed text-[#fdfbf6]/95">
                    {{ $storeSetting->about_story ?: 'Berdiri sejak tahun 2018, Brownies Kelana hadir untuk menemani setiap momen spesial Anda dengan aneka kue yang lezat dan berkualitas. Kami percaya bahwa setiap gigitan memiliki cerita dan setiap hidangan manis dapat menghadirkan kebahagiaan.' }}
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="rounded-full bg-[#52311a] px-6 py-2.5 text-xs font-bold tracking-wide text-white uppercase shadow-md transition hover:bg-[#492d1a]">
                        Jelajahi Katalog Produk
                    </a>
                    <a href="{{ route('orders.guide') }}" class="rounded-full border border-white/70 bg-white/10 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-white/20">
                        Cara Pemesanan
                    </a>
                </div>
            </div>
            <div class="md:col-span-5">
                <img src="{{ asset('images/kelana/cocoa-wave-banner.png') }}" alt="Aneka Kue Brownies Kelana"
                    class="mx-auto w-full max-w-md rounded-2xl border-2 border-[#f9eddd]/40 object-cover shadow-xl">
            </div>
        </div>
    </section>

    {{-- 2. Bagian Sistem Pemesanan Fresh-Baked Pre-order H-1 & Whisk Cokelat --}}
    <section class="relative overflow-hidden py-16">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 md:grid-cols-12 lg:px-8">
            <div class="md:col-span-5">
                <img src="{{ asset('images/kelana/whisk-chocolate.png') }}" alt="Adonan Cokelat Segar"
                    class="mx-auto max-h-80 w-auto object-contain">
            </div>
            <div class="md:col-span-7">
                <span class="text-xs font-bold tracking-widest text-[#936b49] uppercase">Fresh From The Oven</span>
                <h2 class="mt-2 font-serif text-2xl font-bold text-[#52311a] sm:text-3xl">
                    Ingin Menikmati Kelezatan Brownies Kelana?
                </h2>
                <p class="mt-3 text-sm leading-relaxed text-[#5e524a]">
                    Kami selalu menyajikan kue yang baru dipanggang. Pastikan kamu memesan minimal <strong>H-{{ $storeSetting->preorder_min_days }} hari sebelumnya</strong> agar tim dapur kami dapat menyiapkannya dengan sempurna sesuai slot jadwal pilihanmu.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-[#5e524a]">
                    Tersedia opsi pengiriman langsung ke alamat maupun <strong>pengambilan mandiri (pick-up) gratis ongkir</strong> di titik lokasi resmi kami di Sidoarjo &amp; Surabaya.
                </p>
            </div>
        </div>
    </section>

    {{-- 3. Kolase Foto Cerita Rasa --}}
    <section class="bg-[#52311a] py-14 text-white">
        <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 sm:px-6 md:grid-cols-12 lg:px-8">
            <div class="md:col-span-5">
                <h2 class="font-serif text-3xl leading-snug">
                    Temukan <span class="italic text-[#e9dcc7]">kebahagiaan</span><br>
                    di setiap <span class="font-bold">Rasa</span>
                </h2>
                <p class="mt-3 text-xs leading-relaxed text-[#e9dcc7]">
                    Dibuat dengan bahan berkualitas dan cokelat pilihan untuk menghadirkan cita rasa klasik yang selalu dirindukan.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-4 md:col-span-7">
                <img src="{{ asset('images/kelana/about-hands.png') }}" alt="Brookies Kelana" class="h-48 w-full rounded-2xl object-cover shadow-md sm:h-56">
                <img src="{{ asset('images/kelana/about-smile.png') }}" alt="Menikmati Brownies Kelana" class="h-48 w-full rounded-2xl object-cover shadow-md sm:h-56">
            </div>
        </div>
    </section>

    {{-- 4. Jam Operasional, Kontak WhatsApp & Peta Lokasi Toko + Titik Pickup --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-12">
            {{-- Jam Operasional Dinamis dari Database --}}
            <div class="flex flex-col justify-between rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs lg:col-span-6">
                <div>
                    <h2 class="text-xl font-bold text-[#52311a]">Jam Operasional</h2>
                    <p class="mt-1 text-xs text-[#796b62]">Jadwal produksi, pengiriman, dan layanan CS Brownies Kelana.</p>

                    <div class="mt-5 divide-y divide-[#f2e4cd]">
                        @foreach($storeSetting->operating_hours ?? [] as $day)
                            <div class="flex items-center justify-between py-2.5 text-xs">
                                <span class="font-bold text-[#28211c]">{{ $day['label'] ?? '' }}</span>
                                <div class="flex items-center gap-3">
                                    @if($day['is_open'] ?? false)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-800">Buka</span>
                                        <span class="font-semibold text-[#52311a]">
                                            {{ str_replace(':', '.', $day['open'] ?? '09:00') }}–{{ str_replace(':', '.', $day['close'] ?? '16:00') }} WIB
                                        </span>
                                    @else
                                        <span class="rounded-full bg-[#f2e4cd] px-2.5 py-0.5 text-[10px] font-bold text-[#796b62]">Tutup</span>
                                        <span class="text-[#8d7d70]">&mdash;</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 rounded-2xl bg-[#f9eddd] p-5">
                    <h3 class="text-sm font-bold text-[#52311a]">Ada Pertanyaan atau Pemesanan?</h3>
                    <p class="mt-1 text-xs text-[#5e524a]">
                        Untuk konsultasi pesanan custom atau pertanyaan pengiriman, silakan chat WhatsApp CS kami.
                    </p>
                    <a href="{{ $storeSetting->whatsappUrl('Halo Brownies Kelana, saya ingin bertanya tentang pemesanan.') }}" target="_blank" rel="noopener"
                        class="btn-kelana-primary mt-3 text-xs">
                        Chat via WhatsApp &rarr;
                    </a>
                </div>
            </div>

            {{-- Lokasi Dapur Utama & 3 Titik Pickup Resmi --}}
            <div class="flex flex-col rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs lg:col-span-6">
                <h2 class="text-xl font-bold text-[#52311a]">Lokasi Dapur &amp; Titik Pickup</h2>
                <p class="mt-1 text-xs text-[#796b62]">Gratis ongkos kirim apabila kamu mengambil pesanan langsung di titik pickup berikut.</p>

                <div class="mt-4 overflow-hidden rounded-2xl border border-[#e3d5c3]">
                    <img src="{{ asset('images/kelana/map-sidoarjo.png') }}" alt="Peta Lokasi Sidoarjo Surabaya" class="h-44 w-full object-cover">
                    <div class="flex items-center justify-between gap-3 bg-[#fdfbf6] p-4">
                        <div>
                            <p class="text-xs font-bold text-[#52311a]">Dapur Utama &bull; {{ $storeSetting->store_name }}</p>
                            <p class="mt-0.5 text-[11px] text-[#5e524a]">{{ $storeSetting->store_address }}</p>
                        </div>
                        @if($storeSetting->google_maps_url)
                            <a href="{{ $storeSetting->google_maps_url }}" target="_blank" rel="noopener"
                                class="btn-kelana-outline shrink-0 px-3 py-1.5 text-[11px]">
                                Buka Maps &nearr;
                            </a>
                        @endif
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <h3 class="text-xs font-bold tracking-wider text-[#936b49] uppercase">Titik Pengambilan Resmi ({{ $pickupLocations->count() }})</h3>
                    @foreach($pickupLocations as $pickup)
                        <div class="flex items-start justify-between gap-3 rounded-xl border border-[#efe4d4] bg-[#fdfbf6] p-3.5">
                            <div>
                                <p class="text-xs font-bold text-[#28211c]">{{ $pickup->name }}</p>
                                <p class="mt-0.5 text-[11px] text-[#796b62]">{{ $pickup->address }}</p>
                                @if($pickup->operating_info)
                                    <p class="mt-1 text-[10px] font-semibold text-emerald-700">{{ $pickup->operating_info }}</p>
                                @endif
                            </div>
                            @if($pickup->maps_url)
                                <a href="{{ $pickup->maps_url }}" target="_blank" rel="noopener" class="shrink-0 text-[11px] font-bold text-[#52311a] hover:underline">
                                    Maps &nearr;
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection

