@extends('layouts.app')

@section('title', 'Brownies Kelana — Dibuat Untuk Dinikmati')

@section('content')
    @php
        $store = $sharedStoreSetting ?? \App\Models\StoreSetting::current();
    @endphp

    {{-- 1. Hero Banner Cokelat Tua dengan Foto Tray Brownies Panggang --}}
    <section class="relative overflow-hidden bg-[#52311a] pb-14 pt-8 text-white sm:pb-20 sm:pt-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl border-4 border-[#67442f] bg-[#492d1a] shadow-2xl">
                <div class="relative">
                    <img src="{{ asset('images/kelana/hero-brownies.png') }}" alt="Brownies Panggang Signature Kelana"
                        class="h-64 w-full object-cover sm:h-96 md:h-[430px]">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#28211c]/80 via-[#28211c]/20 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                        <div>
                            <span class="inline-block rounded-full bg-[#b18950] px-3 py-1 text-[10px] font-bold tracking-widest text-white uppercase">
                                {{ $store->tagline }}
                            </span>
                            <h1 class="mt-2 font-serif text-2xl font-bold leading-tight text-white sm:text-4xl">
                                Hangatnya Brownies Panggang<br>untuk Setiap Momen Spesial
                            </h1>
                        </div>
                        <a href="{{ route('products.index') }}" class="rounded-full bg-white px-5 py-2.5 text-xs font-bold tracking-wider text-[#52311a] uppercase shadow-md transition hover:bg-[#f9eddd]">
                            Pesan Sekarang &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Bagian PRODUK KAMI (Kategori Utama + Unggulan) --}}
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-[#52311a] uppercase sm:text-3xl">PRODUK KAMI</h2>
                <p class="mt-1 text-sm text-[#796b62]">Temukan Rasa Yang Kamu Suka!</p>
            </div>
            <a href="{{ route('products.index') }}" class="btn-kelana-primary rounded-full text-xs tracking-wider uppercase">
                Jelajahi Koleksi &rarr;
            </a>
        </div>

        <div class="mt-8 grid gap-6 sm:grid-cols-3">
            @foreach($categories as $category)
                <a href="{{ route('products.index', ['kategori' => $category->slug]) }}"
                    class="group relative overflow-hidden rounded-2xl border border-[#e3d5c3] bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="aspect-4/3 overflow-hidden bg-[#f9eddd]">
                        <img src="{{ asset($category->image ?: 'images/kelana/prod-brownies-panggang.png') }}"
                            alt="{{ $category->name }}"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    </div>
                    <div class="flex items-center justify-between bg-[#52311a] px-5 py-3.5 text-white">
                        <div>
                            <h3 class="text-sm font-bold tracking-wide uppercase">{{ $category->name }}</h3>
                            <p class="line-clamp-1 text-[11px] text-[#e9dcc7]">{{ $category->description }}</p>
                        </div>
                        <span class="text-xs font-bold text-[#e9dcc7]">&rsaquo;</span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pilihan Produk Unggulan Siap Dipesan --}}
        @if($featuredProducts->isNotEmpty())
            <div class="mt-12">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-base font-bold text-[#52311a]">Menu Favorit Pelanggan</h3>
                    <a href="{{ route('products.index') }}" class="text-xs font-semibold text-[#936b49] hover:text-[#52311a]">Lihat semua &rarr;</a>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($featuredProducts as $product)
                        <div class="flex flex-col overflow-hidden rounded-2xl border border-[#e3d5c3] bg-white shadow-xs transition hover:shadow-md">
                            <a href="{{ route('products.show', $product->slug) }}" class="relative block aspect-4/3 overflow-hidden bg-[#f9eddd]">
                                <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-300 hover:scale-105">
                                @if(! $product->is_available)
                                    <span class="absolute left-3 top-3 rounded-full bg-red-600 px-3 py-1 text-[10px] font-bold text-white uppercase">Habis</span>
                                @endif
                            </a>
                            <div class="flex flex-1 flex-col p-4">
                                <span class="text-[10px] font-bold tracking-wider text-[#936b49] uppercase">{{ $product->category?->name }}</span>
                                <a href="{{ route('products.show', $product->slug) }}" class="mt-1 text-base font-bold text-[#28211c] hover:text-[#52311a]">
                                    {{ $product->name }}
                                </a>
                                <p class="mt-1 line-clamp-2 text-xs text-[#796b62]">{{ $product->description }}</p>
                                <div class="mt-auto flex items-center justify-between pt-4">
                                    <span class="text-sm font-extrabold text-[#52311a]">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
                                    @if($product->is_available)
                                        <form method="POST" action="{{ route('cart.items.store') }}">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="product_variant_id" value="{{ $product->variants->first()?->id }}">
                                            <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#52311a] text-sm font-bold text-white transition hover:bg-[#492d1a]" title="Tambah ke Keranjang">
                                                +
                                            </button>
                                        </form>
                                    @else
                                        <span class="rounded-full bg-[#f2e4cd] px-2.5 py-1 text-[10px] font-bold text-[#796b62]">Habis</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    {{-- 3. Bagian TERSEDIA DI K3MART (Sisi Lain dari Kelana) --}}
    <section class="border-y border-[#e9dcc7] bg-[#fbf4e9] py-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight text-[#52311a] uppercase sm:text-2xl">TERSEDIA DI K3MART</h2>
                        <p class="text-xs text-[#796b62]">Temukan favorit Kelana &amp; jajanan spesial akhir pekan di K3Mart Corner.</p>
                    </div>
                    <img src="{{ asset('images/kelana/k3mart-badge.png') }}" alt="K3Mart" class="h-8 w-auto object-contain">
                </div>
                <a href="{{ route('products.index', ['k3mart' => 1]) }}" class="btn-kelana-outline rounded-full text-xs">
                    Lihat Produk K3Mart &rarr;
                </a>
            </div>

            <div class="mt-8 grid gap-6 sm:grid-cols-3">
                @foreach($k3martProducts as $k3Product)
                    <a href="{{ route('products.show', $k3Product->slug) }}"
                        class="group overflow-hidden rounded-2xl border border-[#8d6c4d]/30 bg-[#9b7251] text-white shadow-md transition hover:-translate-y-1">
                        <div class="relative aspect-4/3 overflow-hidden bg-white">
                            <img src="{{ $k3Product->primaryImageUrl() }}" alt="{{ $k3Product->name }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @if($k3Product->is_weekend_only)
                                <span class="absolute left-3 top-3 rounded-full bg-[#52311a] px-3 py-1 text-[10px] font-bold text-white">
                                    Hanya Akhir Pekan
                                </span>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="text-base font-bold">{{ $k3Product->name }}</h3>
                            <p class="mt-0.5 text-xs text-[#f9eddd]">Rp {{ number_format($k3Product->base_price, 0, ',', '.') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. Bagian Apa Kata Mereka ? (Testimoni Pelanggan Pilihan Admin) --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="font-serif text-2xl font-bold text-[#52311a] sm:text-3xl">Apa Kata Mereka ?</h2>
            <p class="mx-auto mt-2 max-w-md text-xs leading-relaxed text-[#796b62]">
                Cerita jujur dari pelanggan setia yang menikmati kelezatan Brownies Kelana di setiap momen berharga.
            </p>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @forelse($homeReviews as $review)
                <article class="flex flex-col rounded-2xl border border-[#e3d5c3] bg-white p-6 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#52311a] text-sm font-bold text-white">
                                {{ strtoupper(substr($review->user?->name ?? 'P', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-[#28211c]">{{ $review->user?->name ?? 'Pelanggan Kelana' }}</p>
                                <p class="text-[11px] text-[#936b49]">{{ $review->product?->name ?? 'Produk Brownies Kelana' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex gap-0.5 text-amber-500">
                        @for($star = 1; $star <= 5; $star++)
                            <span class="text-sm">{{ $star <= $review->rating ? '★' : '☆' }}</span>
                        @endfor
                    </div>

                    <p class="mt-3 flex-1 text-xs leading-relaxed text-[#5e524a]">
                        “{{ $review->comment }}”
                    </p>

                    @if($review->admin_reply)
                        <div class="mt-4 rounded-xl bg-[#f9eddd] p-3 text-[11px] text-[#52311a]">
                            <span class="font-bold">Balasan Brownies Kelana:</span> {{ $review->admin_reply }}
                        </div>
                    @endif
                </article>
            @empty
                <div class="col-span-3 rounded-2xl border border-[#e3d5c3] bg-[#f9eddd] p-8 text-center">
                    <p class="text-sm font-semibold text-[#52311a]">Jadilah yang pertama berbagi cerita manis tentang Brownies Kelana!</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection

