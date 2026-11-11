@extends('layouts.app')

@section('title', 'Kebijakan Privasi — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs sm:p-10">
            <h1 class="font-serif text-3xl font-bold text-[#52311a]">Kebijakan Privasi</h1>
            <p class="mt-2 text-xs text-[#796b62]">Dokumen ini menjelaskan pengelolaan data pelanggan pada layanan website resmi {{ $storeSetting->store_name }}.</p>

            <div class="mt-7 space-y-5 text-xs leading-relaxed text-[#5e524a]">
                <div>
                    <h2 class="text-sm font-semibold text-[#52311a]">1. Data yang Digunakan</h2>
                    <p class="mt-1">Data yang kamu berikan meliputi nama, email, nomor telepon/WhatsApp, alamat pengiriman beserta titik peta, informasi penerima, catatan khusus, dan riwayat pesanan.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#52311a]">2. Tujuan Penggunaan</h2>
                    <p class="mt-1">Data digunakan untuk mengelola akun pelanggan, memproses pemesanan H-1, menghitung estimasi ongkos kirim, mengatur pengantaran atau pengambilan, serta mengirimkan pembaruan status pesanan di dashboard.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#52311a]">3. Penyedia Pembayaran (Midtrans)</h2>
                    <p class="mt-1">Seluruh pembayaran online pada website diproses melalui gateway pembayaran resmi Midtrans (QRIS, Virtual Account, GoPay, Kartu Kredit/Debit, dan Snap). Informasi yang diperlukan untuk transaksi diteruskan secara terenkripsi kepada penyedia tersebut sesuai kebutuhan pemrosesan pembayaran.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-[#52311a]">4. Akses &amp; Kontak Pembaruan Data</h2>
                    <p class="mt-1">Kamu dapat memperbarui data akun pada halaman Profil Saya atau menghubungi {{ $storeSetting->email }} / WhatsApp CS {{ $storeSetting->whatsapp_number }} untuk pertanyaan maupun koreksi data.</p>
                </div>
            </div>
        </div>
    </section>
@endsection

