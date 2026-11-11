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
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" aria-label="Kembali ke Dashboard"
                        class="flex h-8 w-8 items-center justify-center rounded-full border border-[#8f6743]/50 bg-white text-[#593822] transition hover:bg-[#f7ebd5]">
                        &larr;
                    </a>
                    <h1 class="font-serif text-2xl font-bold text-[#593822] sm:text-3xl">Detail Pesanan</h1>
                </div>

                @if($isTerminated)
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs text-red-900">
                        <p class="font-bold">Pesanan {{ $order->orderStatusLabel() }}</p>
                        <p class="mt-0.5">Alasan: {{ $order->cancellation_reason ?: 'Batas waktu pembayaran berakhir.' }}</p>
                    </div>
                @endif

                <div class="grid gap-6 lg:grid-cols-12">
                    {{-- Kiri: Ringkasan Pesanan, Detail Produk, Informasi Penerima, Pembayaran --}}
                    <div class="space-y-6 lg:col-span-6">
                        {{-- Kartu Header Pesanan --}}
                        <div class="kelana-card p-5">
                            <h2 class="text-xl font-bold text-[#2f2118]">Pesanan #{{ $order->order_number }}</h2>
                            <p class="mt-1 text-xs text-[#6f5a4a]">
                                {{ $order->customer_name }} &middot; {{ $order->source === 'whatsapp' ? 'WhatsApp' : 'Website' }} &middot;
                                {{ $order->delivery_type === 'pickup' ? 'Ambil Sendiri' : 'Pengiriman' }} &middot;
                                {{ $order->items->sum('quantity') }} item
                            </p>
                            <p class="mt-0.5 text-[11px] text-[#8f7968]">
                                Dipesan {{ $order->created_at->locale('id')->isoFormat('D MMMM YYYY · HH:mm') }} WIB
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full {{ $order->payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} px-3 py-0.5 text-[11px] font-bold">
                                    {{ $order->paymentStatusLabel() }}
                                </span>
                                <span class="rounded-full {{ $isTerminated ? 'bg-red-100 text-red-700' : 'bg-[#f7ebd5] text-[#593822]' }} px-3 py-0.5 text-[11px] font-bold">
                                    {{ $order->orderStatusLabel() }}
                                </span>
                            </div>

                            <p class="mt-3 text-xs font-medium text-[#2f2118]">
                                Jadwal {{ $order->delivery_type === 'pickup' ? 'ambil' : 'kirim' }}:
                                <strong>{{ $order->scheduled_date?->locale('id')->isoFormat('D MMMM YYYY') }} &middot; {{ $order->scheduled_time_slot }}</strong>
                            </p>
                        </div>

                        {{-- Kartu Detail Produk dengan Header Cokelat Tua --}}
                        <div class="kelana-card">
                            <div class="kelana-card-header">Detail Produk</div>
                            <div class="divide-y divide-[#efe2ce] p-5">
                                @foreach($order->items as $item)
                                    <div class="flex items-start justify-between gap-3 py-2.5 first:pt-0">
                                        <div>
                                            <p class="text-xs font-bold text-[#2f2118]">{{ $item->quantity }}&times; {{ $item->product_name }} &middot; {{ $item->variant_name ?? 'Reguler' }}</p>
                                            <p class="text-[11px] text-[#796b62]">@ Rp {{ number_format($item->unit_price, 0, ',', '.') }}</p>
                                            @if($item->custom_note)
                                                <p class="mt-0.5 text-[11px] font-semibold text-[#8f6743]">Tulisan kue: “{{ $item->custom_note }}”</p>
                                            @endif
                                        </div>
                                        <span class="text-xs font-bold text-[#593822]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach

                                <div class="space-y-1.5 pt-3 text-xs text-[#5e4d3e]">
                                    <div class="flex justify-between">
                                        <span>Subtotal &middot; {{ $order->items->sum('quantity') }} item</span>
                                        <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>{{ $order->delivery_type === 'pickup' ? 'Ambil sendiri' : 'Ongkos kirim' }}</span>
                                        <span>Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between pt-2 text-base font-bold text-[#593822]">
                                        <span>Total</span>
                                        <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Informasi Penerima / Pickup --}}
                        <div class="kelana-card p-5">
                            <h2 class="font-serif text-base font-semibold text-[#2f2118]">
                                {{ $order->delivery_type === 'pickup' ? 'Informasi Pengambilan (Pick-up)' : 'Informasi Penerima' }}
                            </h2>

                            <dl class="mt-4 space-y-2.5 text-xs">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-[#796b62]">Nama</dt>
                                    <dd class="font-bold text-[#593822]">{{ $order->recipient_name }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-[#796b62]">No. HP</dt>
                                    <dd class="font-semibold text-[#593822]">{{ $order->recipient_phone }}</dd>
                                </div>

                                @if($order->delivery_type === 'pickup' && $order->pickupLocation)
                                    <div class="rounded-xl bg-[#f7ebd5] p-3">
                                        <p class="font-bold text-[#593822]">{{ $order->pickupLocation->name }}</p>
                                        <p class="mt-0.5 text-[11px] text-[#5e4d3e]">{{ $order->pickupLocation->address }}</p>
                                        @if($order->pickupLocation->maps_url)
                                            <a href="{{ $order->pickupLocation->maps_url }}" target="_blank" rel="noopener" class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-bold text-[#593822] underline">
                                                <span>Lihat lokasi di Google Maps</span>
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <div class="pt-1">
                                        <dt class="font-bold text-[#2f2118]">Alamat tujuan</dt>
                                        <dd class="mt-1 leading-relaxed text-[#5e4d3e]">{{ $order->delivery_address }}</dd>
                                        @if($order->delivery_address_detail)
                                            <dd class="mt-0.5 text-[11px] text-[#796b62]">{{ $order->delivery_address_detail }}</dd>
                                        @endif
                                        @if($order->maps_url)
                                            <a href="{{ $order->maps_url }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-[11px] font-bold text-[#593822] underline">
                                                <span>Lihat lokasi di Google Maps</span>
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </dl>

                            <p class="mt-4 border-t border-[#efe2ce] pt-3 text-[11px] text-[#8f7968]">
                                Perubahan data penerima atau jadwal setelah checkout ditangani melalui WhatsApp CS Brownies Kelana.
                            </p>
                        </div>

                        {{-- Kartu Metode Pembayaran --}}
                        <div class="kelana-card p-5">
                            <h2 class="font-serif text-base font-semibold text-[#2f2118]">Metode Pembayaran</h2>
                            <p class="mt-2 text-xs font-bold text-[#593822]">{{ $order->latestPayment?->channel_label ?? 'Midtrans Snap' }}</p>

                            @if($order->payment_status === 'lunas')
                                <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs text-emerald-900">
                                    <p class="font-bold">&#10003; Pembayaran Berhasil &middot; {{ $order->paymentStatusLabel() }}</p>
                                    <p class="mt-0.5 text-[11px]">
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        @if($order->paid_at)
                                            &middot; {{ $order->paid_at->locale('id')->isoFormat('D MMMM YYYY · HH:mm') }} WIB
                                        @endif
                                    </p>
                                </div>
                            @else
                                <div class="mt-3 rounded-xl border border-[#d9c8b4] bg-[#f7ebd5] p-3.5 text-xs text-[#593822]">
                                    <p class="font-bold">Status Pembayaran: {{ $order->paymentStatusLabel() }}</p>
                                    @if($order->payment_deadline_at)
                                        <p class="mt-0.5 text-[11px]">Batas bayar: {{ $order->payment_deadline_at->locale('id')->isoFormat('D MMMM YYYY · HH:mm') }} WIB</p>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-4">
                                @if($order->payment_status === 'belum_dibayar' && ! $isTerminated)
                                    <a href="{{ route('payments.show', $order) }}" class="btn-kelana-primary w-full rounded-lg text-xs">
                                        Bayar Sekarang via Midtrans &rarr;
                                    </a>
                                @else
                                    <a href="{{ route('payments.show', $order) }}" class="btn-kelana-outline w-full rounded-lg text-xs">
                                        Lihat Detail Pembayaran
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Kanan: Timeline Ikon Status Pesanan Vertikal, Informasi Kurir & Ulasan --}}
                    <div class="space-y-6 lg:col-span-6">
                        <div class="kelana-card p-5">
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="flex items-center gap-2 font-serif text-base font-semibold text-[#2f2118]">
                                    Status Pesanan
                                </h2>
                                <span class="rounded-full bg-[#e8decb] px-3 py-0.5 text-[11px] font-bold text-[#593822]">
                                    {{ $order->orderStatusLabel() }}
                                </span>
                            </div>

                            @if(! $isTerminated)
                                <div class="mt-5 space-y-5">
                                    @foreach($orderedKeys as $idx => $stepKey)
                                        @php
                                            $reached = $activeStepIdx !== false && $idx <= $activeStepIdx;
                                            $isLast = $loop->last;
                                        @endphp
                                        <div class="relative flex gap-4">
                                            @if(! $isLast)
                                                <span class="absolute left-5 top-10 -bottom-5 w-1 {{ $reached && $idx < $activeStepIdx ? 'bg-[#593822]' : 'bg-[#e8decb]' }}"></span>
                                            @endif
                                            <div class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $reached ? 'bg-[#593822] text-white' : 'bg-[#e8decb] text-[#796b62]' }}">
                                                @if($idx === 0)
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                @elseif($idx === 1)
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                                                    </svg>
                                                @elseif($idx === 2)
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                                    </svg>
                                                @elseif($idx === 3)
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                                    </svg>
                                                @else
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 pt-1">
                                                <p class="text-xs font-bold {{ $reached ? 'text-[#2f2118]' : 'text-[#8f7968]' }}">
                                                    {{ $timelineSteps[$stepKey] }}
                                                </p>
                                                @if($reached)
                                                    <div class="mt-1.5 rounded-lg bg-[#e8decb] px-3 py-2 text-[11px] text-[#593822]">
                                                        {{ $loop->first ? 'Pesanan dibuat melalui checkout.' : ($idx === $activeStepIdx ? 'Status pesanan saat ini.' : 'Tahap telah diselesaikan.') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($order->logs->isNotEmpty())
                                <div class="mt-6 border-t border-[#efe2ce] pt-4">
                                    <h3 class="text-xs font-bold text-[#8f6743] uppercase">Catatan Aktivitas</h3>
                                    <div class="mt-3 space-y-2.5">
                                        @foreach($order->logs as $log)
                                            <div class="rounded-xl bg-[#e8decb]/70 p-3 text-xs">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-bold text-[#2f2118]">{{ $log->title }}</span>
                                                    <span class="text-[10px] text-[#6f5a4a]">{{ $log->created_at->locale('id')->isoFormat('D MMM, HH:mm') }}</span>
                                                </div>
                                                @if($log->description)
                                                    <p class="mt-1 text-[11px] text-[#5e4d3e]">{{ $log->description }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Informasi Kurir & Bukti Pengiriman --}}
                        @if($order->courier_name || $order->delivery_proof_path || $order->received_by)
                            <div class="kelana-card p-5">
                                <h2 class="font-serif text-base font-semibold text-[#2f2118]">Informasi Kurir</h2>
                                <dl class="mt-3 space-y-2 text-xs">
                                    @if($order->courier_name)
                                        <div class="flex justify-between">
                                            <dt class="text-[#796b62]">Kurir</dt>
                                            <dd class="font-bold text-[#593822]">{{ $order->courier_name }}</dd>
                                        </div>
                                    @endif
                                    @if($order->courier_phone)
                                        <div class="flex justify-between">
                                            <dt class="text-[#796b62]">No. HP kurir</dt>
                                            <dd class="font-semibold text-[#593822]">{{ $order->courier_phone }}</dd>
                                        </div>
                                    @endif
                                    @if($order->courier_vehicle_plate)
                                        <div class="flex justify-between">
                                            <dt class="text-[#796b62]">Plat kendaraan</dt>
                                            <dd class="font-semibold text-[#593822]">{{ $order->courier_vehicle_plate }}</dd>
                                        </div>
                                    @endif
                                    @if($order->received_by)
                                        <div class="flex justify-between border-t border-[#efe2ce] pt-2">
                                            <dt class="text-[#796b62]">Diterima oleh</dt>
                                            <dd class="font-bold text-emerald-700">{{ $order->received_by }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                @if($order->delivery_proof_path)
                                    <div class="mt-4 rounded-xl bg-[#e8decb] p-3">
                                        <p class="mb-2 text-[11px] font-bold text-[#593822]">Foto bukti pengiriman tersedia</p>
                                        <img src="{{ asset($order->delivery_proof_path) }}" alt="Bukti Pengiriman" class="h-32 w-full rounded-lg object-cover">
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Bagian Ulasan Pelanggan (Muncul Setelah Selesai) --}}
                        @if($order->isCompleted())
                            <div class="rounded-2xl border border-[#8f6743] bg-[#e8decb] p-5">
                                <h2 class="font-serif text-base font-semibold text-[#2f2118]">Ulasan Pesanan</h2>
                                @if($order->review)
                                    <div class="mt-3 rounded-xl bg-white p-4 text-xs">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-[#593822]">Rating kamu: {{ $order->review->rating }} dari 5</span>
                                            <span class="text-[#d4ad37]">{{ str_repeat('★', $order->review->rating) }}</span>
                                        </div>
                                        <p class="mt-2 text-[#2f2118]">“{{ $order->review->comment }}”</p>
                                        @if($order->review->admin_reply)
                                            <p class="mt-2 rounded-lg bg-[#f7ebd5] p-2.5 text-[#593822]">
                                                <strong>Balasan Brownies Kelana:</strong> {{ $order->review->admin_reply }}
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('orders.reviews.store', $order) }}" class="mt-3 space-y-4">
                                        @csrf
                                        <div>
                                            <label class="kelana-label">Bagaimana pengalamanmu menikmati Brownies Kelana?</label>
                                            <div class="mt-2 flex gap-2">
                                                @for($star = 1; $star <= 5; $star++)
                                                    <label class="cursor-pointer">
                                                        <input type="radio" name="rating" value="{{ $star }}" class="peer sr-only" {{ $star === 5 ? 'checked' : '' }}>
                                                        <span class="inline-flex rounded-xl border border-[#8f6743]/50 bg-white px-3 py-1.5 text-xs font-bold text-[#593822] peer-checked:border-[#593822] peer-checked:bg-[#593822] peer-checked:text-white">
                                                            {{ $star }} &#9733;
                                                        </span>
                                                    </label>
                                                @endfor
                                            </div>
                                        </div>
                                        <div>
                                            <label for="comment" class="kelana-label">Ceritakan Pengalamanmu *</label>
                                            <textarea id="comment" name="comment" rows="3" required
                                                placeholder="Ceritakan pengalamanmu..."
                                                class="kelana-input"></textarea>
                                        </div>
                                        <button type="submit" class="btn-kelana-primary w-full rounded-full text-xs">Kirim Ulasan</button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if($order->canBeCancelledByCustomer())
                            <div class="rounded-2xl border border-red-200 bg-white p-5 shadow-xs">
                                <p class="text-xs text-[#5e4d3e]">
                                    Pesanan masih dapat dibatalkan sebelum diproses oleh dapur. Setelah diproses, hubungi WhatsApp CS kami.
                                </p>
                                <button type="button" data-open-modal="cancel-order-modal"
                                    class="mt-3 w-full rounded-lg bg-red-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-red-700">
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
            <div class="w-full max-w-md rounded-3xl border border-[#8f6743] bg-[#fcf3e3] p-6 shadow-2xl">
                <h3 class="font-serif text-xl font-bold text-[#593822]">Batalkan Pesanan {{ $order->order_number }}</h3>
                <p class="mt-1 text-xs text-[#6f5a4a]">Total tagihan: <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>. Tindakan ini tidak dapat dibatalkan.</p>

                <form method="POST" action="{{ route('orders.cancel', $order) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="kelana-label">Pilih Alasan Pembatalan *</label>
                        <div class="space-y-2 text-xs text-[#2f2118]">
                            @foreach(['Salah memilih produk', 'Ingin mengubah pesanan', 'Berubah pikiran', 'Pesanan terlalu lama', 'Lainnya'] as $idx => $reasonOpt)
                                <label class="flex items-center gap-2 rounded-lg border border-[#d9c8b4] bg-white px-3 py-2">
                                    <input type="radio" name="cancellation_reason" value="{{ $reasonOpt }}" {{ $idx === 0 ? 'checked' : '' }} class="text-[#593822]">
                                    <span>{{ $reasonOpt }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="custom_reason" class="kelana-label">Keterangan Tambahan (jika memilih Lainnya)</label>
                        <input id="custom_reason" name="custom_reason" type="text" placeholder="Tuliskan alasan..." class="kelana-input">
                    </div>

                    <label class="flex items-start gap-2 text-xs text-[#5e4d3e]">
                        <input type="checkbox" name="confirm_cancel" value="1" required class="mt-0.5 rounded text-red-600">
                        <span>Saya yakin ingin membatalkan pesanan {{ $order->order_number }} ini.</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" data-close-modal="cancel-order-modal" class="btn-kelana-outline rounded-lg text-xs">Kembali</button>
                        <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-xs font-bold text-white hover:bg-red-700">
                            Konfirmasi Pembatalan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
