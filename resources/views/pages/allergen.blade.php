@extends('layouts.app')

@section('title', 'Informasi Alergen — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs sm:p-10">
            <h1 class="font-serif text-3xl font-bold text-[#52311a]">Informasi Alergen</h1>
            <p class="mt-2 text-sm text-[#796b62]">
                Dapur Brownies Kelana memproduksi aneka kue panggang dan kudapan menggunakan bahan utama telur, produk olahan susu, tepung terigu (gluten), cokelat, serta kacang-kacangan pada varian tertentu.
            </p>

            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-900">
                <strong>Penting:</strong> Semua produk dibuat dalam lingkungan dapur yang sama. Pelanggan dengan alergi berat terhadap susu, telur, gluten, kacang, atau makanan laut disarankan berkonsultasi terlebih dahulu via WhatsApp CS sebelum melakukan pemesanan.
            </div>

            <div class="mt-8 overflow-hidden rounded-2xl border border-[#e3d5c3]">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#f9eddd] text-[#52311a] uppercase">
                        <tr>
                            <th class="px-4 py-3 font-bold">Produk</th>
                            <th class="px-4 py-3 font-bold">Kategori</th>
                            <th class="px-4 py-3 font-bold">Kandungan Alergen Utama</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f2e4cd]">
                        @foreach($products as $product)
                            <tr class="hover:bg-[#fdfbf6]">
                                <td class="px-4 py-3 font-bold text-[#28211c]">
                                    <a href="{{ route('products.show', $product->slug) }}" class="hover:text-[#52311a] hover:underline">{{ $product->name }}</a>
                                </td>
                                <td class="px-4 py-3 text-[#796b62]">{{ $product->category?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-[#5e524a]">{{ $product->allergen_info ?: 'Telur, susu, tepung gandum (gluten)' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

