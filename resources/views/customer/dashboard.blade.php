@extends('layouts.app')

@section('title', 'Dashboard Pelanggan — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('partials.customer-sidebar')

            <div class="min-w-0 flex-1 space-y-6">
                {{-- Banner Sapaan Pelanggan --}}
                <div class="relative overflow-hidden rounded-3xl bg-[#52311a] px-6 py-7 text-white shadow-md sm:px-8">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <span class="rounded-full bg-[#b18950]/90 px-3 py-0.5 text-[10px] font-bold tracking-wider uppercase">
                                {{ $user->customer_code ?? 'PELANGGAN KELANA' }}
                            </span>
                            <h1 class="mt-2 font-serif text-2xl font-bold sm:text-3xl">
                                Halo, {{ explode(' ', $user->name)[0] }}
                            </h1>
                            <p class="mt-1 text-xs text-[#e9dcc7]">
                                Selamat datang di dashboard kamu. Pantau progres pesanan dan notifikasi terbaru di sini.
                            </p>
                        </div>
                        <img src="{{ asset('images/kelana/brownie-fork.png') }}" alt="Brownies Kelana" class="hidden h-24 w-auto object-contain sm:block">
                    </div>
                </div>

                {{-- Banner Jika Email Belum Terverifikasi --}}
                @if(! $user->hasVerifiedEmail())
                    <div class="flex flex-col justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 sm:flex-row sm:items-center">
                        <div class="text-xs text-amber-950">
                            <p class="font-bold">Akun belum terverifikasi</p>
                            <p class="mt-0.5">Halo {{ $user->name }}, silakan verifikasi email ({{ $user->email }}) untuk keamanan akunmu.</p>
                        </div>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="rounded-full bg-amber-800 px-4 py-2 text-xs font-bold text-white hover:bg-amber-900">
                                Kirim Ulang Email Verifikasi
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Grid Kartu Atas: Pesanan Aktif & Info Apresiasi --}}
                <div class="grid gap-5 md:grid-cols-12">
                    <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs md:col-span-8">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold text-[#52311a]">Pesanan Aktif</h2>
                            @if($activeOrder)
                                <span class="rounded-full bg-[#f9eddd] px-3 py-1 text-[11px] font-bold text-[#52311a]">
                                    {{ $activeOrder->orderStatusLabel() }}
                                </span>
                            @endif
                        </div>

                        @if($activeOrder)
                            @php
                                $stepKeys = $activeOrder->delivery_type === 'pickup'
                                    ? ['menunggu_pembayaran', 'diproses', 'dikemas', 'siap_diambil', 'sudah_diambil']
                                    : ['menunggu_pembayaran', 'diproses', 'dikemas', 'dalam_pengiriman', 'sampai_tujuan'];
                                $stepLabels = $activeOrder->delivery_type === 'pickup'
                                    ? ['Bayar', 'Diproses', 'Dikemas', 'Siap Ambil', 'Diambil']
                                    : ['Bayar', 'Diproses', 'Dikemas', 'Dikirim', 'Sampai'];
                                $currentIdx = array_search($activeOrder->order_status, $stepKeys, true);
                                $currentIdx = $currentIdx === false ? 0 : $currentIdx;
                            @endphp

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <div>
                                    <p class="font-extrabold text-[#28211c]">
                                        {{ $activeOrder->order_number }} &middot;
                                        {{ $activeOrder->items->first()?->product_name }}
                                        @if($activeOrder->items->count() > 1)
                                            (+{{ $activeOrder->items->count() - 1 }} lainnya)
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-[#796b62]">
                                        Jadwal: {{ $activeOrder->scheduled_date?->locale('id')->isoFormat('D MMM YYYY') }} &bull; {{ $activeOrder->scheduled_time_slot }}
                                    </p>
                                </div>
                                <p class="text-sm font-extrabold text-[#52311a]">Rp {{ number_format($activeOrder->total_amount, 0, ',', '.') }}</p>
                            </div>

                            {{-- Stepper 5 Tahap --}}
                            <div class="mt-5 grid grid-cols-5 gap-1.5 text-center">
                                @foreach($stepLabels as $idx => $label)
                                    <div>
                                        <div class="mx-auto flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold {{ $idx <= $currentIdx ? 'bg-[#52311a] text-white' : 'bg-[#f2e4cd] text-[#8d7d70]' }}">
                                            {{ $idx + 1 }}
                                        </div>
                                        <p class="mt-1 text-[10px] font-semibold {{ $idx <= $currentIdx ? 'text-[#52311a]' : 'text-[#8d7d70]' }}">{{ $label }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2.5">
                                <a href="{{ route('orders.show', $activeOrder->order_number) }}" class="btn-kelana-outline rounded-full px-4 py-2 text-xs">
                                    Lihat Detail Pesanan
                                </a>
                                @if($activeOrder->payment_status === 'belum_dibayar')
                                    <a href="{{ route('payments.show', $activeOrder) }}" class="btn-kelana-primary rounded-full px-4 py-2 text-xs">
                                        Bayar Sekarang &rarr;
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="mt-4 rounded-xl bg-[#fdfbf6] p-4 text-center">
                                <p class="text-xs font-semibold text-[#5e524a]">Belum ada pesanan yang sedang berlangsung.</p>
                                <a href="{{ route('products.index') }}" class="btn-kelana-primary mt-3 rounded-full text-xs">Pesan Brownies Sekarang</a>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col justify-between rounded-2xl border border-[#e3d5c3] bg-[#f9eddd] p-5 md:col-span-4">
                        <div>
                            <h2 class="text-xs font-bold tracking-wider text-[#936b49] uppercase">Notifikasi &amp; Pesan</h2>
                            <p class="mt-2 font-serif text-base font-bold leading-snug text-[#52311a]">
                                Terima kasih sudah percaya pada Brownies Kelana
                            </p>
                            <p class="mt-1.5 text-xs text-[#5e524a]">
                                Semoga hari kamu selalu manis untuk setiap momennya.
                            </p>
                        </div>
                        <a href="{{ route('notifications.index') }}" class="btn-kelana-outline mt-4 w-full rounded-full bg-white text-xs">
                            Buka Notifikasi ({{ $sharedCustomerUnreadCount ?? 0 }} baru)
                        </a>
                    </div>
                </div>

                {{-- Riwayat Pesanan + Filter Tab --}}
                <div class="rounded-2xl border border-[#e3d5c3] bg-white p-5 shadow-xs">
                    <h2 class="text-base font-bold text-[#52311a]">Riwayat Pesanan</h2>

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
                                class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $statusFilter === $tabKey ? 'bg-[#52311a] text-white' : 'bg-[#f9eddd] text-[#52311a] hover:bg-[#e9dcc7]' }}">
                                {{ $tabLabel }}
                            </a>
                        @endforeach
                    </div>

                    @if($orders->isEmpty())
                        <div class="mt-6 rounded-2xl border border-[#efe4d4] bg-[#fdfbf6] p-8 text-center">
                            <p class="text-sm font-bold text-[#52311a]">Belum ada pesanan pada kategori ini</p>
                            <p class="mt-1 text-xs text-[#796b62]">Temukan pilihan produk favoritmu di katalog kami.</p>
                            <a href="{{ route('products.index') }}" class="btn-kelana-primary mt-4 rounded-full text-xs">Jelajahi Produk</a>
                        </div>
                    @else
                        <div class="mt-5 space-y-3">
                            @foreach($orders as $order)
                                <div class="flex flex-col justify-between gap-4 rounded-2xl border border-[#efe4d4] bg-[#fdfbf6] p-4 transition hover:border-[#b18950] sm:flex-row sm:items-center">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-extrabold text-[#52311a]">{{ $order->order_number }}</span>
                                            <span class="rounded-full bg-[#e9dcc7] px-2.5 py-0.5 text-[10px] font-bold text-[#52311a]">
                                                {{ $order->orderStatusLabel() }}
                                            </span>
                                            <span class="rounded-full {{ $order->payment_status === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} px-2.5 py-0.5 text-[10px] font-bold">
                                                {{ $order->paymentStatusLabel() }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs font-semibold text-[#28211c]">
                                            {{ $order->items->map(fn ($i) => $i->quantity.'x '.$i->product_name)->implode(', ') }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-[#796b62]">
                                            Jadwal: {{ $order->scheduled_date?->locale('id')->isoFormat('D MMM YYYY') }} &middot;
                                            {{ $order->delivery_type === 'pickup' ? 'Ambil Sendiri' : 'Pengiriman' }}
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 sm:justify-end">
                                        <span class="text-sm font-extrabold text-[#52311a]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                        <div class="flex gap-2">
                                            @if($order->payment_status === 'belum_dibayar')
                                                <a href="{{ route('payments.show', $order) }}" class="btn-kelana-primary rounded-full px-3.5 py-1.5 text-[11px]">Bayar</a>
                                            @endif
                                            <a href="{{ route('orders.show', $order->order_number) }}" class="btn-kelana-outline rounded-full px-3.5 py-1.5 text-[11px]">Detail</a>
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

