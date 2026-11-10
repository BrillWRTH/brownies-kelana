@extends('layouts.app')

@section('title', 'Detail Pesanan '.$order->order_number.' — Brownies Kelana')

@section('content')
    @php
        $timelineSteps = $order->delivery_type === 'pickup'
            ? [
                'menunggu_pembayaran' => 'Menunggu Pembayaran',
                'diproses' => 'Diproses',
                'dikemas' => 'Dikemas',
                'siap_diambil' => 'Siap Diambil',
                'sudah_diambil' => 'Sudah Diambil',
            ]
            : [
                'menunggu_pembayaran' => 'Menunggu Pembayaran',
                'diproses' => 'Diproses',
                'dikemas' => 'Dikemas',
                'dalam_pengiriman' => 'Dalam Pengiriman',
                'sampai_tujuan' => 'Sampai Tujuan',
            ];
        $orderedKeys = array_keys($timelineSteps);
        $activeStepIdx = array_search($order->order_status, $orderedKeys, true);
        $isTerminated = in_array($order->order_status, ['dibatalkan', 'kedaluwarsa'], true);
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('partials.customer-sidebar')

            <div class="min-w-0 flex-1 space-y-6">
                <div>
                    <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-[#52311a] hover:underline">&larr; Kembali ke Dashboard</a>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 class="font-serif text-2xl font-bold text-[#52311a]">Pesanan {{ $order->order_number }}</h1>
                            <p class="text-xs text-[#796b62]">
                                {{ $order->customer_name }} &middot; {{ $order->source === 'whatsapp' ? 'WhatsApp' : 'Website' }} &middot;
                                {{ $order->delivery_type === 'pickup' ? 'Ambil Sendiri (Pick-up)' : 'Pengiriman' }} &middot;
                                {{ $order->items->sum('quantity') }} item
                            </p>
                            <p class="mt-0.5 text-[11px] text-[#8d7d70]">
                                Dipesan {{ $order->created_at->locale('id')->isoFormat('D MMMM YYYY · HH:mm') }} WIB
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full {{ $order->payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} px-3 py-1 text-xs font-bold">
                                {{ $order->paymentStatusLabel() }}
                            </span>
                            <span class="rounded-full {{ $isTerminated ? 'bg-red-100 text-red-700' : 'bg-[#52311a] text-white' }} px-3 py-1 text-xs font-bold">
                                {{ $order->orderStatusLabel() }}
                            </span>
                        </div>
                    </div>
                </div>

                @if($isTerminated)
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs text-red-900">
                        <p class="font-bold">Pesanan {{ $order->orderStatusLabel() }}</p>
                        <p class="mt-0.5">Alasan: {{ $order->cancellation_reason ?: 'Batas waktu pembayaran berakhir.' }}</p>
                    </div>
                @endif

                <div class="grid gap-6 lg:grid-cols-12">
                    {{-- Kiri: Detail Produk, Penerima/Pickup & Pembayaran --}}
                    <div class="space-y-6 lg:col-span-7">
                        {{-- Detail Produk --}}
                        <div class="overflow-hidden rounded-2xl border border-[#e3d5c3] bg-white shadow-xs">
                            <div class="bg-[#52311a] px-5 py-3 text-xs font-bold tracking-wider text-white uppercase">
                                Detail Produk
                            </div>
                            <div class="divide-y divide-[#f2e4cd] p-5">
                                @foreach($order->items as $item)
                                    <div class="flex items-start justify-between gap-3 py-2.5 first:pt-0">
                                        <div>
                                            <p class="text-sm font-bold text-[#28211c]">{{ $item->product_name }}</p>
                                            <p class="text-xs text-[#796b62]">{{ $item->quantity }}&times; {{ $item->variant_name ?? 'Reguler' }} @ Rp {{ number_format($item->unit_price, 0, ',', '.') }}</p>
                                            @if($item->custom_note)
                                                <p class="mt-0.5 text-[11px] font-semibold text-[#936b49]">Tulisan kue: “{{ $item->custom_note }}”</p>
                                            @endif
                                        </div>
                                        <span class="text-sm font-bold text-[#52311a]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach

                                <div class="space-y-1.5 pt-3 text-xs text-[#5e524a]">
                                    <div class="flex justify-between">
                                        <span>Subtotal Produk</span>
                                        <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>{{ $order->delivery_type === 'pickup' ? 'Biaya Pengambilan (Pick-up)' : 'Ongkos Kirim' }}</span>
                                        <span>Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between pt-1.5 text-sm font-extrabold text-[#52311a]">
                                        <span>Total</span>
                                        <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Informasi Penerima / Pickup & Kurir --}}
                        <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs">
                            <h2 class="text-sm font-bold text-[#52311a]">
                                {{ $order->delivery_type === 'pickup' ? 'Informasi Pengambilan (Pick-up)' : 'Informasi Penerima & Pengiriman' }}
                            </h2>

                            <dl class="mt-3 space-y-2 text-xs">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-[#796b62]">{{ $order->delivery_type === 'pickup' ? 'Pengambil' : 'Penerima' }}</dt>
                                    <dd class="font-bold text-[#28211c]">{{ $order->recipient_name }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-[#796b62]">No. HP</dt>
                                    <dd class="font-semibold">{{ $order->recipient_phone }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-[#796b62]">Jadwal</dt>
                                    <dd class="font-bold text-[#52311a]">{{ $order->scheduled_date?->locale('id')->isoFormat('D MMMM YYYY') }} &middot; {{ $order->scheduled_time_slot }}</dd>
                                </div>
                                @if($order->delivery_type === 'pickup' && $order->pickupLocation)
                                    <div class="rounded-xl bg-[#f9eddd] p-3">
                                        <p class="font-bold text-[#52311a]">{{ $order->pickupLocation->name }}</p>
                                        <p class="mt-0.5 text-[11px] text-[#5e524a]">{{ $order->pickupLocation->address }}</p>
                                        @if($order->pickupLocation->maps_url)
                                            <a href="{{ $order->pickupLocation->maps_url }}" target="_blank" rel="noopener" class="mt-1.5 inline-block text-[11px] font-bold text-[#52311a] underline">
                                                Lihat lokasi di Google Maps &nearr;
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <div>
                                        <dt class="text-[#796b62]">Alamat Tujuan</dt>
                                        <dd class="mt-0.5 font-medium text-[#28211c]">{{ $order->delivery_address }}</dd>
                                        @if($order->delivery_address_detail)
                                            <dd class="mt-0.5 text-[11px] text-[#796b62]">Patokan: {{ $order->delivery_address_detail }}</dd>
                                        @endif
                                        @if($order->maps_url)
                                            <a href="{{ $order->maps_url }}" target="_blank" rel="noopener" class="mt-1 inline-block text-[11px] font-bold text-[#52311a] underline">
                                                Lihat titik di Google Maps &nearr;
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </dl>

                            @if($order->courier_name || $order->delivery_proof_path || $order->received_by)
                                <div class="mt-4 rounded-xl border border-[#efe4d4] bg-[#fdfbf6] p-4 text-xs">
                                    <h3 class="font-bold text-[#52311a]">Informasi Kurir &amp; Bukti Serah Terima</h3>
                                    @if($order->courier_name)
                                        <p class="mt-1 text-[#28211c]">
                                            Kurir: <strong>{{ $order->courier_name }}</strong>
                                            @if($order->courier_vehicle_plate) ({{ $order->courier_vehicle_plate }}) @endif
                                            @if($order->courier_phone) &middot; {{ $order->courier_phone }} @endif
                                        </p>
                                    @endif
                                    @if($order->received_by)
                                        <p class="mt-0.5 text-emerald-800">
                                            Diterima oleh: <strong>{{ $order->received_by }}</strong>
                                            @if($order->delivered_at) pada {{ $order->delivered_at->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB @endif
                                        </p>
                                    @endif
                                    @if($order->delivery_proof_path)
                                        <div class="mt-3">
                                            <p class="mb-1 text-[11px] font-semibold text-[#796b62]">Foto Bukti Pengiriman:</p>
                                            <img src="{{ asset($order->delivery_proof_path) }}" alt="Bukti Pengiriman" class="h-36 w-full max-w-xs rounded-xl border border-[#e3d5c3] object-cover">
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <p class="mt-3 text-[11px] text-[#8d7d70]">
                                Perubahan data penerima atau jadwal setelah checkout ditangani melalui WhatsApp CS Brownies Kelana.
                            </p>
                        </div>

                        {{-- Kartu Metode Pembayaran Midtrans --}}
                        <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs">
                            <h2 class="text-sm font-bold text-[#52311a]">Metode Pembayaran</h2>
                            <dl class="mt-3 space-y-1.5 text-xs">
                                <div class="flex justify-between">
                                    <dt class="text-[#796b62]">Kanal Pembayaran</dt>
                                    <dd class="font-bold text-[#28211c]">{{ $order->latestPayment?->channel_label ?? 'Midtrans Snap' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-[#796b62]">Nominal</dt>
                                    <dd class="font-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-[#796b62]">Status Pembayaran</dt>
                                    <dd class="font-bold">{{ $order->paymentStatusLabel() }}</dd>
                                </div>
                                @if($order->paid_at)
                                    <div class="flex justify-between">
                                        <dt class="text-[#796b62]">Waktu Lunas</dt>
                                        <dd>{{ $order->paid_at->locale('id')->isoFormat('D MMMM YYYY · HH:mm') }} WIB</dd>
                                    </div>
                                @elseif($order->payment_deadline_at)
                                    <div class="flex justify-between">
                                        <dt class="text-[#796b62]">Batas Pembayaran</dt>
                                        <dd>{{ $order->payment_deadline_at->locale('id')->isoFormat('D MMMM YYYY · HH:mm') }} WIB</dd>
                                    </div>
                                @endif
                            </dl>

                            @if($order->payment_status === 'belum_dibayar' && ! $isTerminated)
                                <a href="{{ route('payments.show', $order) }}" class="btn-kelana-primary mt-4 w-full rounded-full text-xs">
                                    Bayar Sekarang via Midtrans &rarr;
                                </a>
                            @elseif($order->isCompleted())
                                <a href="{{ route('products.index') }}" class="btn-kelana-outline mt-4 w-full rounded-full text-xs">
                                    Pesan Lagi di Katalog
                                </a>
                            @endif
                        </div>

                        {{-- Bagian Ulasan Pelanggan (Muncul Setelah Selesai) --}}
                        @if($order->isCompleted())
                            <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs">
                                <h2 class="text-sm font-bold text-[#52311a]">Ulasan Pesanan</h2>
                                @if($order->review)
                                    <div class="mt-3 rounded-xl bg-[#fdfbf6] p-4 text-xs">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-[#52311a]">Rating kamu: {{ $order->review->rating }} dari 5</span>
                                            <span class="text-amber-500">{{ str_repeat('★', $order->review->rating) }}</span>
                                        </div>
                                        <p class="mt-2 text-[#28211c]">“{{ $order->review->comment }}”</p>
                                        @if($order->review->admin_reply)
                                            <p class="mt-2 rounded-lg bg-[#f9eddd] p-2.5 text-[#52311a]">
                                                <strong>Balasan Brownies Kelana:</strong> {{ $order->review->admin_reply }}
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('orders.reviews.store', $order) }}" class="mt-3 space-y-4">
                                        @csrf
                                        <div>
                                            <label class="kelana-label">Pilih Rating Bintang *</label>
                                            <div class="flex gap-2">
                                                @for($star = 1; $star <= 5; $star++)
                                                    <label class="cursor-pointer">
                                                        <input type="radio" name="rating" value="{{ $star }}" class="peer sr-only" {{ $star === 5 ? 'checked' : '' }}>
                                                        <span class="inline-flex rounded-xl border border-[#dfd2c0] bg-[#fdfbf6] px-3 py-1.5 text-xs font-bold text-[#52311a] peer-checked:border-[#52311a] peer-checked:bg-[#52311a] peer-checked:text-white">
                                                            {{ $star }} &#9733;
                                                        </span>
                                                    </label>
                                                @endfor
                                            </div>
                                        </div>
                                        <div>
                                            <label for="comment" class="kelana-label">Ceritakan Pengalamanmu *</label>
                                            <textarea id="comment" name="comment" rows="3" required
                                                placeholder="Bagaimana rasa kue dan pelayanan pengiriman Brownies Kelana?"
                                                class="kelana-input"></textarea>
                                        </div>
                                        <button type="submit" class="btn-kelana-primary rounded-full text-xs">Kirim Ulasan</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Kanan: Timeline Progres Status & Aksi Pembatalan --}}
                    <div class="space-y-6 lg:col-span-5">
                        <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs">
                            <h2 class="text-sm font-bold text-[#52311a]">Status Pesanan</h2>

                            @if(! $isTerminated)
                                <ol class="mt-4 space-y-3 border-l-2 border-[#e3d5c3] pl-4">
                                    @foreach($orderedKeys as $idx => $stepKey)
                                        @php $reached = $activeStepIdx !== false && $idx <= $activeStepIdx; @endphp
                                        <li class="relative">
                                            <span class="absolute -left-[22px] top-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full {{ $reached ? 'bg-[#52311a]' : 'bg-[#e3d5c3]' }}"></span>
                                            <p class="text-xs font-bold {{ $reached ? 'text-[#52311a]' : 'text-[#8d7d70]' }}">
                                                {{ $timelineSteps[$stepKey] }}
                                            </p>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif

                            @if($order->logs->isNotEmpty())
                                <div class="mt-6 border-t border-[#f0e5d6] pt-4">
                                    <h3 class="text-xs font-bold tracking-wider text-[#936b49] uppercase">Catatan Aktivitas</h3>
                                    <div class="mt-3 space-y-3">
                                        @foreach($order->logs as $log)
                                            <div class="rounded-xl bg-[#fdfbf6] p-3 text-xs">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-bold text-[#28211c]">{{ $log->title }}</span>
                                                    <span class="text-[10px] text-[#8d7d70]">{{ $log->created_at->locale('id')->isoFormat('D MMM, HH:mm') }}</span>
                                                </div>
                                                @if($log->description)
                                                    <p class="mt-1 text-[11px] text-[#5e524a]">{{ $log->description }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if($order->canBeCancelledByCustomer())
                            <div class="rounded-2xl border border-red-200 bg-white p-5 shadow-xs">
                                <p class="text-xs text-[#5e524a]">
                                    Pesanan masih dapat dibatalkan sebelum diproses oleh dapur. Setelah diproses, hubungi WhatsApp CS kami.
                                </p>
                                <button type="button" data-open-modal="cancel-order-modal"
                                    class="mt-3 w-full rounded-full bg-red-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-red-700">
                                    Batalkan Pesanan
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                @include('partials.customer-help-banner')
            </div>
        </div>
    </section>

    {{-- Modal Konfirmasi Batalkan Pesanan --}}
    @if($order->canBeCancelledByCustomer())
        <div id="cancel-order-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-3xl border border-[#e3d5c3] bg-white p-6 shadow-2xl">
                <h3 class="font-serif text-xl font-bold text-[#52311a]">Batalkan Pesanan {{ $order->order_number }}</h3>
                <p class="mt-1 text-xs text-[#796b62]">Total tagihan: <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>. Tindakan ini tidak dapat dibatalkan.</p>

                <form method="POST" action="{{ route('orders.cancel', $order) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="kelana-label">Pilih Alasan Pembatalan *</label>
                        <div class="space-y-2 text-xs text-[#28211c]">
                            @foreach(['Salah memilih produk', 'Ingin mengubah pesanan', 'Berubah pikiran', 'Pesanan terlalu lama', 'Lainnya'] as $idx => $reasonOpt)
                                <label class="flex items-center gap-2 rounded-lg border border-[#efe4d4] px-3 py-2">
                                    <input type="radio" name="cancellation_reason" value="{{ $reasonOpt }}" {{ $idx === 0 ? 'checked' : '' }} class="text-[#52311a]">
                                    <span>{{ $reasonOpt }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="custom_reason" class="kelana-label">Keterangan Tambahan (jika memilih Lainnya)</label>
                        <input id="custom_reason" name="custom_reason" type="text" placeholder="Tuliskan alasan..." class="kelana-input">
                    </div>

                    <label class="flex items-start gap-2 text-xs text-[#5e524a]">
                        <input type="checkbox" name="confirm_cancel" value="1" required class="mt-0.5 rounded text-red-600">
                        <span>Saya yakin ingin membatalkan pesanan {{ $order->order_number }} ini.</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" data-close-modal="cancel-order-modal" class="btn-kelana-outline rounded-full text-xs">Kembali</button>
                        <button type="submit" class="rounded-full bg-red-600 px-5 py-2 text-xs font-bold text-white hover:bg-red-700">
                            Konfirmasi Pembatalan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

