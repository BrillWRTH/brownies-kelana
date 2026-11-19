@extends('layouts.app')

@section('title', 'Tentang Kami — Brownies Kelana')

@section('content')
    {{-- 1. Hero Cerita Brand Brownies Kelana (Caramel Bronze Banner #9b7643) --}}
    <section class="relative overflow-hidden bg-[#9b7643] text-white">
        <div class="mx-auto grid max-w-7xl items-center gap-6 px-4 py-10 sm:gap-8 sm:px-6 md:grid-cols-12 lg:gap-12 lg:px-8 lg:py-16">
            <div class="md:col-span-6">
                <h1 class="text-2xl font-bold leading-tight tracking-tight text-white sm:text-3xl md:text-4xl lg:text-[40px]">
                    Selamat Datang di {{ $storeSetting->store_name }}
                </h1>
                <p class="mt-4 text-xs leading-relaxed text-[#fcf5eb]/95 sm:mt-5 sm:text-sm lg:text-[15px]">
                    {{ $storeSetting->about_story ?: 'Berdiri sejak tahun 2018, Brownies Kelana hadir untuk menemani setiap momen spesial Anda dengan aneka kue yang lezat dan berkualitas. Kami percaya bahwa setiap gigitan memiliki cerita dan setiap hidangan manis dapat menghadirkan kebahagiaan. Dengan beragam pilihan brownies dan kue yang menggugah selera, kami berusaha menghadirkan cita rasa istimewa untuk berbagai kesempatan, mulai dari camilan sehari-hari, hadiah untuk orang tersayang, hingga pelengkap perayaan spesial.' }}
                </p>
                {{-- Sesuai instruksi owner: Bagian Download E-Katalog tidak disertakan --}}
            </div>
            <div class="md:col-span-6 flex justify-center md:justify-end">
                <img src="{{ asset('images/kelana/hero-about-platter.png') }}" alt="Aneka Kue Brownies Kelana di atas Platter"
                    class="w-full max-w-[420px] sm:max-w-[500px] lg:max-w-[580px] object-contain drop-shadow-2xl">
            </div>
        </div>
    </section>

    {{-- 2. Bagian Fresh-Baked Pre-order & Whisk Cokelat Mentok Kiri Sesuai Figma --}}
    <section class="relative overflow-hidden bg-[#fcf8f2] py-8 sm:py-12 lg:py-16">
        <div class="mx-auto grid grid-cols-12 items-center gap-3 px-3 sm:gap-6 sm:px-6 md:gap-8 lg:gap-10 lg:px-8 max-w-7xl">
            <div class="col-span-5 sm:col-span-5 -ml-3 sm:-ml-8 md:-ml-14 lg:-ml-20 flex justify-start">
                <img src="{{ asset('images/kelana/whisk-glossy.png') }}" alt="Whisk Berlumur Cokelat Leleh"
                    class="w-full max-w-[260px] sm:max-w-[380px] md:max-w-[460px] lg:max-w-[520px] object-contain object-left scale-105 origin-top-left">
            </div>
            <div class="col-span-7 sm:col-span-7 pl-1 sm:pl-0">
                <h2 class="text-base font-bold leading-snug tracking-tight text-[#593822] sm:text-2xl md:text-3xl lg:text-4xl">
                    Ingin Menikmati Kelezatan<br>Brownies Kelana?
                </h2>
                <div class="mt-2.5 space-y-2 text-xs leading-relaxed text-[#593822] sm:mt-5 sm:space-y-4 sm:text-sm md:text-base">
                    <p>
                        Kami selalu menyajikan kue yang baru dipanggang. Pastikan Kamu memesan minimal <strong>1 hari sebelumnya</strong> agar kami dapat menyiapkannya dengan sempurna.
                    </p>
                    <p>
                        Untuk pengambilan mandiri di luar, Kamu bisa mengambil pesanan langsung di titik pengambilan resmi yang telah kami tentukan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Kolase 3 Foto "Temukan kebahagiaan di setiap Rasa" Bergelombang Sesuai Gambar 1 & asset 2.zip --}}
    <section class="relative overflow-hidden bg-[#fcf8f2] pt-4 pb-12 sm:pb-16 lg:pb-20">
        {{-- Latar belakang blok cokelat gelap (#53311a) di bagian bawah foto dengan pita gelombang --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-0 top-[42%] bg-[#53311a]">
            <img src="{{ asset('images/kelana/about-ngisoran.png') }}" alt=""
                class="absolute bottom-2 sm:bottom-4 left-0 w-full object-cover opacity-95">
        </div>

        <div class="relative z-10 mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
            <div class="grid grid-cols-12 items-end gap-2 sm:gap-5 lg:gap-8">
                {{-- Foto 1 (Kiri): culkle 1.png (Tangan mematahkan cookies/brownies) --}}
                <div class="col-span-4 self-center pb-4 sm:pb-8 lg:pb-12">
                    <div class="overflow-hidden shadow-xl sm:shadow-2xl">
                        <img src="{{ asset('images/kelana/about-cukle.png') }}" alt="Brookies Brownies Kelana"
                            class="h-44 w-full object-cover sm:h-72 md:h-84 lg:h-96">
                    </div>
                </div>

                {{-- Kolom Tengah: Judul "Temukan kebahagiaan di setiap Rasa" + Foto 2 senyum 1.png --}}
                <div class="col-span-4 flex flex-col items-center text-center">
                    <div class="mb-3 sm:mb-5 flex justify-center">
                        <img src="{{ asset('images/kelana/about-heading-rasa.png') }}" alt="Temukan kebahagiaan di setiap Rasa"
                            class="h-10 sm:h-16 md:h-20 lg:h-24 object-contain">
                    </div>
                    <div class="w-full overflow-hidden shadow-xl sm:shadow-2xl">
                        <img src="{{ asset('images/kelana/about-senyum.png') }}" alt="Kebahagiaan Bersama Brownies Kelana"
                            class="h-56 w-full object-cover sm:h-84 md:h-[400px] lg:h-[460px]">
                    </div>
                </div>

                {{-- Foto 3 (Kanan): tepunglop 1.png (Tangan mengolah adonan tepung) --}}
                <div class="col-span-4 self-end pt-6 sm:pt-12 md:pt-16 lg:pt-24">
                    <div class="overflow-hidden shadow-xl sm:shadow-2xl">
                        <img src="{{ asset('images/kelana/about-tepunglop.png') }}" alt="Proses Adonan Penuh Cinta Brownies Kelana"
                            class="h-40 w-full object-cover sm:h-64 md:h-72 lg:h-80">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Jam Operasional, Kontak WhatsApp & Peta Lokasi Toko + Titik Pickup --}}
    <section class="bg-[#fcf8f2] py-10 sm:py-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-12">
                {{-- Jam Operasional Dinamis dari Database --}}
                <div class="flex flex-col justify-between rounded-2xl border border-[#8f6743]/60 bg-[#fdfaf5] p-5 sm:p-6 lg:col-span-6 shadow-sm">
                    <div>
                        <h2 class="text-center text-lg font-bold text-[#2f2118]">Jam Operasional</h2>

                        <div class="mt-4 space-y-2">
                            @foreach($storeSetting->operating_hours ?? [] as $day)
                                <div class="grid grid-cols-3 items-center py-0.5 text-xs">
                                    <span class="font-bold text-[#2f2118]">{{ $day['label'] ?? '' }}</span>
                                    <div class="text-center">
                                        @if($day['is_open'] ?? false)
                                            <span class="inline-block min-w-[4rem] rounded-full bg-[#e8decb] px-2.5 py-0.5 text-[10px] font-bold text-[#593822]">Buka</span>
                                        @else
                                            <span class="inline-block min-w-[4rem] rounded-full bg-[#e8decb] px-2.5 py-0.5 text-[10px] font-bold text-[#796b62]">Tutup</span>
                                        @endif
                                    </div>
                                    <span class="text-right text-[11px] font-bold text-[#2f2118]">
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

                    <div class="mt-6 border-t border-[#8f6743]/40 pt-4">
                        <h3 class="text-base font-bold leading-snug text-[#2f2118]">Ada Pertanyaan atau Pemesanan?</h3>
                        <p class="mt-1 text-xs leading-relaxed text-[#5e4d3e]">
                            Untuk menanyakan ketersediaan produk, melakukan pemesanan, atau info lainnya langsung chat di WhatsApp!
                        </p>
                        <a href="{{ $storeSetting->whatsappUrl('Halo Brownies Kelana, saya ingin bertanya tentang pemesanan.') }}" target="_blank" rel="noopener"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-[#7d5a42] px-4 py-1.5 text-xs font-bold text-white transition hover:bg-[#593822]">
                            <span>Hubungi {{ $storeSetting->store_name }}</span>
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- Lokasi Dapur Utama & Titik Pickup Resmi --}}
                <div class="flex flex-col rounded-2xl border border-[#8f6743]/60 bg-[#fdfaf5] p-5 sm:p-6 lg:col-span-6 space-y-3 shadow-sm">
                    <div class="overflow-hidden rounded-xl border border-[#d9c8b4]">
                        <img src="{{ asset('images/kelana/map-sidoarjo.png') }}" alt="Peta Lokasi Sidoarjo Surabaya" class="h-36 w-full object-cover sm:h-40">
                    </div>

                    @if($storeSetting->google_maps_url)
                        <div class="flex justify-end">
                            <a href="{{ $storeSetting->google_maps_url }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-[#593822] hover:underline">
                                <span>Lihat di google maps</span>
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                </svg>
                            </a>
                        </div>
                    @endif

                    <div class="rounded-xl border border-[#8f6743] bg-white p-3">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 text-[#593822]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-bold text-[#2f2118]">{{ $storeSetting->store_name }}</p>
                                <p class="text-[11px] text-[#6f5a4a]">{{ $storeSetting->store_address }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5 pt-1">
                        <h3 class="text-[10px] font-bold tracking-wide text-[#8f6743] uppercase">Titik Pengambilan Resmi ({{ $pickupLocations->count() }})</h3>
                        @foreach($pickupLocations as $pickup)
                            <div class="flex items-start justify-between gap-2 rounded-xl border border-[#e3d5c3] bg-white px-3 py-2">
                                <div>
                                    <p class="text-xs font-bold text-[#2f2118]">{{ $pickup->name }}</p>
                                    <p class="text-[10px] text-[#796b62]">{{ $pickup->address }}</p>
                                    @if($pickup->operating_info)
                                        <p class="text-[9px] font-bold text-emerald-700">{{ $pickup->operating_info }}</p>
                                    @endif
                                </div>
                                @if($pickup->maps_url)
                                    <a href="{{ $pickup->maps_url }}" target="_blank" rel="noopener" class="inline-flex shrink-0 items-center gap-0.5 text-[10px] font-bold text-[#593822] hover:underline">
                                        <span>Maps</span>
                                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
