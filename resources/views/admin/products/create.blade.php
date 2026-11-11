@extends('layouts.admin')

@section('title', 'Tambah Produk Baru — Panel Admin')
@section('kicker', 'TAMBAH PRODUK KATALOG')

@section('content')
    @php
        $oldVariants = old('variants', [
            ['name' => 'Reguler', 'price' => 45000, 'is_available' => 1],
        ]);
    @endphp

    <div class="mx-auto max-w-5xl space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-serif text-xl font-semibold text-[#28211c]">Tambah Produk Brownies / Kue</h1>
                <p class="mt-1 text-xs text-[#796b62]">Lengkapi deskripsi, alergen, varian ukuran/harga, serta galeri foto produk.</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="btn-kelana-outline px-4 py-2 text-xs">&larr; Kembali</a>
        </div>

        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-12">
            @csrf

            <div class="space-y-5 lg:col-span-8">
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs space-y-4">
                    <h2 class="text-sm font-semibold text-[#52311a]">Informasi Utama Produk</h2>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="product_code" class="kelana-label">Kode Produk</label>
                            <input id="product_code" name="product_code" type="text" value="{{ old('product_code', $suggestedCode) }}" class="kelana-input text-xs">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="category_id" class="kelana-label">Kategori Produk *</label>
                            <select id="category_id" name="category_id" required class="kelana-input text-xs">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="name" class="kelana-label">Nama Produk *</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="Contoh: Brownies Panggang Fudgy Keju" class="kelana-input text-xs">
                    </div>

                    <div>
                        <label for="tagline" class="kelana-label">Tagline Singkat</label>
                        <input id="tagline" name="tagline" type="text" value="{{ old('tagline') }}" placeholder="Contoh: Lembut di dalam, shiny crust di luar" class="kelana-input text-xs">
                    </div>

                    <div>
                        <label for="description" class="kelana-label">Deskripsi Produk *</label>
                        <textarea id="description" name="description" rows="4" required class="kelana-input text-xs">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="base_price" class="kelana-label">Harga Dasar Mulai (Rp) *</label>
                            <input id="base_price" name="base_price" type="number" min="1000" value="{{ old('base_price', 45000) }}" required class="kelana-input text-xs">
                        </div>
                        <div>
                            <label for="allergen_info" class="kelana-label">Informasi Alergen</label>
                            <input id="allergen_info" name="allergen_info" type="text" value="{{ old('allergen_info', 'Telur, gandum (gluten), susu') }}" class="kelana-input text-xs">
                        </div>
                    </div>
                </div>

                {{-- Varian Ukuran / Harga --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-[#52311a]">Varian Ukuran &amp; Harga</h2>
                            <p class="text-[11px] text-[#796b62]">Harga dasar otomatis mengikuti varian termurah.</p>
                        </div>
                        <button type="button" id="btn-add-variant" class="btn-kelana-outline px-3 py-1.5 text-xs">+ Tambah Varian</button>
                    </div>

                    <div id="variants-container" class="mt-4 space-y-2.5">
                        @foreach($oldVariants as $index => $variant)
                            <div class="variant-row grid gap-2.5 rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-3 sm:grid-cols-12">
                                <div class="sm:col-span-5">
                                    <input type="text" name="variants[{{ $index }}][name]" value="{{ $variant['name'] ?? '' }}"
                                        placeholder="Nama Varian (misal: Loyang 20x20)" class="kelana-input py-1.5 text-xs">
                                </div>
                                <div class="sm:col-span-4">
                                    <input type="number" min="1000" name="variants[{{ $index }}][price]" value="{{ $variant['price'] ?? 45000 }}"
                                        placeholder="Harga (Rp)" class="kelana-input py-1.5 text-xs">
                                </div>
                                <div class="flex items-center sm:col-span-2">
                                    <input type="hidden" name="variants[{{ $index }}][is_available]" value="0">
                                    <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold">
                                        <input type="checkbox" name="variants[{{ $index }}][is_available]" value="1"
                                            @checked(!empty($variant['is_available'])) class="rounded border-[#b18950] text-[#52311a]">
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

                {{-- Unggah Foto Galeri --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Foto Produk Galeri</h2>
                    <p class="mt-1 text-xs text-[#796b62]">Unggah satu atau beberapa gambar sekaligus (maks 2MB per foto). Foto pertama menjadi thumbnail utama.</p>
                    <input type="file" name="images[]" multiple accept="image/*" class="kelana-input mt-3 py-2 text-xs">
                </div>
            </div>

            {{-- Kolom Kanan: Status, K3Mart & Catatan Kue --}}
            <div class="space-y-5 lg:col-span-4">
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs space-y-3 text-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Pengaturan Tampilan &amp; Stok</h2>

                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_available" value="1" @checked(old('is_available', true)) class="rounded border-[#b18950] text-[#52311a]">
                        <span><strong>Stok Tersedia</strong> (bisa dimasukkan keranjang)</span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-[#b18950] text-[#52311a]">
                        <span><strong>Tampil Aktif</strong> di katalog storefront</span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured')) class="rounded border-[#b18950] text-[#52311a]">
                        <span>Tampilkan di <strong>Menu Andalan Home</strong></span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_k3mart" value="1" @checked(old('is_k3mart')) class="rounded border-[#b18950] text-[#52311a]">
                        <span>Produk <strong>Sisi Lain Kelana · K3Mart</strong></span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="is_weekend_only" value="1" @checked(old('is_weekend_only')) class="rounded border-[#b18950] text-[#52311a]">
                        <span>Khusus akhir pekan (Weekend Only)</span>
                    </label>

                    <div class="border-t border-[#efe7dc] pt-3">
                        <label class="flex items-center gap-2.5">
                            <input type="checkbox" name="allows_custom_note" value="1" @checked(old('allows_custom_note')) class="rounded border-[#b18950] text-[#52311a]">
                            <span>Izinkan <strong>Tulisan di Atas Kue</strong></span>
                        </label>
                        <div class="mt-2">
                            <label for="custom_note_max_chars" class="kelana-label">Maksimal Karakter Tulisan</label>
                            <input id="custom_note_max_chars" name="custom_note_max_chars" type="number" min="5" max="120"
                                value="{{ old('custom_note_max_chars', 30) }}" class="kelana-input text-xs">
                        </div>
                    </div>

                    <button type="submit" class="btn-kelana-primary mt-4 w-full py-2.5 text-xs">
                        Simpan Produk Baru
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('variants-container');
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

    document.getElementById('btn-add-variant').addEventListener('click', () => {
        const idx = nextIdx++;
        const row = document.createElement('div');
        row.className = 'variant-row grid gap-2.5 rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-3 sm:grid-cols-12';
        row.innerHTML = `
            <div class="sm:col-span-5">
                <input type="text" name="variants[${idx}][name]" placeholder="Nama Varian" class="kelana-input py-1.5 text-xs">
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
