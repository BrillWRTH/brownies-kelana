@extends('layouts.app')

@section('title', 'Syarat dan Ketentuan — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs sm:p-10">
            <h1 class="font-serif text-3xl font-bold text-[#52311a]">Syarat dan Ketentuan</h1>
            <p class="mt-2 text-xs text-[#796b62]">Ketentuan pemesanan resmi melalui website {{ $storeSetting->store_name }}.</p>

            <div class="mt-7 space-y-5 text-xs leading-relaxed text-[#5e524a]">
                <div>
                    <h2 class="text-sm font-semibold text-[#52311a]">1. Pemesanan melalui Website</h2>
                    <p class="mt-1">Pilih produk, varian, dan jumlah, lalu periksa harga serta catatan pada keranjang. Pastikan data pemesan, penerima, dan lokasi tujuan pada halaman checkout sudah benar sebelum melanjutkan pembayaran.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#52311a]">2. Sistem Pre-order, Kuota &amp; Jadwal</h2>
                    <p class="mt-1">Pemesanan menggunakan sistem pre-order minimal H-{{ $storeSetting->preorder_min_days }} dengan kuota maksimal {{ $storeSetting->daily_quota }} pesanan per hari. Hari operasional: {{ $storeSetting->operatingHoursSummary() }}. Tanggal tutup dan tanggal penuh tidak dapat dipilih sebagai jadwal.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#52311a]">3. Pengiriman dan Pengambilan</h2>
                    <p class="mt-1">Pelanggan dapat memilih pengiriman ke alamat (dengan perhitungan ongkos kirim sesuai pengaturan toko aktif) atau ambil sendiri (pick-up) gratis ongkir di titik lokasi resmi kami dengan menunjukkan nomor pesanan.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#52311a]">4. Pembayaran Midtrans &amp; Batas Waktu</h2>
                    <p class="mt-1">Pembayaran online diproses melalui Midtrans dan wajib diselesaikan dalam batas waktu {{ $storeSetting->payment_timeout_minutes }} menit sejak pesanan dibuat. Sesi pembayaran yang kedaluwarsa akan ditutup otomatis oleh sistem dan tidak dapat digunakan kembali.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#52311a]">5. Pembatalan sebelum Diproses</h2>
                    <p class="mt-1">Pelanggan dapat membatalkan pesanan langsung melalui halaman Detail Pesanan selama status masih Menunggu Pembayaran (belum diproses). Untuk pesanan yang sudah diproses, hubungi WhatsApp CS {{ $storeSetting->whatsapp_number }}.</p>
                </div>
            </div>
        </div>
    </section>
@endsection

