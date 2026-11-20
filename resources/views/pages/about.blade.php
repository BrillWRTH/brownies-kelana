@extends('layouts.app')

@section('title', 'Tentang Kami — Brownies Kelana')

@section('content')
    {{-- 1. Hero Cerita Brand Brownies Kelana (Caramel Bronze Banner #9b7643 - Luas, Dalam & Tidak Ceper) --}}
    <section class="relative overflow-hidden bg-[#9b7643] text-white py-20 sm:py-28 lg:py-36 min-h-[520px] sm:min-h-[580px] lg:min-h-[660px] flex items-center">
        <div class="mx-auto grid max-w-7xl w-full items-center gap-8 px-4 sm:px-6 md:grid-cols-12 lg:gap-14 lg:px-8">
            <div class="md:col-span-5 lg:col-span-5">
                <h1 class="text-3xl font-bold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
                    Selamat Datang di {{ $storeSetting->store_name }}
                </h1>
                <p class="mt-6 text-sm leading-relaxed text-[#fcf5eb]/95 sm:text-base lg:text-lg max-w-xl">
                    {{ $storeSetting->about_story ?: 'Berdiri sejak tahun 2018, Brownies Kelana hadir untuk menemani setiap momen spesial Anda dengan aneka kue yang lezat dan berkualitas. Kami percaya bahwa setiap gigitan memiliki cerita dan setiap hidangan manis dapat menghadirkan kebahagiaan. Dengan beragam pilihan brownies dan kue yang menggugah selera, kami berusaha menghadirkan cita rasa istimewa untuk berbagai kesempatan, mulai dari camilan sehari-hari, hadiah untuk orang tersayang, hingga pelengkap perayaan spesial.' }}
                </p>
                <div class="mt-7 sm:mt-9">
                    <a href="{{ route('products.index') }}"
                        class="inline-flex items-center gap-2.5 rounded-lg bg-[#2b201a] px-5 py-2.5 sm:px-6 sm:py-3 text-xs sm:text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-[#1a130f] hover:shadow-lg hover:-translate-y-0.5">
                        <svg class="h-4 w-4 shrink-0 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Kunjungi Katalog Produk</span>
                    </a>
                </div>
            </div>
            <div class="md:col-span-7 lg:col-span-7 flex justify-center md:justify-end">
                <img src="{{ asset('images/kelana/hero-about-platter.png') }}" alt="Aneka Kue Brownies Kelana di atas Platter"
                    class="w-full max-w-[620px] lg:max-w-[760px] object-contain drop-shadow-2xl">
            </div>
        </div>
    </section>

    {{-- 2. Bagian Fresh-Baked Pre-order & Whisk Cokelat Mepet Dinding Kiri Sesuai Figma --}}
    <section class="relative overflow-hidden bg-[#f9eedd] py-14 sm:py-20 lg:py-24 min-h-[500px] lg:min-h-[600px] flex items-center">
        {{-- Whisk Cokelat Desktop: Mepet Dinding Kiri & Masuk dari Sudut Kiri Atas Persis Figma --}}
        <div class="pointer-events-none absolute left-0 top-0 hidden md:block">
            <img src="{{ asset('images/kelana/whisk-glossy.png') }}" alt="Whisk Berlumur Cokelat Leleh"
                class="h-[520px] lg:h-[590px] xl:h-[620px] w-auto object-contain object-left-top origin-top-left">
        </div>

        <div class="mx-auto max-w-7xl w-full px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-12 items-center gap-6">
                {{-- Mobile Whisk (mepet tepi kiri layar di layar kecil) --}}
                <div class="col-span-5 md:hidden -ml-4 flex justify-start">
                    <img src="{{ asset('images/kelana/whisk-glossy.png') }}" alt="Whisk Berlumur Cokelat Leleh"
                        class="w-full max-w-[240px] object-contain object-left-top origin-top-left">
                </div>

                {{-- Teks Konten Sesuai Figma: Berjarak Harmonis di Sebelah Kanan Drip Cokelat --}}
                <div class="col-span-7 md:col-start-6 md:col-span-7 lg:col-start-6 lg:col-span-6 md:pl-8 lg:pl-12">
                    <div class="max-w-[430px]">
                        <h2 class="text-xl sm:text-2xl md:text-3xl lg:text-[32px] font-bold leading-snug tracking-tight text-[#593822]">
                            Ingin Menikmati Kelezatan<br>Brownies Kelana?
                        </h2>
                        <div class="mt-5 sm:mt-6 space-y-4 text-xs sm:text-sm lg:text-[15px] leading-relaxed text-[#593822]">
                            <p>
                                Kami selalu menyajikan kue yang baru dipanggang. Pastikan Kamu memesan minimal 1 hari sebelumnya agar kami dapat menyiapkannya dengan sempurna.
                            </p>
                            <p>
                                Untuk pengambilan mandiri di luar, Kamu bisa mengambil pesanan langsung di titik pengambilan resmi yang telah kami tentukan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Kolase 3 Foto "Temukan kebahagiaan di setiap Rasa" - PERSIS FIGMA (TANGGA ATAS & BAWAH DENGAN PITA COKELAT HORIZONTAL) --}}
    <section class="relative overflow-hidden bg-[#f9eedd] pt-8 sm:pt-14 lg:pt-16 pb-20 sm:pb-28 lg:pb-36">
        {{-- Pita Cokelat Horizontal (#54311a) di Bagian Tengah sesuai Figma (Atas Cream, Tengah Pita Cokelat, Bawah Cream) --}}
        <div class="pointer-events-none absolute inset-x-0 top-[210px] sm:top-[250px] md:top-[280px] lg:top-[310px] h-[260px] sm:h-[320px] md:h-[360px] lg:h-[400px] bg-[#54311a]">
            <img src="{{ asset('images/kelana/about-ngisoran.png') }}" alt=""
                class="absolute bottom-0 left-0 w-full min-h-[140px] sm:min-h-[180px] lg:min-h-[240px] object-cover opacity-90">
        </div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{-- Grid 3 Kolom Bertangga Nyata di Atas & Bawah (Tangga Atas & Bawahnya Sesuai Gambar Referensi) --}}
            <div class="grid grid-cols-12 items-start gap-4 sm:gap-6 lg:gap-10">
                {{-- Foto 1 (Kiri): culkle 1.png - TANGGA 1 (Atas Tinggi, Bawah Berhenti di Paruh Atas Pita Cokelat) --}}
                <div class="col-span-4 self-start">
                    <div class="overflow-hidden shadow-2xl">
                        <img src="{{ asset('images/kelana/about-cukle.png') }}" alt="Brookies Brownies Kelana"
                            class="h-56 sm:h-76 md:h-88 lg:h-[400px] w-full object-cover">
                    </div>
                </div>

                {{-- Kolom Tengah: Judul Left-Aligned + Foto 2 senyum 1.png - TANGGA 2 (Tengah Atas & Bawah Mencapai Ujung Bawah Pita Cokelat) --}}
                <div class="col-span-4 flex flex-col items-start">
                    <div class="mb-4 sm:mb-6 w-full flex justify-start">
                        <img src="{{ asset('images/kelana/about-heading-rasa.png') }}" alt="Temukan kebahagiaan di setiap Rasa"
                            class="h-12 sm:h-16 md:h-20 lg:h-24 object-contain object-left">
                    </div>
                    <div class="w-full overflow-hidden shadow-2xl">
                        <img src="{{ asset('images/kelana/about-senyum.png') }}" alt="Kebahagiaan Bersama Brownies Kelana"
                            class="h-68 sm:h-88 md:h-[430px] lg:h-[490px] w-full object-cover">
                    </div>
                </div>

                {{-- Foto 3 (Kanan): tepunglop 1.png - TANGGA 3 (Atas Paling Turun, Bawah Paling Turun Menembus Pita Cokelat ke Area Cream Bawah) --}}
                <div class="col-span-4 self-start pt-32 sm:pt-52 md:pt-64 lg:pt-80 flex justify-end">
                    <div class="w-full max-w-[280px] sm:max-w-[320px] lg:max-w-[360px] overflow-hidden shadow-2xl">
                        <img src="{{ asset('images/kelana/about-tepunglop.png') }}" alt="Proses Adonan Penuh Cinta Brownies Kelana"
                            class="h-56 sm:h-76 md:h-88 lg:h-[420px] w-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Jam Operasional, Kontak WhatsApp & Peta Lokasi Toko + Titik Pickup --}}
    <section class="bg-[#f9eedd] py-14 sm:py-20">
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
