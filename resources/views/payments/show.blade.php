@extends('layouts.app')

@section('title', 'Pembayaran '.$order->order_number.' — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('orders.show', $order->order_number) }}" class="text-xs font-semibold text-[#52311a] hover:underline">
                    &larr; Detail Pesanan {{ $order->order_number }}
                </a>
                <h1 class="mt-1 font-serif text-3xl font-bold text-[#52311a]">Instruksi Pembayaran</h1>
            </div>

            <div class="rounded-2xl border border-amber-300 bg-amber-50 px-4 py-2 text-right">
                <p class="text-[10px] font-bold tracking-wider text-amber-800 uppercase">Batas Waktu Pembayaran</p>
                <p id="payment-countdown" data-deadline="{{ $order->payment_deadline_at?->timestamp ?? 0 }}"
                    class="text-lg font-extrabold text-amber-900">
                    Memuat waktu...
                </p>
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-12">
            {{-- Kolom Kiri: Instruksi Kanal Pembayaran Midtrans --}}
            <div class="space-y-6 lg:col-span-7">
                <div class="rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#f0e5d6] pb-4">
                        <div>
                            <span class="text-[11px] font-bold tracking-wider text-[#936b49] uppercase">Gateway Pembayaran Resmi</span>
                            <h2 class="text-lg font-bold text-[#52311a]">{{ $payment?->channel_label ?? 'Midtrans Snap' }}</h2>
                            <p class="text-xs text-[#796b62]">ID Transaksi: {{ $payment?->midtrans_order_id ?? $order->order_number }}</p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Menunggu Pembayaran</span>
                    </div>

                    {{-- Konten Adaptif Sesuai Metode yang Dipilih --}}
                    @if($payment && in_array($payment->payment_method, ['mandiri_va', 'bca_va'], true))
                        <div class="mt-5 space-y-4 rounded-2xl border border-[#efe4d4] bg-[#fdfbf6] p-5">
                            @if($payment->payment_method === 'mandiri_va' && $payment->biller_code)
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-[11px] font-semibold text-[#796b62]">Kode Perusahaan / Biller Code</p>
                                        <p class="text-lg font-extrabold text-[#52311a]">{{ $payment->biller_code }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-semibold text-[#796b62]">Kode Bayar (Bill Key)</p>
                                        <p class="text-lg font-extrabold text-[#52311a]">{{ $payment->bill_key }}</p>
                                    </div>
                                </div>
                            @endif

                            <div>
                                <p class="text-[11px] font-semibold text-[#796b62]">Nomor Virtual Account</p>
                                <div class="mt-1 flex items-center justify-between gap-3 rounded-xl border border-[#e3d5c3] bg-white px-4 py-3">
                                    <span id="va-number-text" class="font-mono text-xl font-extrabold tracking-wider text-[#28211c]">{{ $payment->va_number }}</span>
                                    <button type="button" onclick="copyText('va-number-text', this)"
                                        class="rounded-lg bg-[#f2e4cd] px-3 py-1.5 text-xs font-bold text-[#52311a] hover:bg-[#e5d2b5]">
                                        Salin
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between border-t border-[#efe4d4] pt-3">
                                <span class="text-xs font-semibold text-[#796b62]">Total Pembayaran</span>
                                <span class="text-lg font-extrabold text-[#52311a]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3 text-xs leading-relaxed text-[#5e524a]">
                            <h3 class="font-bold text-[#52311a]">Cara Pembayaran</h3>
                            @if($payment->payment_method === 'mandiri_va')
                                <div class="rounded-xl bg-[#fdfbf6] p-4">
                                    <p class="font-bold text-[#28211c]">Melalui Livin’ by Mandiri / ATM Mandiri:</p>
                                    <ol class="mt-1.5 list-decimal space-y-1 pl-4">
                                        <li>Pilih menu <strong>Bayar</strong> &rarr; <strong>Multipayment</strong>.</li>
                                        <li>Masukkan kode penyedia jasa <strong>{{ $payment->biller_code ?: '70012' }} (Midtrans)</strong>.</li>
                                        <li>Masukkan Nomor Virtual Account <strong>{{ $payment->va_number }}</strong>.</li>
                                        <li>Pastikan nominal <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong> sesuai, lalu konfirmasi dengan PIN.</li>
                                    </ol>
                                </div>
                            @else
                                <div class="rounded-xl bg-[#fdfbf6] p-4">
                                    <p class="font-bold text-[#28211c]">Melalui BCA Mobile / ATM BCA:</p>
                                    <ol class="mt-1.5 list-decimal space-y-1 pl-4">
                                        <li>Pilih <strong>m-Transfer</strong> &rarr; <strong>BCA Virtual Account</strong>.</li>
                                        <li>Masukkan Nomor VA <strong>{{ $payment->va_number }}</strong> dan klik Send.</li>
                                        <li>Periksa detail tagihan sebesar <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>.</li>
                                        <li>Masukkan PIN m-BCA untuk menyelesaikan pembayaran secara otomatis.</li>
                                    </ol>
                                </div>
                            @endif
                        </div>

                    @elseif($payment && in_array($payment->payment_method, ['qris', 'gopay'], true))
                        <div class="mt-6 flex flex-col items-center rounded-2xl border border-[#efe4d4] bg-[#fdfbf6] p-6 text-center">
                            <p class="text-xs font-semibold text-[#5e524a]">
                                Pindai kode QR di bawah ini menggunakan aplikasi {{ $payment->payment_method === 'gopay' ? 'GoPay / Gojek' : 'GoPay, OVO, DANA, ShopeePay, atau Mobile Banking berlogo QRIS' }}.
                            </p>

                            {{-- QR Code Vector Deterministic Offline-Safe --}}
                            <div class="mt-4 rounded-2xl border-2 border-[#52311a] bg-white p-4 shadow-xs">
                                <canvas id="kelana-qris-canvas" width="210" height="210" data-seed="{{ $payment->midtrans_order_id ?? $order->order_number }}"></canvas>
                            </div>

                            <p class="mt-4 text-xs text-[#796b62]">Total Tagihan QRIS</p>
                            <p class="text-2xl font-extrabold text-[#52311a]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                        </div>

                    @else
                        <div class="mt-6 rounded-2xl border border-[#efe4d4] bg-[#fdfbf6] p-6 text-center">
                            <p class="text-sm font-bold text-[#52311a]">Popup Pembayaran Midtrans Snap</p>
                            <p class="mt-1 text-xs text-[#796b62]">
                                Klik tombol di bawah untuk membuka jendela pembayaran aman Midtrans ({{ $payment?->channel_label ?? 'Snap' }}).
                            </p>
                            @if(!empty($midtransClientKey) && $payment?->snap_token)
                                <button type="button" id="pay-with-snap-btn" class="btn-kelana-primary mt-5 rounded-full text-xs">
                                    Buka Jendela Pembayaran Midtrans &rarr;
                                </button>
                            @endif
                        </div>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-[#f0e5d6] pt-5">
                        <form method="POST" action="{{ route('payments.check-status', $order) }}">
                            @csrf
                            <button type="submit" class="btn-kelana-primary rounded-full text-xs">
                                Cek Status Pembayaran
                            </button>
                        </form>
                        <a href="{{ route('orders.show', $order->order_number) }}" class="text-xs font-semibold text-[#796b62] hover:text-[#52311a] hover:underline">
                            Kembali ke Detail Pesanan
                        </a>
                    </div>
                </div>

                {{-- Panel Simulasi Sandbox / Mode Lokal untuk Pengujian & Presentasi --}}
                @if(app()->environment('local', 'testing') || empty(config('services.midtrans.server_key')))
                    <div class="rounded-3xl border border-dashed border-[#b18950] bg-[#f9eddd]/70 p-5">
                        <h3 class="text-xs font-bold tracking-wider text-[#52311a] uppercase">Mode Simulasi Midtrans Lokal (Tanpa Key Sandbox)</h3>
                        <p class="mt-1 text-[11px] text-[#5e524a]">
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

            {{-- Kolom Kanan: Ringkasan Pesanan & Jadwal --}}
            <div class="space-y-5 lg:col-span-5">
                <div class="rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs">
                    <h3 class="text-base font-bold text-[#52311a]">Ringkasan Pemesanan</h3>
                    <div class="mt-4 space-y-2 border-b border-[#f0e5d6] pb-4 text-xs">
                        @foreach($order->items as $item)
                            <div class="flex justify-between gap-2">
                                <span>{{ $item->quantity }}&times; {{ $item->product_name }} ({{ $item->variant_name ?? 'Reguler' }})</span>
                                <span class="font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 space-y-1.5 text-xs text-[#5e524a]">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>{{ $order->delivery_type === 'pickup' ? 'Ambil Sendiri' : 'Ongkos Kirim' }}</span>
                            <span>Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-t border-[#f0e5d6] pt-2 text-sm font-extrabold text-[#52311a]">
                            <span>Total Bayar</span>
                            <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-xs text-xs">
                    <h3 class="text-sm font-bold text-[#52311a]">Detail Pesanan</h3>
                    <dl class="mt-3 space-y-2">
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#796b62]">No. Pesanan</dt>
                            <dd class="font-bold">{{ $order->order_number }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#796b62]">Jadwal</dt>
                            <dd class="font-semibold text-right">{{ $order->scheduled_date?->locale('id')->isoFormat('D MMMM YYYY') }} &bull; {{ $order->scheduled_time_slot }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#796b62]">Metode</dt>
                            <dd class="font-semibold">{{ $order->delivery_type === 'pickup' ? 'Pickup · '.$order->pickupLocation?->name : 'Pengiriman' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        @include('partials.customer-help-banner')
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
                    ctx.fillStyle = '#28211c';

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

