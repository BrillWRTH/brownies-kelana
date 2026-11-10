@extends('layouts.app')

@section('title', 'Katalog Produk — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Header Judul Katalog & Form Pencarian --}}
        <div class="flex flex-col justify-between gap-4 border-b border-[#e9dcc7] pb-6 sm:flex-row sm:items-end">
            <div>
                <h1 class="font-serif text-2xl font-bold text-[#52311a] sm:text-3xl">Temukan Rasa Yang Kamu Suka !</h1>
                <p class="mt-1 text-xs text-[#796b62]">Aneka brownies panggang, bolu klasik, brookies, cheesecake, hingga jajanan K3Mart.</p>
            </div>

            <form method="GET" action="{{ route('products.index') }}" class="flex w-full max-w-sm items-center gap-2">
                @if($selectedCategorySlug !== '')
                    <input type="hidden" name="kategori" value="{{ $selectedCategorySlug }}">
                @endif
                @if($isK3martFilter)
                    <input type="hidden" name="k3mart" value="1">
                @endif
                <div class="relative flex-1">
                    <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="Cari brownies, bolu, dimsum..."
                        class="kelana-input rounded-full pl-4 pr-9">
                    @if($searchKeyword !== '')
                        <a href="{{ route('products.index', array_filter(['kategori' => $selectedCategorySlug ?: null, 'k3mart' => $isK3martFilter ? 1 : null])) }}"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-[#8d7d70] hover:text-[#52311a]" title="Hapus Pencarian">
                            &times;
                        </a>
                    @endif
                </div>
                <button type="submit" class="btn-kelana-primary rounded-full px-4 py-2.5 text-xs">Cari</button>
            </form>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-12">
            {{-- Sidebar Filter Kategori & K3Mart Corner --}}
            <aside class="lg:col-span-3">
                <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs">
                    <h2 class="text-xs font-bold tracking-wider text-[#936b49] uppercase">Kategori Menu</h2>
                    <ul class="mt-3 space-y-1.5 text-xs font-semibold">
                        <li>
                            <a href="{{ route('products.index', $searchKeyword !== '' ? ['q' => $searchKeyword] : []) }}"
                                class="flex items-center justify-between rounded-xl px-3 py-2 transition {{ $selectedCategorySlug === '' && !$isK3martFilter ? 'bg-[#52311a] text-white' : 'text-[#52311a] hover:bg-[#f9eddd]' }}">
                                <span>Semua Produk</span>
                                <span>&rsaquo;</span>
                            </a>
                        </li>
                        @foreach($categories as $category)
                            <li>
                                <a href="{{ route('products.index', array_filter(['kategori' => $category->slug, 'q' => $searchKeyword ?: null])) }}"
                                    class="flex items-center justify-between rounded-xl px-3 py-2 transition {{ $selectedCategorySlug === $category->slug ? 'bg-[#52311a] text-white' : 'text-[#52311a] hover:bg-[#f9eddd]' }}">
                                    <span>{{ $category->name }}</span>
                                    <span>&rsaquo;</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6 rounded-2xl border border-[#e3d5c3] bg-[#f9eddd] p-4">
                        <p class="text-[10px] font-bold tracking-widest text-[#936b49] uppercase">Tersedia di</p>
                        <img src="{{ asset('images/kelana/k3mart-badge.png') }}" alt="K3Mart Corner" class="mt-1 h-7 w-auto object-contain">
                        <p class="mt-2 text-[11px] text-[#5e524a]">Menu edisi spesial akhir pekan di K3Mart Corner.</p>
                        <a href="{{ route('products.index', ['k3mart' => 1]) }}"
                            class="mt-3 inline-flex items-center gap-1 rounded-full {{ $isK3martFilter ? 'bg-[#52311a] text-white' : 'bg-white text-[#52311a]' }} px-3.5 py-1.5 text-[11px] font-bold shadow-xs hover:bg-[#52311a] hover:text-white">
                            Lihat produk K3Mart &rsaquo;
                        </a>
                    </div>
                </div>
            </aside>

            {{-- Grid Produk Katalog --}}
            <div class="lg:col-span-9">
                @if($searchKeyword !== '')
                    <p class="mb-4 text-xs text-[#796b62]">
                        Menampilkan <strong>{{ $products->total() }}</strong> hasil untuk “{{ $searchKeyword }}”.
                    </p>
                @endif

                @if($products->isEmpty())
                    <div class="rounded-3xl border border-[#e3d5c3] bg-white p-12 text-center shadow-xs">
                        <h3 class="font-serif text-xl font-bold text-[#52311a]">Produk tidak ditemukan</h3>
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
                            <div class="flex flex-col overflow-hidden rounded-2xl border border-[#e3d5c3] bg-white shadow-xs transition hover:-translate-y-0.5 hover:shadow-md">
                                <a href="{{ route('products.show', $product->slug) }}" class="relative block aspect-4/3 overflow-hidden bg-[#f9eddd]">
                                    <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}"
                                        class="h-full w-full object-cover transition duration-300 hover:scale-105 {{ $product->is_available ? '' : 'opacity-65 grayscale-[30%]' }}">
                                    @if(! $product->is_available)
                                        <span class="absolute left-3 top-3 rounded-full bg-red-600 px-3 py-1 text-[10px] font-bold text-white uppercase">Habis</span>
                                    @elseif($product->is_k3mart)
                                        <span class="absolute left-3 top-3 rounded-full bg-[#52311a] px-3 py-1 text-[10px] font-bold text-white">K3Mart</span>
                                    @endif
                                </a>

                                <div class="flex flex-1 flex-col p-4">
                                    <span class="text-[10px] font-bold tracking-wider text-[#936b49] uppercase">{{ $product->category?->name }}</span>
                                    <a href="{{ route('products.show', $product->slug) }}" class="mt-1 text-base font-bold text-[#28211c] hover:text-[#52311a]">
                                        {{ $product->name }}
                                    </a>
                                    <p class="mt-1 line-clamp-2 text-xs text-[#796b62]">{{ $product->description }}</p>

                                    <div class="mt-auto flex items-center justify-between pt-4">
                                        <div>
                                            <span class="block text-[10px] text-[#8d7d70]">Mulai dari</span>
                                            <span class="text-sm font-extrabold text-[#52311a]">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
                                        </div>

                                        @if($product->is_available)
                                            <form method="POST" action="{{ route('cart.items.store') }}">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <input type="hidden" name="product_variant_id" value="{{ $product->variants->first()?->id }}">
                                                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-full bg-[#52311a] text-base font-bold text-white transition hover:bg-[#492d1a]" title="Tambah ke Keranjang">
                                                    +
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('products.show', $product->slug) }}" class="rounded-full bg-[#f2e4cd] px-3 py-1 text-[10px] font-bold text-[#796b62]">Detail</a>
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

