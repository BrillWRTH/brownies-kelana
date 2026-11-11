@extends('layouts.admin')

@section('title', 'Edit Produk '.$product->name.' — Panel Admin')
@section('kicker', 'UBAH DATA PRODUK')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold tracking-wider text-[#936b49] uppercase">{{ $product->product_code }}</p>
                <h1 class="font-serif text-xl font-semibold text-[#28211c]">Edit Produk: {{ $product->name }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('products.show', $product) }}" target="_blank" class="btn-kelana-outline inline-flex items-center gap-1 px-3 py-2 text-xs">
                    <span>Lihat di Toko</span>
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                    </svg>
                </a>
                <a href="{{ route('admin.products.index') }}" class="btn-kelana-outline px-4 py-2 text-xs">&larr; Daftar Produk</a>
            </div>
        </div>

        {{-- Form Utama Pembaruan Data Produk, Varian & Unggah Foto Tambahan --}}
        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-12">
            @csrf
            @method('PUT')

            <div class="space-y-5 lg:col-span-8">
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs space-y-4">
                    <h2 class="text-sm font-semibold text-[#52311a]">Informasi Utama Produk</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="category_id" class="kelana-label">Kategori Produk *</label>
                            <select id="category_id" name="category_id" required class="kelana-input text-xs">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="base_price" class="kelana-label">Harga Dasar (Rp) *</label>
                            <input id="base_price" name="base_price" type="number" min="1000" value="{{ old('base_price', $product->base_price) }}" required class="kelana-input text-xs">
                        </div>
                    </div>

                    <div>
                        <label for="name" class="kelana-label">Nama Produk *</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required class="kelana-input text-xs">
                    </div>

                    <div>
                        <label for="tagline" class="kelana-label">Tagline Singkat</label>
                        <input id="tagline" name="tagline" type="text" value="{{ old('tagline', $product->tagline) }}" class="kelana-input text-xs">
                    </div>

                    <div>
                        <label for="description" class="kelana-label">Deskripsi Produk *</label>
                        <textarea id="description" name="description" rows="4" required class="kelana-input text-xs">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div>
                        <label for="allergen_info" class="kelana-label">Informasi Alergen</label>
                        <input id="allergen_info" name="allergen_info" type="text" value="{{ old('allergen_info', $product->allergen_info) }}" class="kelana-input text-xs">
                    </div>
                </div>

                {{-- Sinkronisasi Varian Ukuran & Harga --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[#52311a]">Varian Ukuran &amp; Harga</h2>
                        <button type="button" id="btn-add-edit-variant" class="btn-kelana-outline px-3 py-1.5 text-xs">+ Tambah Varian</button>
                    </div>

                    <div id="edit-variants-container" class="mt-4 space-y-2.5">
                        @foreach($product->variants as $index => $variant)
                            <div class="variant-row grid gap-2.5 rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-3 sm:grid-cols-12">
                                <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                                <div class="sm:col-span-5">
                                    <input type="text" name="variants[{{ $index }}][name]" value="{{ $variant->name }}"
                                        placeholder="Nama Varian" class="kelana-input py-1.5 text-xs">
                                </div>
                                <div class="sm:col-span-4">
                                    <input type="number" min="1000" name="variants[{{ $index }}][price]" value="{{ $variant->price }}"
                                        placeholder="Harga (Rp)" class="kelana-input py-1.5 text-xs">
                                </div>
                                <div class="flex items-center sm:col-span-2">
                                    <input type="hidden" name="variants[{{ $index }}][is_available]" value="0">
                                    <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold">
                                        <input type="checkbox" name="variants[{{ $index }}][is_available]" value="1"
                                            @checked($variant->is_available) class="rounded border-[#b18950] text-[#52311a]">
                                        <span>Ada</span>
                                    </label>
                                </div>
                                <div class="flex items-center justify-end sm:col-span-1">
                                    <button type="button" class="btn-remove-variant rounded-lg border border-red-200 px-2 py-1 text-xs font-bold text-red-700 hover:bg-red-50">&times;</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tambah Foto Baru --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Tambahkan Foto Baru ke Galeri</h2>
                    <p class="mt-1 text-xs text-[#796b62]">Unggah foto tambahan (maks 2MB per foto). Foto lama tetap aman di bagian Galeri bawah.</p>
                    <input type="file" name="images[]" multiple accept="image/*" class="kelana-input mt-3 py-2 text-xs">
                </div>
            </div>

            {{-- Kolom Kanan --}}
            <div class="space-y-5 lg:col-span-4">
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs space-y-3 text-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Status &amp; Pengaturan</h2>

                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $product->is_available)) class="rounded border-[#b18950] text-[#52311a]">
                        <span><strong>Stok Tersedia</strong></span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded border-[#b18950] text-[#52311a]">
                        <span><strong>Tampil Aktif</strong> di katalog</span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="rounded border-[#b18950] text-[#52311a]">
                        <span><strong>Menu Andalan Home</strong></span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_k3mart" value="1" @checked(old('is_k3mart', $product->is_k3mart)) class="rounded border-[#b18950] text-[#52311a]">
                        <span>Produk <strong>K3Mart</strong></span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_weekend_only" value="1" @checked(old('is_weekend_only', $product->is_weekend_only)) class="rounded border-[#b18950] text-[#52311a]">
                        <span>Khusus Akhir Pekan</span>
                    </label>

                    <div class="border-t border-[#efe7dc] pt-3">
                        <label class="flex items-center gap-2.5">
                            <input type="checkbox" name="allows_custom_note" value="1" @checked(old('allows_custom_note', $product->allows_custom_note)) class="rounded border-[#b18950] text-[#52311a]">
                            <span>Izinkan <strong>Tulisan di Atas Kue</strong></span>
                        </label>
                        <div class="mt-2">
                            <label for="custom_note_max_chars" class="kelana-label">Maksimal Karakter</label>
                            <input id="custom_note_max_chars" name="custom_note_max_chars" type="number" min="5" max="120"
                                value="{{ old('custom_note_max_chars', $product->custom_note_max_chars) }}" class="kelana-input text-xs">
                        </div>
                    </div>

                    <button type="submit" class="btn-kelana-primary mt-4 w-full py-2.5 text-xs">
                        Simpan Perubahan Produk
                    </button>
                </div>
            </div>
        </form>

        {{-- Galeri Foto Tersimpan (Form Terpisah untuk Hapus Gambar) --}}
        <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
            <h2 class="text-sm font-semibold text-[#52311a]">Galeri Foto Tersimpan ({{ $product->images->count() }})</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3 md:grid-cols-4">
                @forelse($product->images as $image)
                    <div class="rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-2.5">
                        <img src="{{ str_starts_with($image->image_path, 'http') || str_starts_with($image->image_path, '/') ? $image->image_path : asset($image->image_path) }}"
                            alt="{{ $product->name }}" class="h-32 w-full rounded-lg object-cover">
                        <div class="mt-2 flex items-center justify-between text-[11px]">
                            <span class="font-semibold text-[#52311a]">{{ $image->is_primary ? 'Thumbnail Utama' : 'Galeri' }}</span>
                            <form method="POST" action="{{ route('admin.product-images.destroy', $image) }}"
                                onsubmit="return confirm('Hapus foto ini dari galeri produk?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-bold text-red-700 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full text-xs text-[#796b62]">Belum ada foto galeri tambahan. Gunakan form di atas untuk mengunggah foto.</p>
                @endforelse
            </div>
        </div>

        {{-- Hapus Produk (Form Terpisah) --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-200 bg-red-50/50 p-5">
            <div>
                <h3 class="text-sm font-semibold text-red-800">Hapus Produk dari Katalog</h3>
                <p class="text-xs text-red-700">Pesanan lama tetap mempertahankan nama dan harga produk pada saat transaksi terjadi.</p>
            </div>
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                onsubmit="return confirm('Yakin ingin menghapus {{ $product->name }} secara permanen?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-xs font-bold text-white hover:bg-red-800">
                    Hapus Produk Ini
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('edit-variants-container');
    let nextIdx = container.querySelectorAll('.variant-row').length;

    const bindRemove = (row) => {
        row.querySelector('.btn-remove-variant').addEventListener('click', () => {
            if (container.querySelectorAll('.variant-row').length <= 1) {
                return;
            }
            row.remove();
        });
    };

    container.querySelectorAll('.variant-row').forEach(bindRemove);

    document.getElementById('btn-add-edit-variant').addEventListener('click', () => {
        const idx = nextIdx++;
        const row = document.createElement('div');
        row.className = 'variant-row grid gap-2.5 rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-3 sm:grid-cols-12';
        row.innerHTML = `
            <div class="sm:col-span-5">
                <input type="text" name="variants[${idx}][name]" placeholder="Nama Varian Baru" class="kelana-input py-1.5 text-xs">
            </div>
            <div class="sm:col-span-4">
                <input type="number" min="1000" name="variants[${idx}][price]" value="50000" placeholder="Harga (Rp)" class="kelana-input py-1.5 text-xs">
            </div>
            <div class="flex items-center sm:col-span-2">
                <input type="hidden" name="variants[${idx}][is_available]" value="0">
                <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold">
                    <input type="checkbox" name="variants[${idx}][is_available]" value="1" checked class="rounded border-[#b18950] text-[#52311a]">
                    <span>Ada</span>
                </label>
            </div>
            <div class="flex items-center justify-end sm:col-span-1">
                <button type="button" class="btn-remove-variant rounded-lg border border-red-200 px-2 py-1 text-xs font-bold text-red-700 hover:bg-red-50">&times;</button>
            </div>
        `;
        container.appendChild(row);
        bindRemove(row);
    });
});
</script>
@endpush
