@extends('layouts.app')

@section('title', $product->name.' — Brownies Kelana')

@section('content')
    @php
        $defaultVariant = $product->variants->first();
        $galleryImages = $product->images->isNotEmpty()
            ? $product->images->map(fn ($img) => str_starts_with($img->image_path, 'http') || str_starts_with($img->image_path, '/') ? $img->image_path : asset($img->image_path))
            : collect([$product->primaryImageUrl()]);
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-2 text-xs text-[#796b62]">
            <a href="{{ route('products.index') }}" class="font-semibold text-[#593822] hover:underline">&larr; Kembali ke Katalog</a>
            <span>/</span>
            <span>{{ $product->category?->name }}</span>
        </div>

        <div class="grid items-start gap-10 lg:grid-cols-12">
            {{-- Galeri Foto Produk langsung di atas kanvas krem --}}
            <div class="lg:col-span-5">
                <div class="relative overflow-hidden rounded-2xl bg-white shadow-xs">
                    <img id="main-product-photo" src="{{ $galleryImages->first() }}" alt="{{ $product->name }}"
                        class="aspect-square w-full object-cover {{ $product->is_available ? '' : 'opacity-65 grayscale-[30%]' }}">
                    @if(! $product->is_available)
                        <span class="absolute left-4 top-4 rounded-full bg-red-600 px-3.5 py-1 text-xs font-bold text-white uppercase">Habis</span>
                    @endif
                </div>

                @if($galleryImages->count() > 1)
                    <div class="mt-4 flex gap-3 overflow-x-auto pb-1">
                        @foreach($galleryImages as $imgUrl)
                            <button type="button" onclick="document.getElementById('main-product-photo').src = '{{ $imgUrl }}'"
                                class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border-2 border-[#efe2ce] bg-white transition hover:border-[#593822]">
                                <img src="{{ $imgUrl }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Detail Produk, Deskripsi Krem Hangat & Bar Pemesanan --}}
            <div class="lg:col-span-7">
                @if($product->tagline)
                    <p class="text-sm text-[#593822]">{{ $product->tagline }}</p>
                @endif

                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h1 class="font-serif text-3xl font-bold text-[#2f2118] sm:text-4xl">{{ $product->name }}</h1>
                    @if(! $product->is_available)
                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Stok Habis</span>
                    @endif
                    @if($product->is_weekend_only)
                        <span class="rounded-full bg-[#e8decb] px-3 py-1 text-xs font-bold text-[#593822]">Khusus Akhir Pekan</span>
                    @endif
                </div>

                <div class="mt-5 rounded-2xl bg-[#e8decb] p-5 sm:p-6">
                    <h2 class="font-serif text-sm font-semibold text-[#2f2118]">Deskripsi</h2>
                    <p class="mt-2 font-serif text-sm leading-relaxed text-[#3b2a1e]">{{ $product->description }}</p>

                    @if($product->allergen_info)
                        <p class="mt-3 border-t border-[#8f6743]/30 pt-2.5 text-xs text-[#5e4d3e]">
                            <strong class="text-[#593822]">Informasi Alergen:</strong> {{ $product->allergen_info }}
                        </p>
                    @endif
                </div>

                <form method="POST" action="{{ route('cart.items.store') }}" class="mt-6 space-y-5" id="product-order-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    {{-- Pilihan Varian Ukuran / Paket --}}
                    @if($product->variants->isNotEmpty())
                        <div>
                            <label class="kelana-label uppercase">Pilih Varian</label>
                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                @foreach($product->variants as $index => $variant)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="product_variant_id" value="{{ $variant->id }}"
                                            data-price="{{ (int) $variant->price }}"
                                            class="peer sr-only variant-radio"
                                            {{ $index === 0 ? 'checked' : '' }}>
                                        <div class="flex items-center justify-between rounded-xl border-2 border-[#d9c8b4] bg-white px-4 py-3 transition peer-checked:border-[#593822] peer-checked:bg-[#f7ebd5]">
                                            <span class="text-xs font-semibold text-[#2f2118]">{{ $variant->name }}</span>
                                            <span class="text-xs font-bold text-[#593822]">Rp {{ number_format($variant->price, 0, ',', '.') }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Input Tulisan Khusus Kue jika Diaktifkan --}}
                    @if($product->allows_custom_note)
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="custom_note" class="kelana-label">Tulisan Khusus di Kue (Opsional)</label>
                                <span class="text-[11px] text-[#8d7d70]">Maks {{ $product->custom_note_max_chars ?: 30 }} karakter</span>
                            </div>
                            <input id="custom_note" name="custom_note" type="text"
                                maxlength="{{ $product->custom_note_max_chars ?: 30 }}"
                                placeholder="Contoh: Happy Birthday ke-21"
                                class="kelana-input">
                        </div>
                    @endif

                    {{-- Action Pill Bar Horizontal ala UI/UX: Qty (- 1 +) + Harga + Tombol --}}
                    @if($product->is_available)
                        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#8f6743] bg-white px-4 py-3.5 shadow-xs">
                            <div class="flex items-center gap-4">
                                <div class="inline-flex items-center gap-2.5">
                                    <button type="button" id="qty-minus" aria-label="Kurangi jumlah"
                                        class="flex h-7 w-7 items-center justify-center rounded-full border border-[#8f6743] text-sm font-bold text-[#593822] transition hover:bg-[#f7ebd5]">&minus;</button>
                                    <input id="qty-input" name="quantity" type="number" min="1" max="50" value="1"
                                        class="w-10 border-0 bg-transparent text-center text-sm font-bold text-[#2f2118] focus:outline-none">
                                    <button type="button" id="qty-plus" aria-label="Tambah jumlah"
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-[#a67251] text-sm font-bold text-white transition hover:bg-[#593822]">+</button>
                                </div>

                                <span id="live-total-price" class="text-base font-bold text-[#2f2118]">
                                    Rp {{ number_format($defaultVariant?->price ?? $product->base_price, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5">
                                <button type="submit" name="buy_now" value="0"
                                    class="rounded-md bg-[#593822] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#492d1a]">
                                    + Keranjang
                                </button>
                                <button type="submit" name="buy_now" value="1"
                                    class="rounded-md bg-[#a67251] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#8f5e40]">
                                    Beli Sekarang
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-center">
                            <p class="text-xs font-semibold text-red-800">Produk ini sedang habis sementara waktu.</p>
                            <a href="{{ route('products.index') }}" class="btn-kelana-primary mt-3 rounded-full text-xs">Kembali ke Katalog</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        {{-- Ulasan Pelanggan untuk Produk Ini --}}
        <div class="mt-14 rounded-2xl border border-[#efe2ce] bg-white p-6 shadow-xs sm:p-8">
            <h2 class="font-serif text-xl font-semibold text-[#593822]">Ulasan Pelanggan ({{ $approvedReviews->count() }})</h2>
            @if($approvedReviews->isEmpty())
                <p class="mt-2 text-xs text-[#796b62]">Belum ada ulasan terverifikasi untuk produk ini.</p>
            @else
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach($approvedReviews as $review)
                        <div class="rounded-2xl border border-[#efe2ce] bg-[#fcf3e3]/60 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#2f2118]">{{ $review->user?->name ?? 'Pelanggan' }}</span>
                                <span class="text-xs text-[#d4ad37]">{{ str_repeat('★', $review->rating) }}</span>
                            </div>
                            <p class="mt-2 text-xs leading-relaxed text-[#5e4d3e]">“{{ $review->comment }}”</p>
                            @if($review->admin_reply)
                                <p class="mt-2 rounded-lg bg-[#f7ebd5] p-2 text-[11px] text-[#593822]">
                                    <strong>Balasan Admin:</strong> {{ $review->admin_reply }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Produk Terkait --}}
        @if($relatedProducts->isNotEmpty())
            <div class="mt-12">
                <h2 class="font-serif text-xl font-semibold text-[#593822]">Menu Sejenis Lainnya</h2>
                <div class="mt-5 grid gap-6 sm:grid-cols-3">
                    @foreach($relatedProducts as $rel)
                        <a href="{{ route('products.show', $rel->slug) }}" class="overflow-hidden rounded-2xl border border-[#efe2ce] bg-white p-3.5 shadow-xs transition hover:shadow-md">
                            <img src="{{ $rel->primaryImageUrl() }}" alt="{{ $rel->name }}" class="aspect-square w-full rounded-xl object-cover">
                            <div class="pt-3">
                                <h3 class="font-serif text-sm font-medium text-[#2f2118]">{{ $rel->name }}</h3>
                                <p class="mt-1 text-xs font-semibold text-[#593822]">Rp {{ number_format($rel->base_price, 0, ',', '.') }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const radios = document.querySelectorAll('.variant-radio');
                const qtyInput = document.getElementById('qty-input');
                const minusBtn = document.getElementById('qty-minus');
                const plusBtn = document.getElementById('qty-plus');
                const priceEl = document.getElementById('live-total-price');

                let unitPrice = {{ (int) ($defaultVariant?->price ?? $product->base_price) }};

                const renderTotal = () => {
                    const selected = document.querySelector('.variant-radio:checked');
                    if (selected) {
                        unitPrice = parseInt(selected.dataset.price || unitPrice, 10);
                    }
                    const qty = Math.max(1, parseInt(qtyInput.value || '1', 10));
                    qtyInput.value = qty;
                    priceEl.textContent = 'Rp ' + (unitPrice * qty).toLocaleString('id-ID');
                };

                radios.forEach((r) => r.addEventListener('change', renderTotal));
                qtyInput?.addEventListener('input', renderTotal);
                minusBtn?.addEventListener('click', () => {
                    qtyInput.value = Math.max(1, parseInt(qtyInput.value || '1', 10) - 1);
                    renderTotal();
                });
                plusBtn?.addEventListener('click', () => {
                    qtyInput.value = Math.min(50, parseInt(qtyInput.value || '1', 10) + 1);
                    renderTotal();
                });
            });
        </script>
    @endpush
@endsection
