@extends('layouts.app')

@section('title', 'Cara Pemesanan — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs sm:p-10">
            <h1 class="font-serif text-3xl font-bold text-[#52311a]">Cara Pemesanan</h1>
            <p class="mt-2 text-sm text-[#796b62]">Ikuti langkah berikut untuk memesan melalui website Brownies Kelana.</p>

            <div class="mt-8 space-y-5">
                @php
                    $steps = [
                        ['1. Pilih produk dan varian', 'Buka katalog, pilih produk yang kamu sukai, lalu tentukan varian (seperti Reguler atau Premium) dan jumlahnya. Harga mengikuti varian yang dipilih.'],
                        ['2. Periksa keranjang', 'Tambahkan produk ke keranjang. Gunakan tombol tambah atau kurang untuk mengatur jumlah, hapus produk bila perlu, dan isi catatan pesanan. Pilih Lanjut Checkout.'],
                        ['3. Lengkapi checkout', 'Isi nama, email dan nomor telepon pemesan. Pilih Kirim ke alamat dan lengkapi data penerima serta alamat tujuan, atau Ambil sendiri (pick-up) dan pilih lokasi pengambilan.'],
                        ['4. Tentukan jadwal H-'.$storeSetting->preorder_min_days, 'Pemesanan menerapkan sistem Pre-order minimal H-'.$storeSetting->preorder_min_days.'. Pilih tanggal operasional yang tersedia dan slot jam pengiriman. Kuota maksimal '.$storeSetting->daily_quota.' pesanan per hari.'],
                        ['5. Selesaikan pembayaran Midtrans', 'Periksa ringkasan checkout dan pilih kanal pembayaran Midtrans (Snap, QRIS, Kartu Kredit/Debit, Mandiri VA, BCA VA, atau GoPay). Selesaikan dalam '.$storeSetting->payment_timeout_minutes.' menit setelah pesanan dibuat.'],
                        ['6. Pantau pesanan', 'Buka Dashboard untuk melihat progres pesanan (Menunggu Pembayaran -> Diproses -> Dikemas -> Dikirim/Siap Diambil), detail kurir, dan notifikasi. Saat pickup, tunjukkan nomor pesanan kepada petugas.'],
                    ];
                @endphp

                @foreach($steps as [$heading, $desc])
                    <div class="rounded-2xl border border-[#efe4d4] bg-[#fdfbf6] p-5">
                        <h2 class="text-sm font-semibold text-[#52311a]">{{ $heading }}</h2>
                        <p class="mt-1.5 text-xs leading-relaxed text-[#5e524a]">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
                <strong>Catatan Operasional:</strong> {{ $storeSetting->operatingHoursSummary() }}. Tanggal tutup dan tanggal penuh tidak dapat dipilih sebagai jadwal pesanan.
            </div>

            <div class="mt-8">
                <a href="{{ route('products.index') }}" class="btn-kelana-primary rounded-full text-xs">Jelajahi Produk &rarr;</a>
            </div>
        </div>
    </section>
@endsection

