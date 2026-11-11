@extends('layouts.app')

@section('title', 'Keranjang Belanja — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="font-serif text-3xl font-bold text-[#593822]">Keranjang Belanja</h1>
        <p class="mt-1 text-xs text-[#6f5a4a]">Periksa kembali produk pilihan, jumlah kotak, dan catatan sebelum melanjutkan checkout.</p>

        @if($cart->items->isEmpty())
            <div class="mt-8 rounded-2xl border border-[#8f6743]/50 bg-white p-12 text-center shadow-xs">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#f7ebd5] text-[#593822]">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                    </svg>
                </div>
                <h2 class="mt-4 font-serif text-lg font-bold text-[#593822]">Keranjang Kamu Masih Kosong</h2>
                <p class="mt-1 text-xs text-[#6f5a4a]">Yuk pilih brownies, bolu, atau burnt cheesecake favoritmu di katalog kami.</p>
                <a href="{{ route('products.index') }}" class="btn-kelana-primary mt-6 rounded-full text-xs">Jelajahi Produk &rarr;</a>
            </div>
        @else
            <div class="mt-8 grid gap-8 lg:grid-cols-12">
                {{-- Daftar Item Keranjang & Catatan Pesanan --}}
                <div class="space-y-4 lg:col-span-8">
                    @foreach($cart->items as $item)
                        <div class="flex flex-col gap-4 rounded-2xl border border-[#8f6743]/50 bg-white p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                <img src="{{ $item->product->primaryImageUrl() }}" alt="{{ $item->product->name }}" class="h-20 w-20 rounded-xl object-cover">
                                <div>
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="font-serif text-base font-semibold text-[#2f2118] hover:text-[#593822]">
                                        {{ $item->product->name }}
                                    </a>
                                    <p class="text-xs text-[#6f5a4a]">
                                        {{ $item->variant?->name ?? 'Reguler' }} &middot; Rp {{ number_format($item->unitPrice(), 0, ',', '.') }}
                                    </p>
                                    @if($item->custom_note)
                                        <p class="mt-1 text-xs font-medium text-[#8f6743]">Tulisan kue: “{{ $item->custom_note }}”</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-5 sm:justify-end">
                                <div class="inline-flex items-center rounded-xl border border-[#8f6743]/50 bg-[#fcf3e3]">
                                    <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ max(0, $item->quantity - 1) }}">
                                        <button type="submit" class="px-3 py-1.5 text-xs font-bold text-[#593822] hover:bg-[#e8decb]">&minus;</button>
                                    </form>
                                    <span class="px-3 text-xs font-bold">{{ $item->quantity }}</span>
                                    <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                        <button type="submit" class="px-3 py-1.5 text-xs font-bold text-[#593822] hover:bg-[#e8decb]">+</button>
                                    </form>
                                </div>

                                <div class="text-right">
                                    <p class="text-sm font-bold text-[#593822]">Rp {{ number_format($item->lineSubtotal(), 0, ',', '.') }}</p>
                                    <form method="POST" action="{{ route('cart.items.destroy', $item) }}" class="mt-0.5">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[11px] font-bold text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Form Catatan Pesanan Keseluruhan --}}
                    <form method="POST" action="{{ route('cart.note.update') }}" class="kelana-card p-5">
                        @csrf
                        @method('PATCH')
                        <label for="order_note" class="kelana-label">Catatan pesanan (opsional)</label>
                        <div class="mt-1 flex flex-col gap-3 sm:flex-row">
                            <input id="order_note" name="order_note" type="text" value="{{ old('order_note', $cart->order_note) }}"
                                placeholder="Contoh: Tanpa kartu ucapan. Terima kasih." class="kelana-input">
                            <button type="submit" class="btn-kelana-outline shrink-0 text-xs">Simpan Catatan</button>
                        </div>
                    </form>
                </div>

                {{-- Ringkasan Pemesanan --}}
                <div class="lg:col-span-4">
                    <div class="kelana-card">
                        <div class="kelana-card-header">Ringkasan Pemesanan</div>
                        <div class="p-5">
                            <div class="space-y-2 border-b border-[#efe2ce] pb-4 text-xs">
                                @foreach($cart->items as $item)
                                    <div class="flex justify-between gap-2 text-[#5e4d3e]">
                                        <span>{{ $item->quantity }}&times; {{ $item->product->name }} &middot; {{ $item->variant?->name ?? 'Reguler' }}</span>
                                        <span class="font-semibold text-[#2f2118]">Rp {{ number_format($item->lineSubtotal(), 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 space-y-1 text-xs">
                                <div class="flex justify-between text-[#6f5a4a]">
                                    <span>Subtotal &middot; {{ $cart->totalQuantity() }} item</span>
                                    <span class="text-base font-bold text-[#593822]">Rp {{ number_format($cart->subtotalAmount(), 0, ',', '.') }}</span>
                                </div>
                                <p class="text-[11px] text-[#8f7968]">Ongkos kirim atau pickup dihitung saat halaman checkout.</p>
                            </div>

                            <div class="mt-6 space-y-2.5">
                                <a href="{{ route('checkout.index') }}" class="btn-kelana-primary w-full rounded-lg text-xs">
                                    Lanjut Checkout &rarr;
                                </a>
                                <a href="{{ route('products.index') }}" class="btn-kelana-outline w-full rounded-lg text-xs">
                                    Lanjut Belanja
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Rekomendasi Tambahan Menu --}}
        @if($recommendedProducts->isNotEmpty())
            <div class="mt-14">
                <h2 class="font-serif text-xl font-bold text-[#593822]">Sempurnakan Pesananmu</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($recommendedProducts as $rec)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-[#efe2ce] bg-white p-3 shadow-xs">
                            <a href="{{ route('products.show', $rec->slug) }}" class="flex min-w-0 items-center gap-3">
                                <img src="{{ $rec->primaryImageUrl() }}" alt="{{ $rec->name }}" class="h-14 w-14 shrink-0 rounded-xl object-cover">
                                <div class="min-w-0">
                                    <p class="truncate font-serif text-xs font-semibold text-[#2f2118]">{{ $rec->name }}</p>
                                    <p class="text-xs font-semibold text-[#593822]">Rp {{ number_format($rec->base_price, 0, ',', '.') }}</p>
                                </div>
                            </a>
                            <form method="POST" action="{{ route('cart.items.store') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $rec->id }}">
                                <input type="hidden" name="product_variant_id" value="{{ $rec->variants->first()?->id }}">
                                <button type="submit" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#a67251] text-sm font-bold text-white hover:bg-[#593822]">+</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection
