@extends('layouts.app')

@section('title', 'Tentang Kami — Brownies Kelana')

@section('content')
    {{-- 1. Hero Cerita Brand Brownies Kelana (Gold Caramel Banner) --}}
    <section class="relative overflow-hidden bg-[#a4804f] text-white">
        <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-12 sm:px-6 md:grid-cols-12 lg:px-8 lg:py-16">
            <div class="md:col-span-6">
                <span class="inline-block rounded-full bg-[#593822]/85 px-3 py-1 text-[10px] font-bold tracking-wide uppercase">Sejak 2018</span>
                <h1 class="mt-4 font-serif text-3xl font-bold leading-tight sm:text-4xl">
                    Selamat Datang di {{ $storeSetting->store_name }}
                </h1>
                <p class="mt-4 text-sm leading-relaxed text-[#fcf3e3]/95">
                    {{ $storeSetting->about_story ?: 'Berdiri sejak tahun 2018, Brownies Kelana hadir untuk menemani setiap momen spesial Anda dengan aneka kue yang lezat dan berkualitas. Kami percaya bahwa setiap gigitan memiliki cerita dan setiap hidangan manis dapat menghadirkan kebahagiaan.' }}
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}"
                        class="rounded-md bg-[#492d1a] px-6 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#2f2118]">
                        Jelajahi Katalog Produk
                    </a>
                    <a href="{{ route('orders.guide') }}"
                        class="rounded-md border border-white/75 bg-white/10 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-white/20">
                        Cara Pemesanan
                    </a>
                </div>
            </div>
            <div class="md:col-span-6">
                <div class="overflow-hidden rounded-2xl border-2 border-[#fcf3e3]/35 bg-[#593822]/25 p-2 shadow-xl">
                    <img src="{{ asset('images/kelana/hero-brownies.png') }}" alt="Aneka Kue Brownies Kelana"
                        class="h-64 w-full rounded-xl object-cover sm:h-72">
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Bagian Sistem Pemesanan Fresh-Baked Pre-order H-1 & Whisk Cokelat --}}
    <section class="relative overflow-hidden py-14 sm:py-16">
        <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 sm:px-6 md:grid-cols-12 lg:px-8">
            <div class="md:col-span-5">
                <img src="{{ asset('images/kelana/whisk-chocolate.png') }}" alt="Adonan Cokelat Segar"
                    class="-ml-4 max-h-80 w-auto object-contain brightness-[1.06] contrast-[1.05] mix-blend-multiply sm:-ml-8">
            </div>
            <div class="md:col-span-7">
                <h2 class="font-serif text-2xl font-bold text-[#593822] sm:text-3xl">
                    Ingin Menikmati Kelezatan Brownies Kelana?
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-[#593822]">
                    Kami selalu menyajikan kue yang baru dipanggang. Pastikan kamu memesan minimal <strong>H-{{ $storeSetting->preorder_min_days }} hari sebelumnya</strong> agar kami dapat menyiapkannya dengan sempurna.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-[#593822]">
                    Untuk pengambilan mandiri di luar, kamu bisa mengambil pesanan langsung di titik pengambilan resmi yang telah kami tentukan dengan gratis ongkos kirim.
                </p>
            </div>
        </div>
    </section>

    {{-- 3. Kolase 3 Foto "Temukan kebahagiaan di setiap Rasa" di atas Gelombang Cokelat --}}
    <section class="relative overflow-hidden pt-4">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-center font-serif text-3xl font-semibold leading-snug text-[#9f826a] sm:text-4xl">
                Temukan <span class="font-bold text-[#a4804f]">kebahagiaan</span><br>
                di setiap <span class="font-bold text-[#593822]">Rasa</span>
            </h2>
        </div>

        <div class="relative mt-8">
            {{-- Latar belakang pita cokelat gelap melengkung di bagian bawah foto --}}
            <div class="pointer-events-none absolute inset-x-0 bottom-0 top-24 bg-[#593822]"></div>

            <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-6 px-4 pb-14 sm:grid-cols-12 sm:px-6 lg:px-8">
                <div class="sm:col-span-4">
                    <img src="{{ asset('images/kelana/prod-brookies-hand.png') }}" alt="Brookies Brownies Kelana"
                        class="h-60 w-full rounded-xl object-cover shadow-lg">
                </div>
                <div class="sm:col-span-4">
                    <img src="{{ asset('images/kelana/about-smile.png') }}" alt="Kebahagiaan Bersama Brownies Kelana"
                        class="h-80 w-full rounded-xl object-cover shadow-xl">
                </div>
                <div class="sm:col-span-4 sm:self-end">
                    <img src="{{ asset('images/kelana/about-hands.png') }}" alt="Proses Pembuatan Adonan Kelana"
                        class="h-52 w-full rounded-xl object-cover shadow-lg">
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Jam Operasional, Kontak WhatsApp & Peta Lokasi Toko + Titik Pickup --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-12">
            {{-- Jam Operasional Dinamis dari Database --}}
            <div class="flex flex-col justify-between rounded-2xl border border-[#8f6743] bg-[#fdfaf5] p-6 sm:p-8 lg:col-span-6">
                <div>
                    <h2 class="text-center font-serif text-xl font-bold text-[#2f2118]">Jam Operasional</h2>

                    <div class="mt-6 space-y-2.5">
                        @foreach($storeSetting->operating_hours ?? [] as $day)
                            <div class="grid grid-cols-3 items-center py-1 text-xs">
                                <span class="font-medium text-[#2f2118]">{{ $day['label'] ?? '' }}</span>
                                <div class="text-center">
                                    @if($day['is_open'] ?? false)
                                        <span class="inline-block min-w-[4.5rem] rounded-full bg-[#e8decb] px-3 py-0.5 text-[11px] font-medium text-[#593822]">Buka</span>
                                    @else
                                        <span class="inline-block min-w-[4.5rem] rounded-full bg-[#e8decb] px-3 py-0.5 text-[11px] font-medium text-[#796b62]">Tutup</span>
                                    @endif
                                </div>
                                <span class="text-right font-medium text-[#2f2118]">
                                    @if($day['is_open'] ?? false)
                                        {{ str_replace(':', '.', $day['open'] ?? '09:00') }}-{{ str_replace(':', '.', $day['close'] ?? '16:00') }}
                                    @else
                                        &ndash;
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-8 border-t border-[#8f6743]/50 pt-6">
                    <h3 class="font-serif text-lg font-bold leading-snug text-[#2f2118]">Ada Pertanyaan atau<br>Pemesanan?</h3>
                    <p class="mt-2 text-xs leading-relaxed text-[#5e4d3e]">
                        Untuk menanyakan ketersediaan produk, melakukan pemesanan, atau info lainnya langsung chat di WhatsApp!
                    </p>
                    <a href="{{ $storeSetting->whatsappUrl('Halo Brownies Kelana, saya ingin bertanya tentang pemesanan.') }}" target="_blank" rel="noopener"
                        class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#7d5a42] px-5 py-2 text-xs font-bold text-white transition hover:bg-[#593822]">
                        <span>{{ $storeSetting->store_name }}</span>
                    </a>
                </div>
            </div>

            {{-- Lokasi Dapur Utama & 3 Titik Pickup Resmi --}}
            <div class="flex flex-col rounded-2xl border border-[#8f6743] bg-[#fdfaf5] p-6 sm:p-8 lg:col-span-6">
                <div class="overflow-hidden rounded-xl border border-[#d9c8b4]">
                    <img src="{{ asset('images/kelana/map-sidoarjo.png') }}" alt="Peta Lokasi Sidoarjo Surabaya" class="h-52 w-full object-cover">
                </div>

                @if($storeSetting->google_maps_url)
                    <div class="mt-2 flex justify-end">
                        <a href="{{ $storeSetting->google_maps_url }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 text-xs font-medium text-[#593822] hover:underline">
                            <span>Lihat di google maps</span>
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                            </svg>
                        </a>
                    </div>
                @endif

                <div class="mt-3 rounded-xl border border-[#8f6743] bg-white p-4">
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 text-[#593822]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                            </svg>
                        </span>
                        <div>
                            <p class="font-serif text-sm font-bold text-[#2f2118]">{{ $storeSetting->store_name }}</p>
                            <p class="mt-0.5 text-xs text-[#6f5a4a]">{{ $storeSetting->store_address }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 space-y-2.5">
                    <h3 class="text-[11px] font-bold tracking-wide text-[#8f6743] uppercase">Titik Pengambilan Resmi ({{ $pickupLocations->count() }})</h3>
                    @foreach($pickupLocations as $pickup)
                        <div class="flex items-start justify-between gap-3 rounded-xl border border-[#e3d5c3] bg-white px-3.5 py-2.5">
                            <div>
                                <p class="text-xs font-bold text-[#2f2118]">{{ $pickup->name }}</p>
                                <p class="mt-0.5 text-[11px] text-[#796b62]">{{ $pickup->address }}</p>
                                @if($pickup->operating_info)
                                    <p class="mt-0.5 text-[10px] font-semibold text-emerald-700">{{ $pickup->operating_info }}</p>
                                @endif
                            </div>
                            @if($pickup->maps_url)
                                <a href="{{ $pickup->maps_url }}" target="_blank" rel="noopener" class="inline-flex shrink-0 items-center gap-1 text-[11px] font-bold text-[#593822] hover:underline">
                                    <span>Maps</span>
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
