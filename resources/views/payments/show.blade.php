@extends('layouts.app')

@section('title', 'Pembayaran '.$order->order_number.' — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('orders.show', $order->order_number) }}" aria-label="Kembali ke Detail Pesanan"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-[#8f6743]/50 bg-white text-[#593822] transition hover:bg-[#f7ebd5]">
                    &larr;
                </a>
                <h1 class="font-serif text-2xl font-bold text-[#593822] sm:text-3xl">Instruksi Pembayaran</h1>
            </div>

            <div class="rounded-2xl border border-[#8f6743]/60 bg-[#f7ebd5] px-4 py-2 text-right">
                <p class="text-[10px] font-bold tracking-wider text-[#593822] uppercase">Sisa Waktu Pembayaran</p>
                <p id="payment-countdown" data-deadline="{{ $order->payment_deadline_at?->timestamp ?? 0 }}"
                    class="text-lg font-extrabold text-[#593822]">
                    Memuat waktu...
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            {{-- Kolom Kiri: Instruksi Kanal Pembayaran Midtrans --}}
            <div class="space-y-6 lg:col-span-7">
                <div class="kelana-card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#efe2ce] pb-4">
                        <div>
                            <span class="rounded-full bg-[#e8decb] px-3 py-0.5 text-[11px] font-bold text-[#593822]">Pending</span>
                            <h2 class="mt-2 text-xl font-bold text-[#2f2118]">{{ $payment?->channel_label ?? 'Midtrans Snap' }}</h2>
                            <p class="mt-0.5 text-xs text-[#6f5a4a]">
                                Pesanan {{ $order->order_number }} &middot; {{ $payment?->midtrans_order_id ?? 'Midtrans Snap' }}
                            </p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Menunggu Pembayaran</span>
                    </div>

                    {{-- Konten Adaptif Sesuai Metode yang Dipilih --}}
                    @if($payment && in_array($payment->payment_method, ['mandiri_va', 'bca_va'], true))
                        <div class="mt-5 space-y-4">
                            <div>
                                <p class="text-xs font-semibold text-[#6f5a4a]">Nomor Virtual Account (VA)</p>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <span id="va-number-text" class="font-mono text-2xl font-extrabold tracking-wider text-[#2f2118] sm:text-3xl">{{ $payment->va_number }}</span>
                                    <button type="button" onclick="copyText('va-number-text', this)"
                                        class="rounded-lg border border-[#8f6743] bg-white px-4 py-1.5 text-xs font-bold text-[#593822] hover:bg-[#f7ebd5]">
                                        Salin
                                    </button>
                                </div>
                            </div>

                            @if($payment->payment_method === 'mandiri_va' && $payment->biller_code)
                                <div class="space-y-2 border-t border-[#efe2ce] pt-3 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-[#6f5a4a]">Kode perusahaan</span>
                                        <span class="font-bold text-[#2f2118]">{{ $payment->biller_code }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-[#6f5a4a]">Kode pembayaran</span>
                                        <span class="font-bold text-[#2f2118]">{{ $payment->bill_key }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-center justify-between border-t border-[#efe2ce] pt-3">
                                <span class="text-sm font-bold text-[#2f2118]">Total pembayaran</span>
                                <span class="text-xl font-extrabold text-[#593822]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>

                            <div class="rounded-xl bg-[#f7ebd5] px-4 py-3 text-xs text-[#593822]">
                                &#9432; Batas pembayaran {{ $order->payment_deadline_at?->locale('id')->isoFormat('D MMMM YYYY · HH.mm') }} WIB. Nomor VA ini hanya untuk pesanan {{ $order->order_number }}.
                            </div>
                        </div>

                    @elseif($payment && in_array($payment->payment_method, ['qris', 'gopay'], true))
                        <div class="mt-6 flex flex-col items-center rounded-2xl border border-[#efe2ce] bg-[#fcf3e3] p-6 text-center">
                            <p class="text-xs font-semibold text-[#5e4d3e]">
                                Pindai kode QR di bawah ini menggunakan aplikasi {{ $payment->payment_method === 'gopay' ? 'GoPay / Gojek' : 'GoPay, OVO, DANA, ShopeePay, atau Mobile Banking berlogo QRIS' }}.
                            </p>

                            {{-- QR Code Vector Deterministic Offline-Safe --}}
                            <div class="mt-4 rounded-2xl border-2 border-[#593822] bg-white p-4 shadow-xs">
                                <canvas id="kelana-qris-canvas" width="210" height="210" data-seed="{{ $payment->midtrans_order_id ?? $order->order_number }}"></canvas>
                            </div>

                            <p class="mt-4 text-xs text-[#6f5a4a]">Total Tagihan QRIS</p>
                            <p class="text-2xl font-extrabold text-[#593822]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                        </div>

                    @else
                        <div class="mt-6 rounded-2xl border border-[#efe2ce] bg-[#fcf3e3] p-6 text-center">
                            <p class="text-sm font-bold text-[#593822]">Popup Pembayaran Midtrans Snap</p>
                            <p class="mt-1 text-xs text-[#6f5a4a]">
                                Klik tombol di bawah untuk membuka jendela pembayaran aman Midtrans ({{ $payment?->channel_label ?? 'Snap' }}).
                            </p>
                            @if(!empty($midtransClientKey) && $payment?->snap_token)
                                <button type="button" id="pay-with-snap-btn" class="btn-kelana-primary mt-5 rounded-lg text-xs">
                                    Buka Jendela Pembayaran Midtrans &rarr;
                                </button>
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

                {{-- Kartu Cara Pembayaran dengan Header Cokelat Tua --}}
                @if($payment && in_array($payment->payment_method, ['mandiri_va', 'bca_va'], true))
                    <div class="kelana-card">
                        <div class="kelana-card-header">Cara Pembayaran</div>
                        <div class="space-y-4 p-6 text-xs leading-relaxed text-[#5e4d3e]">
                            @if($payment->payment_method === 'mandiri_va')
                                <div>
                                    <p class="text-sm font-bold text-[#2f2118]">Melalui ATM Mandiri</p>
                                    <ol class="mt-2 list-decimal space-y-1.5 pl-4">
                                        <li>Masukkan kartu ATM dan PIN Mandiri.</li>
                                        <li>Pilih Bayar/Beli &rarr; Lainnya &rarr; Multi Payment.</li>
                                        <li>Masukkan Kode Perusahaan <strong>{{ $payment->biller_code ?: '70012' }}</strong>, lalu masukkan Nomor Virtual Account <strong>{{ $payment->va_number }}</strong>.</li>
                                        <li>Periksa tagihan <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>, konfirmasi dan simpan struk.</li>
                                    </ol>
                                </div>
                                <div class="border-t border-[#efe2ce] pt-4">
                                    <p class="text-sm font-bold text-[#2f2118]">Melalui Livin’ by Mandiri</p>
                                    <ol class="mt-2 list-decimal space-y-1.5 pl-4">
                                        <li>Masuk ke Livin’ by Mandiri, pilih Bayar &rarr; Multipayment.</li>
                                        <li>Masukkan Kode Perusahaan <strong>{{ $payment->biller_code ?: '70012' }}</strong>, lalu masukkan Kode Pembayaran <strong>{{ $payment->bill_key ?: $payment->va_number }}</strong>.</li>
                                        <li>Periksa rincian tagihan <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>, lalu konfirmasi dengan PIN.</li>
                                    </ol>
                                </div>
                            @else
                                <div>
                                    <p class="text-sm font-bold text-[#2f2118]">Melalui BCA Mobile / ATM BCA</p>
                                    <ol class="mt-2 list-decimal space-y-1.5 pl-4">
                                        <li>Pilih <strong>m-Transfer</strong> &rarr; <strong>BCA Virtual Account</strong>.</li>
                                        <li>Masukkan Nomor VA <strong>{{ $payment->va_number }}</strong> dan klik Send.</li>
                                        <li>Periksa detail tagihan sebesar <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>.</li>
                                        <li>Masukkan PIN m-BCA untuk menyelesaikan pembayaran secara otomatis.</li>
                                    </ol>
                                </div>
                            @endif

                            <div class="rounded-xl bg-[#f7ebd5] px-4 py-3 text-[11px] text-[#593822]">
                                &#9432; Status pembayaran diperbarui otomatis setelah pembayaran berhasil. Tidak perlu mengirim bukti transfer atau menunggu verifikasi admin.
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Panel Simulasi Sandbox / Mode Lokal untuk Pengujian & Presentasi --}}
                @if(app()->environment('local', 'testing') || empty(config('services.midtrans.server_key')))
                    <div class="rounded-2xl border border-dashed border-[#8f6743] bg-[#f7ebd5]/80 p-5">
                        <h3 class="text-xs font-bold tracking-wider text-[#593822] uppercase">Mode Simulasi Midtrans Lokal (Tanpa Key Sandbox)</h3>
                        <p class="mt-1 text-[11px] text-[#5e4d3e]">
                            Gunakan tombol di bawah untuk menguji alur notifikasi status pembayaran Midtrans secara langsung saat demo website:
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('payments.simulate', $order) }}">
                                @csrf
                                <input type="hidden" name="outcome" value="berhasil">
                                <button type="submit" class="rounded-lg bg-emerald-700 px-3.5 py-2 text-xs font-bold text-white hover:bg-emerald-800">
                                    Simulasi Berhasil (Lunas)
                                </button>
                            </form>
                            <form method="POST" action="{{ route('payments.simulate', $order) }}">
                                @csrf
                                <input type="hidden" name="outcome" value="pending">
                                <button type="submit" class="rounded-lg bg-amber-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-amber-700">
                                    Simulasi Pending
                                </button>
                            </form>
                            <form method="POST" action="{{ route('payments.simulate', $order) }}">
                                @csrf
                                <input type="hidden" name="outcome" value="gagal">
                                <button type="submit" class="rounded-lg bg-red-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-red-700">
                                    Simulasi Gagal
                                </button>
                            </form>
                            <form method="POST" action="{{ route('payments.simulate', $order) }}">
                                @csrf
                                <input type="hidden" name="outcome" value="kedaluwarsa">
                                <button type="submit" class="rounded-lg bg-slate-700 px-3.5 py-2 text-xs font-bold text-white hover:bg-slate-800">
                                    Simulasi Kedaluwarsa
                                </button>
                            </form>
                            <form method="POST" action="{{ route('payments.simulate', $order) }}">
                                @csrf
                                <input type="hidden" name="outcome" value="dibatalkan">
                                <button type="submit" class="rounded-lg border border-red-300 bg-white px-3.5 py-2 text-xs font-bold text-red-700 hover:bg-red-50">
                                    Batalkan Pembayaran
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
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
                            <div class="flex justify-between border-t border-[#efe2ce] pt-3 text-base font-extrabold text-[#593822]">
                                <span>Total Bayar</span>
                                <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kelana-card p-6 text-xs">
                    <h3 class="text-base font-bold text-[#2f2118]">Detail Pesanan</h3>
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
                    &#9432; Pre-order H-1 &middot; {{ $order->delivery_type === 'pickup' ? 'Pickup · '.$order->pickupLocation?->name : 'Pengiriman oleh kurir resmi Kelana.' }}
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
        @if(!empty($midtransClientKey) && $payment?->snap_token)
            <script src="{{ $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
                data-client-key="{{ $midtransClientKey }}"></script>
            <script>
                document.getElementById('pay-with-snap-btn')?.addEventListener('click', () => {
                    window.snap?.pay(@json($payment->snap_token), {
                        onSuccess: () => window.location.href = @json(route('payments.result', ['order' => $order, 'state' => 'berhasil'])),
                        onPending: () => window.location.href = @json(route('payments.result', ['order' => $order, 'state' => 'pending'])),
                        onError: () => window.location.href = @json(route('payments.result', ['order' => $order, 'state' => 'gagal'])),
                    });
                });
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
                // Countdown Timer Batas Bayar 30 Menit
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

                // Render pola QR code deterministik yang tajam pada canvas QRIS
                const canvas = document.getElementById('kelana-qris-canvas');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    const seedStr = canvas.dataset.seed || 'KELANA';
                    const size = 21;
                    const cell = canvas.width / size;
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.fillStyle = '#2f2118';

                    const drawFinder = (ox, oy) => {
                        for (let y = 0; y < 7; y++) {
                            for (let x = 0; x < 7; x++) {
                                if (x === 0 || x === 6 || y === 0 || y === 6 || (x >= 2 && x <= 4 && y >= 2 && y <= 4)) {
                                    ctx.fillRect((ox + x) * cell, (oy + y) * cell, cell, cell);
                                }
                            }
                        }
                    };
                    drawFinder(0, 0);
                    drawFinder(size - 7, 0);
                    drawFinder(0, size - 7);

                    let hash = 0;
                    for (let i = 0; i < seedStr.length; i++) {
                        hash = ((hash << 5) - hash) + seedStr.charCodeAt(i);
                        hash |= 0;
                    }

                    for (let y = 0; y < size; y++) {
                        for (let x = 0; x < size; x++) {
                            const inFinder = (x < 8 && y < 8) || (x >= size - 8 && y < 8) || (x < 8 && y >= size - 8);
                            if (inFinder) continue;
                            hash = (hash * 1664525 + 1013904223) | 0;
                            if ((hash & 3) < 2) {
                                ctx.fillRect(x * cell, y * cell, cell, cell);
                            }
                        }
                    }
                }
            });
        </script>
    @endpush
@endsection
