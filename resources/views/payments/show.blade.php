@extends('layouts.app')

@section('title', 'Pembayaran '.$order->order_number.' — Brownies Kelana')

@push('head')
    @if(! empty($midtransClientKey) && $payment?->snap_token)
        <link rel="preconnect" href="{{ $isProduction ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com' }}">
        <link rel="preload" href="{{ $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" as="script">
        <script src="{{ $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
            data-client-key="{{ $midtransClientKey }}" defer></script>
    @endif
@endpush

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('orders.show', $order->order_number) }}" aria-label="Kembali ke Detail Pesanan"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-[#8f6743]/50 bg-white text-[#593822] transition hover:bg-[#f7ebd5]">
                    &larr;
                </a>
                <h1 class="font-serif text-2xl font-bold text-[#593822] sm:text-3xl">Pembayaran Midtrans</h1>
            </div>

            <div class="rounded-2xl border border-[#8f6743]/60 bg-[#f7ebd5] px-4 py-2 text-right">
                <p class="text-[10px] font-bold text-[#593822] uppercase">Sisa Waktu Pembayaran</p>
                <p id="payment-countdown" data-deadline="{{ $order->payment_deadline_at?->timestamp ?? 0 }}"
                    class="text-lg font-bold tabular-nums text-[#593822]">
                    Memuat waktu...
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            {{-- Kolom Kiri: Sesi Resmi Midtrans Snap --}}
            <div class="space-y-6 lg:col-span-7">
                <div class="kelana-card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#efe2ce] pb-4">
                        <div>
                            <span class="rounded-full bg-[#e8decb] px-3 py-0.5 text-[11px] font-bold text-[#593822]">
                                {{ $isProduction ? 'Midtrans Resmi' : 'Midtrans Sandbox' }}
                            </span>
                            <h2 class="mt-2 font-serif text-xl font-bold text-[#2f2118]">{{ $payment?->channel_label ?? 'Midtrans Snap' }}</h2>
                            <p class="mt-0.5 text-xs text-[#6f5a4a]">
                                Pesanan {{ $order->order_number }}
                                @if($payment?->midtrans_order_id)
                                    &middot; ID Transaksi {{ $payment->midtrans_order_id }}
                                @endif
                            </p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Menunggu Pembayaran</span>
                    </div>

                    @if(! empty($snapError))
                        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs text-red-800">
                            <p class="font-bold">Sesi Midtrans belum dapat dimuat</p>
                            <p class="mt-1">{{ $snapError }}</p>
                        </div>
                    @endif

                    {{-- Panel Utama Pembayaran Inline Midtrans Snap & QRIS di Halaman Langsung --}}
                    <div class="mt-6 rounded-2xl border border-[#efe2ce] bg-[#fcf3e3] p-5 text-center">
                        <p class="text-xs font-semibold text-[#6f5a4a]">Total Tagihan Pembayaran</p>
                        <p class="mt-1 text-3xl font-bold text-[#593822]">
                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-[#5e4d3e]">
                            Kode QRIS, Virtual Account, GoPay, dan metode lainnya terbuka langsung di halaman ini tanpa membuka tab/halaman baru.
                        </p>

                        <div class="mt-4 flex flex-wrap items-center justify-center gap-2.5">
                            @if(! empty($midtransClientKey) && $payment?->snap_token)
                                <button type="button" id="pay-with-snap-btn" class="btn-kelana-primary rounded-xl px-5 py-2.5 text-xs font-bold shadow-md hover:scale-[1.02] transition">
                                    Buka Pembayaran / QRIS Sekarang &rarr;
                                </button>
                            @endif

                            <form method="POST" action="{{ route('payments.check-status', $order) }}" class="inline-block">
                                @csrf
                                <button type="submit" class="btn-kelana-outline rounded-xl px-4 py-2.5 text-xs font-bold hover:bg-[#f7ebd5] transition">
                                    Cek Status Pembayaran
                                </button>
                            </form>
                        </div>

                        <p class="mt-3 text-[11px] text-[#796b62]">
                            Batas akhir pembayaran: <strong>{{ $order->payment_deadline_at?->locale('id')->isoFormat('D MMMM YYYY · HH.mm') }} WIB</strong>.
                        </p>
                    </div>

                    {{-- Kontainer Snap Embed Langsung di Halaman (Tanpa Tab Baru) --}}
                    @if(! empty($midtransClientKey) && $payment?->snap_token)
                        <div class="mt-4 overflow-hidden rounded-2xl border border-[#d9c8b4] bg-white p-3 shadow-inner">
                            <p class="mb-2 text-center text-[11px] font-bold text-[#593822]">Jendela Pembayaran Langsung</p>
                            <div id="snap-container" class="relative min-h-[460px] w-full rounded-xl bg-[#fdfbf6]">
                                <div id="snap-loading-placeholder" class="flex min-h-[420px] flex-col items-center justify-center p-6 text-center">
                                    <div class="h-10 w-10 animate-spin rounded-full border-3 border-[#d9c8b4] border-t-[#593822]"></div>
                                    <p class="mt-4 font-serif text-sm font-bold text-[#593822]">Menghubungkan ke Pembayaran Midtrans...</p>
                                    <p class="mt-1 text-xs text-[#796b62]">Memuat metode QRIS, GoPay, Virtual Account & Bank Transfer</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Rincian Kanal Resmi Apabila Sudah Dikirim oleh API Midtrans --}}
                    @if($payment && ($payment->va_number || $payment->bill_key || $payment->qris_url))
                        <div class="mt-5 rounded-2xl border border-[#d9c8b4] bg-white p-5">
                            <p class="text-xs font-bold text-[#593822]">Detail Kanal Pembayaran Resmi dari Midtrans</p>

                            @if($payment->va_number)
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] text-[#6f5a4a]">Nomor Virtual Account ({{ strtoupper($payment->va_bank ?? 'Bank') }})</p>
                                        <p id="va-number-text" class="mt-0.5 font-mono text-xl font-bold text-[#2f2118]">{{ $payment->va_number }}</p>
                                    </div>
                                    <button type="button" onclick="copyText('va-number-text', this)"
                                        class="rounded-lg border border-[#8f6743] bg-white px-4 py-1.5 text-xs font-bold text-[#593822] hover:bg-[#f7ebd5]">
                                        Salin
                                    </button>
                                </div>
                            @endif

                            @if($payment->biller_code && $payment->bill_key)
                                <div class="mt-3 space-y-1.5 border-t border-[#efe2ce] pt-3 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-[#6f5a4a]">Kode Perusahaan (Biller Code)</span>
                                        <span class="font-mono font-bold text-[#2f2118]">{{ $payment->biller_code }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-[#6f5a4a]">Kode Pembayaran (Bill Key)</span>
                                        <span id="bill-key-text" class="font-mono font-bold text-[#2f2118]">{{ $payment->bill_key }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($payment->qris_url)
                                <div class="mt-4 flex flex-col items-center text-center">
                                    <img src="{{ $payment->qris_url }}" alt="Kode QRIS Resmi Midtrans" class="h-52 w-52 rounded-xl border border-[#d9c8b4] bg-white p-2">
                                    <p class="mt-2 text-[11px] text-[#6f5a4a]">Kode QRIS resmi yang dihasilkan oleh server Midtrans.</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-[#efe2ce] pt-5">
                        <form method="POST" action="{{ route('payments.check-status', $order) }}">
                            @csrf
                            <button type="submit" class="btn-kelana-primary rounded-lg text-xs">
                                Cek Status Pembayaran
                            </button>
                        </form>
                        <a href="{{ route('orders.show', $order->order_number) }}" class="text-xs font-semibold text-[#6f5a4a] hover:text-[#593822] hover:underline">
                            Kembali ke Detail Pesanan
                        </a>
                    </div>
                </div>

                {{-- Panduan Singkat Pembayaran Otomatis Midtrans --}}
                <div class="kelana-card">
                    <div class="kelana-card-header">Alur Konfirmasi Pembayaran Otomatis</div>
                    <div class="space-y-3 p-6 text-xs leading-relaxed text-[#5e4d3e]">
                        <ol class="list-decimal space-y-1.5 pl-4">
                            <li>Klik tombol <strong>Bayar Sekarang via Midtrans Snap</strong> atau buka tautan halaman pembayaran Midtrans.</li>
                            <li>Pilih metode pembayaran yang tersedia (QRIS, GoPay, BCA VA, Mandiri Bill, atau Kartu Kredit/Debit).</li>
                            <li>Selesaikan pembayaran nominal <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong> sesuai instruksi resmi di layar Midtrans.</li>
                            <li>Setelah transaksi terkonfirmasi, status pesanan otomatis diperbarui menjadi <strong>Lunas &amp; Diproses</strong> tanpa perlu mengirim bukti transfer.</li>
                        </ol>
                        <div class="rounded-xl bg-[#f7ebd5] px-4 py-3 text-[11px] text-[#593822]">
                            &#9432; Jika status belum langsung berubah setelah membayar, tekan tombol <strong>Cek Status Pembayaran</strong> di atas untuk menyinkronkan langsung dengan server Midtrans.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Ringkasan Pemesanan & Detail Pesanan --}}
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
                            <dt class="text-[#796b62]">Dibuat</dt>
                            <dd class="text-right font-semibold text-[#593822]">{{ $order->created_at->locale('id')->isoFormat('D MMMM YYYY · HH.mm') }} WIB</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#796b62]">Batas bayar</dt>
                            <dd class="text-right font-semibold text-[#593822]">{{ $order->payment_deadline_at?->locale('id')->isoFormat('D MMMM YYYY · HH.mm') }} WIB</dd>
                        </div>
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
        @if(! empty($midtransClientKey) && $payment?->snap_token)
            <script>
                const snapToken = @json($payment->snap_token);
                const resultSuccessUrl = @json(route('payments.result', ['order' => $order, 'state' => 'berhasil']));
                const resultPendingUrl = @json(route('payments.result', ['order' => $order, 'state' => 'pending']));
                const resultErrorUrl = @json(route('payments.result', ['order' => $order, 'state' => 'gagal']));

                const snapCallbacks = {
                    onSuccess: (result) => window.location.href = resultSuccessUrl,
                    onPending: (result) => window.location.href = resultPendingUrl,
                    onError: (result) => window.location.href = resultErrorUrl,
                    onClose: () => {
                        console.log('Jendela pembayaran ditutup oleh pengguna.');
                    }
                };

                let snapEmbedded = false;

                function startSnapPayment(forcePopup = false) {
                    if (! window.snap) {
                        return false;
                    }

                    const container = document.getElementById('snap-container');
                    if (!forcePopup && container && typeof window.snap.embed === 'function') {
                        try {
                            const placeholder = document.getElementById('snap-loading-placeholder');
                            window.snap.embed(snapToken, Object.assign({ embedId: 'snap-container' }, snapCallbacks));
                            snapEmbedded = true;
                            if (placeholder) placeholder.style.display = 'none';
                            return true;
                        } catch (err) {
                            console.warn('Embed Snap gagal, beralih ke popup modal:', err);
                        }
                    }

                    // Fallback ke popup Snap modal di atas website langsung
                    if (typeof window.snap.pay === 'function') {
                        window.snap.pay(snapToken, snapCallbacks);
                        return true;
                    }

                    return false;
                }

                document.getElementById('pay-with-snap-btn')?.addEventListener('click', () => {
                    startSnapPayment(true);
                });

                // Inisialisasi secepat kilat: periksa snap.js setiap 50ms sehingga langsung aktif saat script selesai dimuat
                let attempts = 0;
                const snapCheckInterval = setInterval(() => {
                    attempts++;
                    if (window.snap) {
                        clearInterval(snapCheckInterval);
                        startSnapPayment(false);
                    } else if (attempts > 100) { // Timeout 5 detik
                        clearInterval(snapCheckInterval);
                        const placeholder = document.getElementById('snap-loading-placeholder');
                        if (placeholder) {
                            placeholder.innerHTML = `
                                <div class="p-4 text-center">
                                    <p class="text-xs font-bold text-[#593822]">Memuat embed sedikit lambat di perangkat Anda?</p>
                                    <button type="button" onclick="startSnapPayment(true)" class="btn-kelana-primary mt-3 rounded-xl px-5 py-2.5 text-xs font-bold">
                                        Klik untuk Buka Pembayaran Popup Midtrans &rarr;
                                    </button>
                                </div>
                            `;
                        }
                    }
                }, 50);
            </script>
        @endif

        <script>
            function copyText(elementId, buttonEl) {
                const text = document.getElementById(elementId)?.textContent?.trim() || '';
                if (!text) return;
                navigator.clipboard?.writeText(text);
                const original = buttonEl.textContent;
                buttonEl.textContent = 'Tersalin!';
                setTimeout(() => buttonEl.textContent = original, 1500);
            }

            document.addEventListener('DOMContentLoaded', () => {
                const countdownEl = document.getElementById('payment-countdown');
                if (countdownEl) {
                    const deadline = parseInt(countdownEl.dataset.deadline || '0', 10);
                    const tick = () => {
                        const nowSec = Math.floor(Date.now() / 1000);
                        const diff = Math.max(0, deadline - nowSec);
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
