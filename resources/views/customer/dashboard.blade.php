@extends('layouts.app')

@section('title', 'Dashboard Pelanggan — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('partials.customer-sidebar')

            <div class="min-w-0 flex-1 space-y-6">
                {{-- Banner Sapaan Pelanggan Emas Karamel --}}
                <div class="relative overflow-hidden rounded-2xl bg-[#a4804f] px-6 py-6 text-white shadow-xs sm:px-8">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <h1 class="font-serif text-3xl font-bold sm:text-4xl">
                                Halo, {{ explode(' ', $user->name)[0] }}
                            </h1>
                            <p class="mt-2 max-w-md text-xs leading-relaxed text-[#fcf3e3]">
                                Selamat datang di dashboard kamu. Pantau pesanan dan notifikasi terbaru di sini.
                            </p>
                            @if($user->customer_code)
                                <span class="mt-3 inline-block rounded-full bg-[#593822]/85 px-3 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase">
                                    {{ $user->customer_code }}
                                </span>
                            @endif
                        </div>
                        <img src="{{ asset('images/kelana/brownie-fork.png') }}" alt="Brownies Kelana" class="hidden h-28 w-auto shrink-0 object-contain sm:block">
                    </div>
                </div>

                {{-- Banner Jika Email Belum Terverifikasi --}}
                @if(! $user->hasVerifiedEmail())
                    <div class="flex flex-col justify-between gap-3 rounded-2xl border border-[#8f6743]/50 bg-[#f7ebd5] p-4 sm:flex-row sm:items-center">
                        <div class="text-xs text-[#2f2118]">
                            <p class="font-bold text-[#593822]">&#9432; Akun belum terverifikasi</p>
                            <p class="mt-0.5 text-[#5e4d3e]">Halo {{ $user->name }}, {{ $user->email }}. Kirim ulang email verifikasi untuk melengkapi akunmu.</p>
                        </div>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn-kelana-primary rounded-lg px-4 py-2 text-xs">
                                Kirim Ulang Email Verifikasi
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Kartu Pesanan Aktif & Track 5 Langkah dengan Ikon Lingkaran --}}
                <div class="rounded-2xl border border-[#8f6743] bg-white p-5 shadow-xs sm:p-6">
                    <h2 class="font-serif text-lg font-semibold text-[#2f2118]">Pesanan Aktif</h2>

                    @if($activeOrder)
                        @php
                            $firstItem = $activeOrder->items->first();
                            $stepKeys = $activeOrder->delivery_type === 'pickup'
                                ? ['menunggu_pembayaran', 'diproses', 'dikemas', 'siap_diambil', 'sudah_diambil']
                                : ['menunggu_pembayaran', 'diproses', 'dikemas', 'dalam_pengiriman', 'sampai_tujuan'];
                            $stepLabels = $activeOrder->delivery_type === 'pickup'
                                ? ['Menunggu Pembayaran', 'Diproses', 'Dikemas', 'Siap Diambil', 'Sudah Diambil']
                                : ['Menunggu Pembayaran', 'Diproses', 'Dikemas', 'Dalam Pengiriman', 'Sampai Tujuan'];
                            $currentIdx = array_search($activeOrder->order_status, $stepKeys, true);
                            $currentIdx = $currentIdx === false ? 0 : $currentIdx;
                        @endphp

                        <div class="mt-4 rounded-xl border border-[#8f6743]/70 bg-white p-4">
                            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                                <div class="flex items-start gap-4">
                                    <img src="{{ $firstItem?->product?->primaryImageUrl() ?? asset('images/kelana/prod-brownies-panggang.png') }}"
                                        alt="{{ $firstItem?->product_name ?? 'Produk' }}"
                                        class="h-20 w-24 shrink-0 rounded-lg object-cover">
                                    <div class="text-xs">
                                        <p class="text-sm font-bold text-[#2f2118]">
                                            {{ $firstItem?->product_name ?? 'Pesanan Kelana' }}
                                            @if($activeOrder->items->count() > 1)
                                                <span class="text-xs font-normal text-[#6f5a4a]">(+{{ $activeOrder->items->count() - 1 }} lainnya)</span>
                                            @endif
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-[#796b62]">
                                            x{{ $activeOrder->items->sum('quantity') }} kotak &middot; {{ $firstItem?->variant_name ?? 'Reguler' }} &middot; {{ $activeOrder->order_number }}
                                        </p>
                                        <p class="mt-2 font-semibold text-[#2f2118]">
                                            Rp {{ number_format($activeOrder->subtotal, 0, ',', '.') }}
                                            @if($activeOrder->shipping_fee > 0)
                                                <span class="block text-[11px] font-normal text-[#6f5a4a]">+ Rp {{ number_format($activeOrder->shipping_fee, 0, ',', '.') }} (ongkir)</span>
                                            @endif
                                        </p>
                                        <p class="mt-2 text-[11px] text-[#796b62]">
                                            Jadwal: {{ $activeOrder->scheduled_date?->locale('id')->isoFormat('D MMMM YYYY') }}, {{ $activeOrder->scheduled_time_slot }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-col items-start gap-2 sm:items-end">
                                    <span class="rounded-full bg-[#e8decb] px-3 py-0.5 text-[11px] font-semibold text-[#593822]">
                                        {{ $activeOrder->orderStatusLabel() }}
                                    </span>
                                    <p class="text-lg font-bold text-[#2f2118]">
                                        Rp {{ number_format($activeOrder->total_amount, 0, ',', '.') }}
                                    </p>
                                    @if($activeOrder->payment_status === 'belum_dibayar')
                                        <a href="{{ route('payments.show', $activeOrder) }}"
                                            class="btn-kelana-primary rounded-lg px-5 py-2 text-xs">
                                            Bayar Sekarang
                                        </a>
                                    @else
                                        <a href="{{ route('orders.show', $activeOrder->order_number) }}"
                                            class="btn-kelana-outline rounded-lg px-4 py-2 text-xs">
                                            Lihat Detail Pesanan
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Track 5 Langkah dengan Ikon Lingkaran --}}
                        <div class="relative mt-6 grid grid-cols-5 gap-2 text-center">
                            @foreach($stepLabels as $idx => $label)
                                @php $reached = $idx <= $currentIdx; @endphp
                                <div class="relative flex flex-col items-center">
                                    @if($idx < 4)
                                        <span class="pointer-events-none absolute left-1/2 top-4 hidden h-0.5 w-full sm:block {{ $idx < $currentIdx ? 'bg-[#593822]' : 'bg-[#d9c8b4]' }}"></span>
                                    @endif
                                    <div class="relative z-10 flex h-9 w-9 items-center justify-center rounded-full {{ $reached ? 'bg-[#593822] text-white' : 'bg-[#d8d3cc] text-white' }}">
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
                                    <p class="mt-1.5 text-[10px] font-bold leading-tight {{ $reached ? 'text-[#593822]' : 'text-[#796b62]' }}">{{ $label }}</p>
                                    <span class="mt-0.5 text-[9px] text-[#9e8a79]">{{ $reached ? 'Tercapai' : 'Belum tercapai' }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex justify-end">
                            <a href="{{ route('orders.show', $activeOrder->order_number) }}" class="text-xs font-bold text-[#593822] hover:underline">
                                Lihat Detail Lengkap &rarr;
                            </a>
                        </div>
                    @else
                        <div class="mt-4 rounded-xl bg-[#fcf3e3] p-5 text-center">
                            <p class="text-xs font-semibold text-[#5e4d3e]">Belum ada pesanan yang sedang berlangsung.</p>
                            <a href="{{ route('products.index') }}" class="btn-kelana-primary mt-3 rounded-lg text-xs">Pesan Brownies Sekarang</a>
                        </div>
                    @endif
                </div>

                {{-- Baris Notifikasi Cepat & Pesan Apresiasi --}}
                <div class="grid items-center gap-5 md:grid-cols-12">
                    <a href="{{ route('notifications.index') }}"
                        class="flex items-center gap-3.5 rounded-xl border border-[#8f6743] bg-[#e8decb] p-4 transition hover:bg-[#efe2ce] md:col-span-5">
                        <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-[#593822]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            @if(($sharedCustomerUnreadCount ?? 0) > 0)
                                <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[9px] font-bold text-white">
                                    {{ $sharedCustomerUnreadCount }}
                                </span>
                            @endif
                        </span>
                        <div>
                            <p class="text-xs font-bold text-[#2f2118]">Notifikasi</p>
                            <p class="text-[11px] text-[#5e4d3e]">Kamu memiliki {{ $sharedCustomerUnreadCount ?? 0 }} notifikasi baru</p>
                        </div>
                    </a>

                    <div class="flex items-center gap-3.5 px-2 md:col-span-7">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center text-[#c2544f]">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold text-[#2f2118]">Terima kasih sudah percaya pada Brownies Kelana</p>
                            <p class="text-[11px] text-[#6f5a4a]">Semoga hari kamu selalu manis untuk setiap momennya</p>
                        </div>
                    </div>
                </div>

                {{-- Riwayat Pesanan + Filter Tab --}}
                <div class="rounded-2xl border border-[#8f6743] bg-white p-5 shadow-xs sm:p-6">
                    <h2 class="font-serif text-lg font-semibold text-[#2f2118]">Riwayat Pesanan</h2>

                    @php
                        $filterTabs = [
                            'semua' => 'Semua',
                            'menunggu_pembayaran' => 'Menunggu Pembayaran',
                            'diproses' => 'Diproses',
                            'dikirim' => 'Dikirim / Pickup',
                            'selesai' => 'Selesai',
                            'dibatalkan' => 'Dibatalkan',
                            'kedaluwarsa' => 'Kedaluwarsa',
                        ];
                    @endphp

                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($filterTabs as $tabKey => $tabLabel)
                            <a href="{{ route('dashboard', $tabKey === 'semua' ? [] : ['status' => $tabKey]) }}"
                                class="rounded-full border px-3.5 py-1 text-xs font-semibold transition {{ $statusFilter === $tabKey ? 'border-[#593822] bg-[#593822] text-white' : 'border-[#8f6743] bg-white text-[#593822] hover:bg-[#f7ebd5]' }}">
                                {{ $tabLabel }}
                            </a>
                        @endforeach
                    </div>

                    @if($orders->isEmpty())
                        <div class="mt-6 rounded-2xl border border-[#efe2ce] bg-[#fcf3e3] p-8 text-center">
                            <p class="text-sm font-bold text-[#593822]">Belum ada pesanan pada kategori ini</p>
                            <p class="mt-1 text-xs text-[#796b62]">Temukan pilihan produk favoritmu di katalog kami.</p>
                            <a href="{{ route('products.index') }}" class="btn-kelana-primary mt-4 rounded-lg text-xs">Jelajahi Produk</a>
                        </div>
                    @else
                        <div class="mt-5 divide-y divide-[#efe2ce]">
                            @foreach($orders as $order)
                                @php $orderThumb = $order->items->first()?->product?->primaryImageUrl() ?? asset('images/kelana/prod-brownies-panggang.png'); @endphp
                                <div class="flex flex-col justify-between gap-4 py-4 transition sm:flex-row sm:items-center">
                                    <div class="flex items-center gap-3.5">
                                        <img src="{{ $orderThumb }}" alt="{{ $order->order_number }}" class="h-12 w-12 shrink-0 rounded-lg object-cover">
                                        <div>
                                            <p class="text-xs font-bold text-[#2f2118]">
                                                {{ $order->items->map(fn ($i) => $i->quantity.'x '.$i->product_name)->implode(', ') }}
                                            </p>
                                            <p class="mt-0.5 text-[11px] text-[#796b62]">
                                                {{ $order->order_number }} &middot; {{ $order->items->sum('quantity') }} kotak &middot;
                                                <strong class="text-[#593822]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-4 sm:justify-end">
                                        <div class="flex flex-col items-start gap-1 sm:items-center">
                                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold
                                                {{ in_array($order->order_status, ['dibatalkan', 'kedaluwarsa'], true) ? 'bg-red-100 text-red-700' : 'bg-[#e8decb] text-[#593822]' }}">
                                                {{ $order->orderStatusLabel() }}
                                            </span>
                                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold
                                                {{ $order->payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-[#f7ebd5] text-[#8f6743]' }}">
                                                {{ $order->paymentStatusLabel() }}
                                            </span>
                                        </div>

                                        <span class="text-[11px] text-[#6f5a4a]">
                                            {{ $order->scheduled_date?->locale('id')->isoFormat('D MMMM YYYY') }}
                                        </span>

                                        <div class="flex flex-col gap-1.5">
                                            <a href="{{ route('orders.show', $order->order_number) }}"
                                                class="rounded-lg border border-[#8f6743] bg-white px-4 py-1 text-center text-[11px] font-bold text-[#593822] hover:bg-[#f7ebd5]">
                                                Detail
                                            </a>
                                            @if($order->payment_status === 'belum_dibayar')
                                                <a href="{{ route('payments.show', $order) }}"
                                                    class="rounded-lg bg-[#593822] px-4 py-1 text-center text-[11px] font-bold text-white hover:bg-[#492d1a]">
                                                    Bayar Sekarang
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $orders->links() }}
                        </div>
                    @endif
                </div>

                @include('partials.customer-help-banner')
            </div>
        </div>
    </section>
@endsection
