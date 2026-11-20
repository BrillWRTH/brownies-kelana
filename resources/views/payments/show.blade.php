@extends('layouts.app')

@section('title', 'Pembayaran QRIS '.$order->order_number.' — Brownies Kelana')

@section('content')
    @php
        $isWaitingVerification = $order->payment_status === 'menunggu_verifikasi';
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumb Navigasi Halaman Pembayaran --}}
        @include('partials.breadcrumb', ['items' => ['Dashboard' => route('dashboard'), 'Pembayaran' => null, $order->order_number => null]])

        {{-- Kejelasan Pengecekan Manual & WhatsApp Sesuai Permintaan --}}
        <div class="mb-6 rounded-2xl border border-amber-300 bg-amber-50 p-4 sm:p-5 text-amber-900 shadow-xs">
            <div class="flex items-start gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-500 text-white font-bold text-sm">!</span>
                <div class="flex-1 text-xs">
                    <p class="font-bold text-sm text-amber-950">Pengecekan Pembayaran Dilakukan Secara Manual</p>
                    <p class="mt-1 leading-relaxed text-amber-900">
                        Pengecekan bukti pembayaran dilakukan secara manual oleh tim Brownies Kelana. <strong>Mohon bersabar</strong>, atau silakan hubungi nomor WhatsApp yang tercantum jika membutuhkan konfirmasi segera.
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-2.5">
                        <a href="{{ $sellerWhatsappUrl }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition">
                            <span>Hubungi WhatsApp Penjual ({{ $storeSetting->whatsapp_number }})</span> &rarr;
                        </a>
                        <a href="{{ route('dashboard') }}#riwayat-pesanan"
                            class="inline-flex items-center gap-1 rounded-lg border border-amber-600/40 bg-white px-3 py-1.5 text-xs font-bold text-amber-900 hover:bg-amber-100/50 transition">
                            <span>Cek di Dashboard Saya</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('orders.show', $order->order_number) }}" aria-label="Kembali ke Detail Pesanan"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-[#8f6743]/50 bg-white text-[#593822] transition hover:bg-[#f7ebd5]">
                    &larr;
                </a>
                <div>
                    <h1 class="font-serif text-2xl font-bold text-[#593822] sm:text-3xl">Pembayaran QRIS</h1>
                    <p class="text-xs text-[#6f5a4a]">Scan QRIS Brownies Kelana, lampirkan bukti bayar, lalu tunggu verifikasi admin.</p>
                </div>
            </div>

            @if($isWaitingVerification)
                <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-2 text-right">
                    <p class="text-[10px] font-bold text-blue-800 uppercase">Status Pembayaran</p>
                    <p class="text-sm font-bold text-blue-900">Menunggu Verifikasi Admin</p>
                </div>
            @else
                <div class="rounded-2xl border border-[#8f6743]/60 bg-[#f7ebd5] px-4 py-2 text-right">
                    <p class="text-[10px] font-bold text-[#593822] uppercase">Sisa Waktu Pembayaran</p>
                    <p id="payment-countdown" data-deadline="{{ $order->payment_deadline_at?->timestamp ?? 0 }}"
                        class="text-lg font-bold tabular-nums text-[#593822]">
                        Memuat waktu...
                    </p>
                </div>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            {{-- Kolom Kiri: QRIS Resmi, Form Lampirkan Bukti Bayar & Hubungi Penjual --}}
            <div class="space-y-6 lg:col-span-7">

                {{-- Notifikasi Bukti Ditolak oleh Admin --}}
                @if($payment?->rejection_reason)
                    <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-xs text-red-900">
                        <p class="font-bold">Bukti pembayaran sebelumnya ditolak oleh Admin</p>
                        <p class="mt-1">Alasan: <strong>{{ $payment->rejection_reason }}</strong></p>
                        <p class="mt-1 text-[11px] text-red-700">Silakan unggah ulang bukti pembayaran yang jelas dan sesuai nominal tagihan, atau hubungi penjual via WhatsApp.</p>
                    </div>
                @endif

                {{-- Notifikasi Bukti Sudah Diunggah --}}
                @if($isWaitingVerification)
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-900">
                        <p class="font-bold">&#10003; Bukti pembayaran QRIS kamu sudah diterima!</p>
                        <p class="mt-1 leading-relaxed text-blue-800">
                            Admin Brownies Kelana sedang memeriksa mutasi masuk dan akan memverifikasi pesananmu secara manual. Kamu juga bisa klik <strong>Hubungi Penjual via WhatsApp</strong> untuk mempercepat konfirmasi.
                        </p>
                    </div>
                @endif

                {{-- Kartu 1: Scan QRIS Brownies Kelana --}}
                <div class="kelana-card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#efe2ce] pb-4">
                        <div>
                            <span class="rounded-full bg-[#e8decb] px-3 py-0.5 text-[11px] font-bold text-[#593822]">
                                QRIS Resmi Brownies Kelana
                            </span>
                            <h2 class="mt-2 font-serif text-xl font-bold text-[#2f2118]">BROWNIES KELANA</h2>
                            <p class="mt-0.5 text-xs text-[#6f5a4a]">
                                NMID: <strong class="font-mono text-[#593822]">ID1025429266147</strong> &middot; Pesanan {{ $order->order_number }}
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $isWaitingVerification ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $order->paymentStatusLabel() }}
                        </span>
                    </div>

                    {{-- Nominal Tagihan & Tombol Salin --}}
                    <div class="mt-5 rounded-2xl border border-[#efe2ce] bg-[#fcf3e3] p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold text-[#6f5a4a]">Total Nominal yang Harus Dibayar</p>
                                <p class="mt-0.5 text-2xl font-bold tabular-nums text-[#593822] sm:text-3xl">
                                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                </p>
                            </div>
                            <button type="button" id="copy-amount-btn" data-amount="{{ (int) $order->total_amount }}"
                                class="rounded-xl border border-[#8f6743] bg-white px-4 py-2 text-xs font-bold text-[#593822] transition hover:bg-[#f7ebd5]">
                                Salin Nominal
                            </button>
                        </div>
                        <p class="mt-2 text-[11px] text-[#796b62]">
                            Pastikan nominal yang dibayarkan <strong>sama persis</strong> hingga digit terakhir agar verifikasi lebih cepat.
                        </p>
                    </div>

                    {{-- Gambar QRIS Brownies Kelana --}}
                    <div class="mt-5 flex flex-col items-center rounded-2xl border border-[#d9c8b4] bg-[#fdfbf6] p-5 text-center">
                        <div class="w-full max-w-sm overflow-hidden rounded-2xl border border-[#e8decb] bg-white shadow-sm">
                            <img src="{{ asset($qrisImagePath) }}"
                                alt="QRIS Resmi Brownies Kelana NMID ID1025429266147"
                                class="h-auto w-full object-contain">
                        </div>

                        <p class="mt-3 text-xs font-semibold text-[#593822]">
                            Menerima pembayaran QRIS dari semua bank &amp; e-wallet (BCA, Mandiri, BRI, BNI, GoPay, OVO, DANA, ShopeePay, LinkAja)
                        </p>

                        <div class="mt-4 flex flex-wrap items-center justify-center gap-2.5">
                            <a href="{{ asset($qrisImagePath) }}" download="QRIS-Brownies-Kelana-{{ $order->order_number }}.jpg"
                                class="btn-kelana-outline rounded-xl px-4 py-2 text-xs font-bold">
                                Unduh Gambar QRIS
                            </a>
                            <a href="{{ $sellerWhatsappUrl }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-800">
                                <span>Hubungi Penjual (WhatsApp)</span>
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                </svg>
                            </a>
                        </div>

                        @if(! $isWaitingVerification && $order->payment_deadline_at)
                            <p class="mt-3 text-[11px] text-[#796b62]">
                                Batas akhir pembayaran: <strong>{{ $order->payment_deadline_at->locale('id')->isoFormat('D MMMM YYYY · HH.mm') }} WIB</strong>.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Kartu 2: Lampirkan Bukti Bayar --}}
                <div class="kelana-card" id="bukti-bayar">
                    <div class="kelana-card-header flex items-center justify-between">
                        <span>Lampirkan Bukti Pembayaran</span>
                        @if($payment?->proof_path)
                            <span class="rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold text-white">Sudah Diunggah</span>
                        @endif
                    </div>

                    <div class="p-6 space-y-5">
                        @if($payment?->proof_path)
                            <div class="rounded-2xl border border-[#d9c8b4] bg-[#fcf3e3]/60 p-4">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <a href="{{ asset($payment->proof_path) }}" target="_blank" rel="noopener" class="shrink-0">
                                        <img src="{{ asset($payment->proof_path) }}" alt="Bukti Pembayaran QRIS"
                                            class="h-36 w-36 rounded-xl border border-[#d9c8b4] bg-white object-cover shadow-xs transition hover:opacity-90">
                                    </a>
                                    <div class="flex-1 space-y-1.5 text-xs">
                                        <p class="font-bold text-[#593822]">Bukti Pembayaran Tersimpan</p>
                                        <p class="text-[11px] text-[#6f5a4a]">
                                            Diunggah: <strong>{{ $payment->proof_uploaded_at?->locale('id')->isoFormat('D MMMM YYYY · HH.mm') }} WIB</strong>
                                        </p>
                                        @if($payment->sender_name)
                                            <p class="text-[11px] text-[#5e4d3e]">Pengirim / Akun: <strong>{{ $payment->sender_name }}</strong></p>
                                        @endif
                                        @if($payment->customer_payment_note)
                                            <p class="text-[11px] text-[#5e4d3e]">Catatan: {{ $payment->customer_payment_note }}</p>
                                        @endif
                                        <a href="{{ asset($payment->proof_path) }}" target="_blank" rel="noopener"
                                            class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-[#593822] underline">
                                            <span>Lihat ukuran penuh</span> &nearr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('payments.upload-proof', $order) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div>
                                <label for="payment_proof" class="kelana-label">
                                    {{ $payment?->proof_path ? 'Unggah Ulang Foto / Screenshot Bukti Bayar (Opsional)' : 'Foto / Screenshot Bukti Pembayaran QRIS *' }}
                                </label>
                                <input id="payment_proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp" required
                                    class="kelana-input py-2 text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-[#593822] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white hover:file:bg-[#432917]">
                                <p class="mt-1 text-[11px] text-[#796b62]">Format JPG, PNG, atau WEBP (maks. 5MB). Pastikan nominal dan status berhasil terlihat jelas.</p>
                                @error('payment_proof')
                                    <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p>
                                @enderror

                                <div id="proof-preview-wrap" class="mt-3 hidden">
                                    <p class="mb-1 text-[11px] font-semibold text-[#593822]">Pratinjau bukti yang akan dikirim:</p>
                                    <img id="proof-preview-img" src="#" alt="Pratinjau Bukti Bayar"
                                        class="max-h-56 rounded-xl border border-[#d9c8b4] bg-white object-contain p-1">
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="sender_name" class="kelana-label">Nama Pengirim / Aplikasi Bank / E-Wallet</label>
                                    <input id="sender_name" name="sender_name" type="text"
                                        value="{{ old('sender_name', $payment?->sender_name) }}"
                                        placeholder="Contoh: BCA a.n. Sinta / GoPay"
                                        class="kelana-input text-xs">
                                </div>
                                <div>
                                    <label for="customer_payment_note" class="kelana-label">Catatan Tambahan (Opsional)</label>
                                    <input id="customer_payment_note" name="customer_payment_note" type="text"
                                        value="{{ old('customer_payment_note', $payment?->customer_payment_note) }}"
                                        placeholder="Contoh: Sudah bayar via Livin Mandiri"
                                        class="kelana-input text-xs">
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                <button type="submit" class="btn-kelana-primary rounded-xl px-6 py-2.5 text-xs font-bold">
                                    {{ $payment?->proof_path ? 'Perbarui Bukti Pembayaran' : 'Kirim Bukti Pembayaran' }}
                                </button>

                                <a href="{{ $sellerWhatsappUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 hover:underline">
                                    <span>Kirim Bukti / Konfirmasi via WhatsApp Penjual</span> &nearr;
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Kartu 3: Panduan Singkat Pembayaran QRIS --}}
                <div class="kelana-card">
                    <div class="kelana-card-header">Cara Bayar &amp; Konfirmasi QRIS</div>
                    <div class="space-y-3 p-6 text-xs leading-relaxed text-[#5e4d3e]">
                        <ol class="list-decimal space-y-1.5 pl-4">
                            <li>Buka aplikasi mobile banking atau e-wallet pilihanmu (BCA Mobile, Livin' by Mandiri, BRImo, BNI, GoPay, OVO, DANA, ShopeePay, dll.).</li>
                            <li>Scan gambar <strong>QRIS BROWNIES KELANA (NMID: ID1025429266147)</strong> di atas atau pilih dari galeri setelah mengunduh gambar QRIS.</li>
                            <li>Masukkan nominal pembayaran tepat sebesar <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong> dan selesaikan transaksi.</li>
                            <li>Screenshot bukti transaksi berhasil, lalu unggah pada form <strong>Lampirkan Bukti Pembayaran</strong> di atas (atau klik <strong>Hubungi Penjual via WhatsApp</strong>).</li>
                            <li>Admin Brownies Kelana akan memverifikasi pembayaran secara manual, lalu status pesananmu berubah menjadi <strong>Lunas &amp; Diproses</strong>.</li>
                        </ol>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#efe2ce] pt-4">
                            <form method="POST" action="{{ route('payments.check-status', $order) }}">
                                @csrf
                                <button type="submit" class="btn-kelana-outline rounded-lg px-4 py-2 text-xs font-bold">
                                    Cek Status Verifikasi
                                </button>
                            </form>
                            <a href="{{ route('orders.show', $order->order_number) }}" class="text-xs font-semibold text-[#6f5a4a] hover:text-[#593822] hover:underline">
                                Kembali ke Detail Pesanan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Ringkasan Pemesanan, Detail Pesanan & Hubungi Penjual --}}
            <div class="space-y-5 lg:col-span-5">
                <div class="kelana-card">
                    <div class="kelana-card-header">Ringkasan Pemesanan</div>
                    <div class="p-6">
                        <div class="space-y-2.5 border-b border-[#efe2ce] pb-4 text-xs">
                            @foreach($order->items as $item)
                                <div class="flex justify-between gap-2">
                                    <div>
                                        <p class="font-bold text-[#2f2118]">{{ $item->quantity }}&times; {{ $item->product_name }}</p>
                                        <p class="text-[11px] text-[#796b62]">{{ $item->variant_name ?? 'Reguler' }}</p>
                                    </div>
                                    <span class="font-bold text-[#593822]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 space-y-2 text-xs text-[#5e4d3e]">
                            <div class="flex justify-between">
                                <span>Subtotal &middot; {{ $order->items->sum('quantity') }} item</span>
                                <span class="font-semibold">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>{{ $order->delivery_type === 'pickup' ? 'Ambil Sendiri' : 'Ongkos Kirim' }}</span>
                                <span class="font-semibold">Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between border-t border-[#efe2ce] pt-3 text-base font-bold text-[#593822]">
                                <span>Total Bayar</span>
                                <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kartu Hubungi Penjual Langsung --}}
                <div class="kelana-card border-emerald-200 bg-emerald-50/40 p-5 text-xs">
                    <h3 class="font-serif text-base font-bold text-emerald-900">Hubungi Penjual</h3>
                    <p class="mt-1 leading-relaxed text-emerald-800">
                        Butuh konfirmasi cepat setelah transfer QRIS atau ingin bertanya soal pesanan <strong>{{ $order->order_number }}</strong>? Chat langsung ke WhatsApp resmi Brownies Kelana.
                    </p>
                    <p class="mt-2 text-[11px] font-semibold text-emerald-900">
                        WhatsApp Penjual: {{ $storeSetting->whatsapp_number }}
                    </p>
                    <a href="{{ $sellerWhatsappUrl }}" target="_blank" rel="noopener"
                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-800">
                        <span>Chat Penjual via WhatsApp</span>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                        </svg>
                    </a>
                </div>

                <div class="kelana-card p-6 text-xs">
                    <h3 class="font-serif text-base font-semibold text-[#2f2118]">Detail Pesanan</h3>
                    <dl class="mt-4 space-y-2.5">
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#796b62]">Nomor pesanan</dt>
                            <dd class="font-bold text-[#593822]">{{ $order->order_number }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#796b62]">Akun</dt>
                            <dd class="font-semibold text-[#593822]">{{ $order->customer_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#796b62]">Penerima</dt>
                            <dd class="font-semibold text-[#593822]">{{ $order->recipient_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#796b62]">Metode bayar</dt>
                            <dd class="font-bold text-[#593822]">QRIS Brownies Kelana</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#796b62]">Dibuat</dt>
                            <dd class="text-right font-semibold text-[#593822]">{{ $order->created_at->locale('id')->isoFormat('D MMMM YYYY · HH.mm') }} WIB</dd>
                        </div>
                        @if($order->payment_deadline_at)
                            <div class="flex justify-between gap-3">
                                <dt class="text-[#796b62]">Batas bayar</dt>
                                <dd class="text-right font-semibold text-[#593822]">{{ $order->payment_deadline_at->locale('id')->isoFormat('D MMMM YYYY · HH.mm') }} WIB</dd>
                            </div>
                        @endif
                    </dl>
                    <p class="mt-4 border-t border-[#efe2ce] pt-3 text-[11px] text-[#796b62]">
                        Jadwal {{ $order->delivery_type === 'pickup' ? 'ambil' : 'kirim' }}:
                        {{ $order->scheduled_date?->locale('id')->isoFormat('D MMMM YYYY') }} &middot; {{ $order->scheduled_time_slot }}
                    </p>
                </div>

                <div class="rounded-2xl bg-[#f7ebd5] px-4 py-3 text-xs text-[#593822]">
                    &#9432; Pre-order H-{{ $storeSetting->preorder_min_days }} &middot; {{ $order->delivery_type === 'pickup' ? 'Pickup · '.$order->pickupLocation?->name : 'Pengiriman oleh kurir resmi Kelana.' }}
                </div>

                <a href="{{ route('orders.show', $order->order_number) }}"
                    class="btn-kelana-outline w-full rounded-xl text-xs">
                    Lihat Detail Pesanan
                </a>
            </div>
        </div>

        <div class="mt-10">
            @include('partials.customer-help-banner')
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // 1. Tombol Salin Nominal Pembayaran
                const copyAmountBtn = document.getElementById('copy-amount-btn');
                if (copyAmountBtn) {
                    copyAmountBtn.addEventListener('click', () => {
                        const amount = copyAmountBtn.dataset.amount || '';
                        if (!amount) return;
                        navigator.clipboard?.writeText(amount).then(() => {
                            const original = copyAmountBtn.textContent;
                            copyAmountBtn.textContent = 'Nominal Tersalin ✓';
                            window.Toast?.fire({
                                icon: 'success',
                                title: 'Nominal pembayaran berhasil disalin!'
                            });
                            setTimeout(() => copyAmountBtn.textContent = original, 1800);
                        });
                    });
                }

                // 2. Pratinjau Foto Bukti Pembayaran Sebelum Diunggah
                const proofInput = document.getElementById('payment_proof');
                const previewWrap = document.getElementById('proof-preview-wrap');
                const previewImg = document.getElementById('proof-preview-img');
                if (proofInput && previewWrap && previewImg) {
                    proofInput.addEventListener('change', () => {
                        const file = proofInput.files?.[0];
                        if (!file) {
                            previewWrap.classList.add('hidden');
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            previewImg.src = e.target.result;
                            previewWrap.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    });
                }

                // 3. Hitung Mundur Batas Pembayaran (Jika Belum Unggah Bukti)
                const countdownEl = document.getElementById('payment-countdown');
                if (countdownEl) {
                    const deadlineUnix = parseInt(countdownEl.dataset.deadline || '0', 10);
                    const tick = () => {
                        const diff = deadlineUnix - Math.floor(Date.now() / 1000);
                        if (diff <= 0) {
                            countdownEl.textContent = '00:00';
                            window.location.href = @json(route('payments.result', ['order' => $order, 'state' => 'kedaluwarsa']));
                            return;
                        }
                        const mins = String(Math.floor(diff / 60)).padStart(2, '0');
                        const secs = String(diff % 60).padStart(2, '0');
                        countdownEl.textContent = `${mins}:${secs}`;
                    };
                    tick();
                    setInterval(tick, 1000);
                }
            });
        </script>
    @endpush
@endsection
