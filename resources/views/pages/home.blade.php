@extends('layouts.app')

@section('title', 'Brownies Kelana — Dibuat Untuk Dinikmati')

@section('content')
    @php
        $store = $sharedStoreSetting ?? \App\Models\StoreSetting::current();
        $heroSlides = [
            [
                'image' => asset('images/kelana/hero-brownies.png'),
                'alt' => 'Brownies Panggang Signature Brownies Kelana',
                'badge' => $store->tagline,
                'title' => 'Hangatnya Brownies Panggang untuk Setiap Momen Spesial',
            ],
            [
                'image' => asset('images/kelana/prod-brookies-stack.png'),
                'alt' => 'Brookies Panggang Premium Kelana',
                'badge' => 'Fresh Baked H-1',
                'title' => 'Perpaduan Renyah Cookies & Fudgy Brownies Cokelat',
            ],
            [
                'image' => asset('images/kelana/prod-cheesecake.png'),
                'alt' => 'Basque Burnt Cheesecake Kelana',
                'badge' => 'Menu Favorit',
                'title' => 'Lembutnya Cream Cheese Premium dengan Karamel Eksotis',
            ],
        ];
    @endphp

    {{-- 1. Hero Banner Cokelat Tua dengan Carousel Brownies Panggang --}}
    <section class="relative bg-[#593822] pb-12 pt-6 text-white sm:pb-16 sm:pt-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-[1.5rem] bg-[#492d1a] shadow-lg">
                @foreach($heroSlides as $slideIndex => $slide)
                    <div data-hero-slide class="{{ $slideIndex === 0 ? '' : 'hidden' }} relative">
                        <img src="{{ $slide['image'] }}" alt="{{ $slide['alt'] }}"
                            class="h-64 w-full object-cover sm:h-96 md:h-[430px]">
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-[#28211c]/70 via-[#28211c]/20 to-transparent"></div>
                        <div class="absolute bottom-5 left-5 right-5 flex flex-col items-start justify-between gap-3 sm:bottom-6 sm:left-7 sm:right-7 sm:flex-row sm:items-end">
                            <div>
                                <span class="inline-block rounded-full bg-[#a67251]/90 px-3 py-0.5 text-[10px] font-bold tracking-wide text-white uppercase">
                                    {{ $slide['badge'] }}
                                </span>
                                <h1 class="mt-1.5 max-w-lg font-serif text-xl font-bold leading-snug text-white drop-shadow sm:text-3xl">
                                    {{ $slide['title'] }}
                                </h1>
                            </div>
                            <a href="{{ route('products.index') }}"
                                class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-xs font-bold text-[#593822] shadow-md transition hover:bg-[#fcf3e3]">
                                Pesan Sekarang &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach

                {{-- Panah & Indikator Slide Carousel --}}
                <button type="button" data-hero-prev aria-label="Slide sebelumnya"
                    class="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-[#28211c]/35 text-white transition hover:bg-[#28211c]/65 sm:left-5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
                <button type="button" data-hero-next aria-label="Slide berikutnya"
                    class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-[#28211c]/35 text-white transition hover:bg-[#28211c]/65 sm:right-5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>

                <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 items-center gap-2">
                    @foreach($heroSlides as $dotIndex => $slide)
                        <button type="button" data-hero-dot aria-label="Slide {{ $dotIndex + 1 }}"
                            class="h-2.5 w-2.5 rounded-full transition {{ $dotIndex === 0 ? 'bg-white' : 'bg-white/45' }}"></button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Bagian PRODUK KAMI (Kategori Utama + Menu Favorit Pelanggan) --}}
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
        <div>
            <h2 class="font-serif text-2xl font-bold text-[#2f2118] uppercase sm:text-3xl">PRODUK KAMI</h2>
            <p class="mt-1 text-sm text-[#5e4d3e]">Temukan Rasa Yang Kamu Sukai</p>
            <div class="mt-4">
                <a href="{{ route('products.index') }}"
                    class="inline-flex items-center gap-2 rounded-md bg-[#593822] px-5 py-2.5 text-xs font-bold text-white uppercase shadow-xs transition hover:bg-[#492d1a]">
                    &rarr; Jelajahi Koleksi
                </a>
            </div>
        </div>

        <hr class="my-7 border-[#8f6743]/50">

        {{-- Kartu Showcase Kategori Bergaya Frame Karamel --}}
        <div class="grid gap-6 sm:grid-cols-3">
            @foreach($categories as $category)
                <a href="{{ route('products.index', ['kategori' => $category->slug]) }}"
                    class="group relative block overflow-hidden rounded-xl bg-[#a67251] p-4 pb-6 shadow-xs transition hover:-translate-y-1 hover:shadow-md">
                    <div class="relative aspect-square overflow-hidden rounded-lg bg-[#593822]">
                        <img src="{{ asset($category->image ?: 'images/kelana/prod-brownies-panggang.png') }}"
                            alt="{{ $category->name }}"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#28211c]/65 via-transparent to-[#28211c]/20"></div>
                        <div class="absolute left-3 top-2.5 text-[10px] font-bold tracking-wide text-white/90 uppercase">
                            KELANA
                        </div>
                        <div class="absolute bottom-3 right-3 text-right">
                            <h3 class="font-serif text-xl font-bold text-white drop-shadow sm:text-2xl">
                                {{ $category->name }}
                            </h3>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pilihan Menu Favorit Pelanggan Siap Dipesan --}}
        @if($featuredProducts->isNotEmpty())
            <div class="mt-14">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="font-serif text-xl font-bold text-[#593822]">Menu Favorit Pelanggan</h3>
                    <a href="{{ route('products.index') }}" class="text-xs font-semibold text-[#8f6743] hover:text-[#593822]">Lihat semua &rarr;</a>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($featuredProducts as $product)
                        <div class="flex flex-col rounded-2xl border border-[#efe2ce] bg-white p-4 shadow-xs transition hover:-translate-y-0.5 hover:shadow-md">
                            <a href="{{ route('products.show', $product->slug) }}" class="relative block aspect-square overflow-hidden rounded-xl bg-[#f7ebd5]">
                                <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-300 hover:scale-105">
                                @if(! $product->is_available)
                                    <span class="absolute left-3 top-3 rounded-full bg-red-600 px-3 py-1 text-[10px] font-bold text-white uppercase">Habis</span>
                                @endif
                            </a>
                            <div class="mt-3.5 flex flex-1 flex-col">
                                <a href="{{ route('products.show', $product->slug) }}" class="font-serif text-base font-semibold text-[#2f2118] hover:text-[#593822]">
                                    {{ $product->name }}
                                </a>
                                <p class="mt-1 line-clamp-2 text-xs font-normal text-[#796b62]">{{ $product->description }}</p>
                                <div class="mt-auto flex items-center justify-between pt-4">
                                    <span class="text-sm font-semibold text-[#2f2118]">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
                                    @if($product->is_available)
                                        <form method="POST" action="{{ route('cart.items.store') }}">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="product_variant_id" value="{{ $product->variants->first()?->id }}">
                                            <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#a67251] text-base font-bold text-white transition hover:bg-[#593822]" title="Tambah ke Keranjang">
                                                +
                                            </button>
                                        </form>
                                    @else
                                        <span class="rounded-full bg-[#efe2ce] px-2.5 py-1 text-[10px] font-bold text-[#796b62]">Habis</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    {{-- 3. Bagian TERSEDIA DI K3MART --}}
    <section class="border-t border-[#8f6743]/40 py-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="font-serif text-2xl font-bold text-[#2f2118] uppercase sm:text-3xl">TERSEDIA DI K3MART</h2>
                        <img src="{{ asset('images/kelana/k3mart-badge.png') }}" alt="K3Mart" class="h-8 w-auto object-contain">
                    </div>
                    <p class="mt-1 text-xs text-[#6f5a4a]">Temukan favorit barumu di K3Mart Corner.</p>
                </div>
                <a href="{{ route('products.index', ['k3mart' => 1]) }}" class="btn-kelana-outline rounded-full text-xs">
                    Lihat Produk K3Mart &rarr;
                </a>
            </div>

            <div class="mt-8 grid gap-6 sm:grid-cols-3">
                @foreach($k3martProducts as $k3Product)
                    <a href="{{ route('products.show', $k3Product->slug) }}"
                        class="group overflow-hidden rounded-2xl bg-[#a67251] p-4 text-white shadow-xs transition hover:-translate-y-1 hover:shadow-md">
                        <div class="relative aspect-4/3 overflow-hidden rounded-xl bg-white">
                            <img src="{{ $k3Product->primaryImageUrl() }}" alt="{{ $k3Product->name }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            <img src="{{ asset('images/kelana/k3mart-badge.png') }}" alt="K3Mart"
                                class="absolute left-2.5 top-2.5 h-8 w-8 rounded-full bg-white/95 object-contain p-1 shadow-xs">
                        </div>
                        <div class="pt-3.5 pb-1">
                            <h3 class="font-serif text-lg font-semibold">{{ $k3Product->name }}</h3>
                            <p class="mt-0.5 text-xs text-[#f7ebd5]">Rp {{ number_format($k3Product->base_price, 0, ',', '.') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <p class="mt-6 text-center text-xs text-[#8f7968]">Tersedia saat weekend saja</p>
        </div>
    </section>

    {{-- 4. Bagian Apa Kata Mereka ? (Testimoni Pelanggan) --}}
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
        <div class="text-center">
            <div class="mx-auto mb-2 flex h-9 w-9 items-center justify-center text-[#593822]">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                </svg>
            </div>
            <h2 class="font-serif text-2xl font-bold text-[#593822] sm:text-3xl">Apa Kata Mereka ?</h2>
            <p class="mx-auto mt-2 max-w-md text-xs leading-relaxed text-[#6f5a4a]">
                Terima kasih untuk setiap cerita dan kepercayaan yang sudah diberikan kepada Brownies Kelana. Yuk simak beberapa ulasan dari pelanggan setia kami!
            </p>
        </div>

        <div class="mt-10 flex flex-wrap justify-center gap-6">
            @forelse($homeReviews as $review)
                <article class="flex w-full flex-col rounded-xl border border-[#593822] bg-white p-4 shadow-xs md:w-[calc((100%-3rem)/3)]">
                    <div class="overflow-hidden rounded-lg bg-[#f7ebd5]">
                        <img src="{{ asset('images/kelana/testimonial-ig.png') }}" alt="Testimoni Pelanggan Brownies Kelana"
                            class="h-40 w-full object-cover">
                    </div>

                    <div class="mt-3 flex justify-center gap-1 text-[#d4ad37]">
                        @for($star = 1; $star <= 5; $star++)
                            <span class="text-lg leading-none">{{ $star <= $review->rating ? '★' : '☆' }}</span>
                        @endfor
                    </div>

                    <p class="mt-3 flex-1 text-center text-xs leading-relaxed text-[#2f2118]">
                        “{{ $review->comment }}”
                    </p>

                    <div class="mt-3 border-t border-[#efe2ce] pt-2.5 text-center">
                        <p class="text-[11px] font-bold text-[#593822]">{{ $review->user?->name ?? 'Pelanggan Kelana' }}</p>
                        <p class="text-[10px] text-[#8f6743]">{{ $review->product?->name ?? 'Produk Brownies Kelana' }}</p>
                    </div>

                    @if($review->admin_reply)
                        <div class="mt-2.5 rounded-lg bg-[#f7ebd5] p-2.5 text-[11px] text-[#593822]">
                            <span class="font-bold">Balasan Brownies Kelana:</span> {{ $review->admin_reply }}
                        </div>
                    @endif
                </article>
            @empty
                <div class="col-span-3 rounded-2xl border border-[#8f6743]/40 bg-[#f7ebd5] p-8 text-center">
                    <p class="text-sm font-semibold text-[#593822]">Jadilah yang pertama berbagi cerita manis tentang Brownies Kelana!</p>
                </div>
            @endforelse
        </div>

        @if($homeReviews->isNotEmpty())
            <div class="mt-7 flex justify-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-[#593822]"></span>
                <span class="h-2.5 w-2.5 rounded-full border border-[#593822] bg-transparent"></span>
                <span class="h-2.5 w-2.5 rounded-full border border-[#593822] bg-transparent"></span>
            </div>
        @endif
    </section>
@endsection
