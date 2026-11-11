@extends('layouts.admin')

@section('title', 'Kelola Produk Katalog — Panel Admin')
@section('kicker', 'MANAJEMEN PRODUK & STOK')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col gap-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-serif text-xl font-bold text-[#28211c]">Daftar Produk Brownies Kelana</h1>
                <p class="mt-1 text-xs text-[#796b62]">
                    Atur harga varian, foto galeri, status Tersedia/Habis, dan badge spesial K3Mart.
                </p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="btn-kelana-primary px-4 py-2 text-xs">
                + Tambah Produk Baru
            </a>
        </div>

        {{-- Pencarian & Filter Produk --}}
        <form method="GET" action="{{ route('admin.products.index') }}"
            class="grid gap-3 rounded-2xl border border-[#e8dfd3] bg-white p-4 shadow-xs sm:grid-cols-4">
            <div>
                <label class="kelana-label">Cari Nama / Kode Produk</label>
                <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="PRD-001 atau Brownies..." class="kelana-input py-2 text-xs">
            </div>

            <div>
                <label class="kelana-label">Kategori</label>
                <select name="category_id" class="kelana-input py-2 text-xs">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="kelana-label">Status Katalog</label>
                <select name="status" class="kelana-input py-2 text-xs">
                    <option value="">Semua Status</option>
                    <option value="tersedia" @selected($statusFilter === 'tersedia')>Tersedia &amp; Aktif</option>
                    <option value="habis" @selected($statusFilter === 'habis')>Stok Habis</option>
                    <option value="nonaktif" @selected($statusFilter === 'nonaktif')>Disembunyikan (Nonaktif)</option>
                    <option value="k3mart" @selected($statusFilter === 'k3mart')>Khusus K3Mart</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn-kelana-primary flex-1 py-2 text-xs">Terapkan Filter</button>
                @if($searchKeyword || $categoryId || $statusFilter)
                    <a href="{{ route('admin.products.index') }}" class="btn-kelana-outline px-3 py-2 text-xs">Reset</a>
                @endif
            </div>
        </form>

        {{-- Tabel Produk --}}
        <div class="overflow-hidden rounded-2xl border border-[#e8dfd3] bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#efe7dc] text-left text-xs">
                    <thead class="bg-[#fdfbf6] text-[11px] font-bold text-[#936b49] uppercase">
                        <tr>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3">Kategori &amp; Badge</th>
                            <th class="px-4 py-3">Varian &amp; Harga</th>
                            <th class="px-4 py-3">Stok</th>
                            <th class="px-4 py-3">Visibilitas</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f3ece2]">
                        @forelse($products as $product)
                            <tr class="hover:bg-[#fdfbf6]">
                                <td class="px-4 py-3.5 align-top">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}"
                                            class="h-12 w-12 shrink-0 rounded-xl border border-[#e8dfd3] object-cover">
                                        <div>
                                            <p class="text-[10px] font-bold text-[#936b49]">{{ $product->product_code }}</p>
                                            <a href="{{ route('admin.products.edit', $product) }}" class="font-bold text-[#28211c] hover:text-[#52311a] hover:underline">
                                                {{ $product->name }}
                                            </a>
                                            <p class="max-w-xs truncate text-[11px] text-[#796b62]">{{ $product->tagline }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="font-semibold text-[#52311a]">{{ $product->category?->name ?? '-' }}</span>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @if($product->is_featured)
                                            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-900">Favorit</span>
                                        @endif
                                        @if($product->is_k3mart)
                                            <span class="rounded bg-sky-100 px-1.5 py-0.5 text-[10px] font-bold text-sky-900">K3Mart</span>
                                        @endif
                                        @if($product->allows_custom_note)
                                            <span class="rounded bg-[#f9eddd] px-1.5 py-0.5 text-[10px] font-bold text-[#52311a]">Custom Note</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <p class="font-bold text-[#28211c]">Mulai Rp {{ number_format($product->base_price, 0, ',', '.') }}</p>
                                    <p class="mt-0.5 text-[11px] text-[#796b62]">{{ $product->variants->count() }} varian</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <form method="POST" action="{{ route('admin.products.toggle-status', $product) }}">
                                        @csrf
                                        <input type="hidden" name="field" value="is_available">
                                        <button type="submit"
                                            class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $product->is_available ? 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' : 'bg-red-50 text-red-800 hover:bg-red-100' }}">
                                            {{ $product->is_available ? 'Tersedia' : 'Stok Habis' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <form method="POST" action="{{ route('admin.products.toggle-status', $product) }}">
                                        @csrf
                                        <input type="hidden" name="field" value="is_active">
                                        <button type="submit"
                                            class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $product->is_active ? 'bg-blue-50 text-blue-800 hover:bg-blue-100' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                            {{ $product->is_active ? 'Tampil di Toko' : 'Disembunyikan' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3.5 text-right align-top whitespace-nowrap">
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                        class="inline-flex rounded-lg border border-[#dfd2c0] px-3 py-1 text-[11px] font-bold text-[#52311a] hover:bg-[#f9eddd]">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="ml-1 inline-block"
                                        onsubmit="return confirm('Hapus produk {{ $product->name }} dari katalog? Riwayat pesanan lama tetap aman.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 px-2.5 py-1 text-[11px] font-bold text-red-700 hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-[#796b62]">
                                    Tidak ada produk yang cocok dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="border-t border-[#efe7dc] px-4 py-3">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

