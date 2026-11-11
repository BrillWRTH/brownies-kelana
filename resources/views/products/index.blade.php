@extends('layouts.app')

@section('title', 'Katalog Produk — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-12">
            {{-- Sidebar Filter Kategori Tanpa Kotak + Kartu K3Mart --}}
            <aside class="lg:col-span-3 lg:pt-14">
                <ul class="space-y-1 text-sm font-semibold">
                    <li>
                        <a href="{{ route('products.index', $searchKeyword !== '' ? ['q' => $searchKeyword] : []) }}"
                            class="block rounded-full px-4 py-2 transition {{ $selectedCategorySlug === '' && !$isK3martFilter ? 'bg-[#593822] text-white' : 'text-[#3b2a1e] hover:bg-[#f7ebd5]' }}">
                            Semua Produk
                        </a>
                    </li>
                    @foreach($categories as $category)
                        <li>
                            <a href="{{ route('products.index', array_filter(['kategori' => $category->slug, 'q' => $searchKeyword ?: null])) }}"
                                class="block rounded-full px-4 py-2 transition {{ $selectedCategorySlug === $category->slug ? 'bg-[#593822] text-white' : 'text-[#3b2a1e] hover:bg-[#f7ebd5]' }}">
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <hr class="my-5 border-[#8f6743]/50">

                <a href="{{ route('products.index', ['k3mart' => 1]) }}"
                    class="block rounded-2xl border bg-white p-4 shadow-xs transition hover:shadow-md {{ $isK3martFilter ? 'border-[#593822] ring-2 ring-[#593822]/25' : 'border-[#efe2ce]' }}">
                    <p class="text-[10px] font-bold text-[#593822] uppercase">Tersedia di</p>
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ asset('images/kelana/k3mart-badge.png') }}" alt="K3Mart Corner" class="h-8 w-8 shrink-0 object-contain">
                            <div>
                                <span class="block text-sm font-bold text-[#2f2118]">K3Mart</span>
                                <span class="block text-[11px] text-[#796b62]">Lihat produk</span>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-[#593822]">&rsaquo;</span>
                    </div>
                </a>
            </aside>

            {{-- Kolom Kanan: Judul + Pencarian Pill + Grid Produk --}}
            <div class="lg:col-span-9">
                <div class="flex flex-col justify-between gap-4 pb-6 sm:flex-row sm:items-center">
                    <h1 class="font-serif text-2xl font-bold text-[#2f2118] sm:text-3xl">Temukan Rasa Yang Kamu suka !</h1>

                    <form method="GET" action="{{ route('products.index') }}" class="flex w-full items-center gap-2 sm:w-64">
                        @if($selectedCategorySlug !== '')
                            <input type="hidden" name="kategori" value="{{ $selectedCategorySlug }}">
                        @endif
                        @if($isK3martFilter)
                            <input type="hidden" name="k3mart" value="1">
                        @endif
                        <div class="relative flex-1">
                            <button type="submit" aria-label="Cari produk"
                                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#593822]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </button>
                            <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="Cari..."
                                class="w-full rounded-full border border-[#efe2ce] bg-white py-2 pl-10 pr-8 text-xs text-[#2f2118] shadow-xs placeholder-[#9e8a79] focus:border-[#593822] focus:outline-none">
                            @if($searchKeyword !== '')
                                <a href="{{ route('products.index', array_filter(['kategori' => $selectedCategorySlug ?: null, 'k3mart' => $isK3martFilter ? 1 : null])) }}"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-[#8d7d70] hover:text-[#593822]" title="Hapus Pencarian">
                                    &times;
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                @if($searchKeyword !== '')
                    <p class="mb-4 text-xs text-[#796b62]">
                        Menampilkan <strong>{{ $products->total() }}</strong> hasil untuk “{{ $searchKeyword }}”.
                    </p>
                @endif

                @if($products->isEmpty())
                    <div class="rounded-3xl border border-[#efe2ce] bg-white p-12 text-center shadow-xs">
                        <h3 class="font-serif text-xl font-bold text-[#593822]">Produk tidak ditemukan</h3>
                        <p class="mx-auto mt-2 max-w-md text-xs text-[#796b62]">
                            Coba kata kunci lain atau hapus pencarian untuk melihat semua produk Brownies Kelana.
                        </p>
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            @if($searchKeyword !== '')
                                <a href="{{ route('products.index', array_filter(['kategori' => $selectedCategorySlug ?: null])) }}" class="btn-kelana-outline rounded-full text-xs">
                                    Hapus Pencarian
                                </a>
                            @endif
                            <a href="{{ route('products.index') }}" class="btn-kelana-primary rounded-full text-xs">
                                Kembali ke Semua Produk
                            </a>
                        </div>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($products as $product)
                            <div class="flex flex-col rounded-2xl border border-[#efe2ce] bg-white p-3.5 shadow-xs transition hover:-translate-y-0.5 hover:shadow-md">
                                <a href="{{ route('products.show', $product->slug) }}" class="relative block aspect-square overflow-hidden rounded-xl bg-[#f7ebd5]">
                                    <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}"
                                        class="h-full w-full object-cover transition duration-300 hover:scale-105 {{ $product->is_available ? '' : 'opacity-65 grayscale-[30%]' }}">
                                    @if(! $product->is_available)
                                        <span class="absolute left-2.5 top-2.5 rounded-full bg-red-600 px-2.5 py-0.5 text-[10px] font-bold text-white uppercase">Habis</span>
                                    @elseif($product->is_k3mart)
                                        <span class="absolute left-2.5 top-2.5 rounded-full bg-[#593822] px-2.5 py-0.5 text-[10px] font-bold text-white">K3Mart</span>
                                    @endif
                                </a>

                                <div class="mt-3.5 flex flex-1 flex-col">
                                    <a href="{{ route('products.show', $product->slug) }}" class="font-serif text-base font-semibold text-[#2f2118] hover:text-[#593822]">
                                        {{ $product->name }}
                                    </a>

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
                                            <a href="{{ route('products.show', $product->slug) }}" class="rounded-full bg-[#efe2ce] px-3 py-1 text-[10px] font-bold text-[#796b62]">Detail</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
