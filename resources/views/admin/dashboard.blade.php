@extends('layouts.admin')

@section('title', 'Dashboard Admin — Brownies Kelana')
@section('kicker', 'DASHBOARD')

@section('content')
    <div class="space-y-6">
        {{-- Header & Aksi Cepat sesuai UI/UX --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="font-serif text-3xl font-bold text-[#593822]">Ringkasan Operasional</h1>
                <p class="mt-1 text-xs text-[#796b62]">
                    Ringkasan Toko &middot; {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY · HH:mm') }} WIB
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="relative">
                    <button type="submit" aria-label="Cari pesanan" class="absolute left-3 top-1/2 -translate-y-1/2 text-[#8f6743]">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </button>
                    <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="Cari pesanan atau produk..."
                        class="w-48 rounded-xl border border-[#efe2ce] bg-white py-2 pl-9 pr-3 text-xs text-[#2f2118] placeholder-[#9e8a79] focus:border-[#593822] focus:outline-none sm:w-56">
                </form>

                <a href="{{ route('admin.orders.export') }}" class="btn-kelana-outline rounded-xl px-4 py-2 text-xs">
                    Export
                </a>
                <a href="{{ route('admin.orders.create') }}" class="btn-kelana-primary rounded-xl px-4 py-2 text-xs">
                    + Pesanan Baru
                </a>
            </div>
        </div>

        {{-- 4 Kartu Statistik Utama (KPI) dengan Icon Badge Krem --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-[#efe2ce] bg-white p-5 shadow-xs">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold text-[#6f5a4a]">Total Pesanan</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#f7ebd5] text-[#593822]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-2 font-serif text-3xl font-bold text-[#593822]">{{ $totalOrders }}</p>
                <p class="mt-1 text-[11px] text-[#8f6743]">
                    {{ $pendingPaymentCount }} menunggu pembayaran Midtrans
                </p>
            </div>

            <div class="rounded-2xl border border-[#efe2ce] bg-white p-5 shadow-xs">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold text-[#6f5a4a]">Perlu Ditindaklanjuti</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#f7ebd5] text-[#593822]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-2 font-serif text-3xl font-bold text-[#593822]">{{ $needsActionCount }}</p>
                <p class="mt-1 text-[11px] text-[#8f6743]">
                    {{ $processingCount }} diproduksi/kemas &bull; {{ $inTransitCount }} kirim/siap ambil
                </p>
            </div>

            <div class="rounded-2xl border border-[#efe2ce] bg-white p-5 shadow-xs">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold text-[#6f5a4a]">Pembayaran Lunas</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#f7ebd5] text-[#593822]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                        </svg>
                    </span>
                </div>
                <p class="mt-2 font-serif text-2xl font-bold text-[#593822]">
                    Rp {{ number_format($paidTotalRevenue, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-[11px] text-[#8f6743]">
                    {{ $paidOrdersCount }} lunas &bull; batas {{ $storeSetting->payment_timeout_minutes }} menit
                </p>
            </div>

            <div class="rounded-2xl border border-[#efe2ce] bg-white p-5 shadow-xs">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold text-[#6f5a4a]">Pesanan Selesai</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#f7ebd5] text-[#593822]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-2 font-serif text-3xl font-bold text-[#593822]">{{ $completedCount }} / {{ $totalOrders }}</p>
                <p class="mt-1 text-[11px] text-[#8f6743]">
                    Sampai tujuan atau telah diambil pelanggan
                </p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-12">
            {{-- Kolom Kiri: Tabel Pesanan Terbaru --}}
            <div class="xl:col-span-8">
                <div class="rounded-2xl border border-[#efe2ce] bg-white p-6 shadow-xs">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-serif text-2xl font-bold text-[#593822]">Pesanan Terbaru</h2>
                            <p class="text-xs text-[#796b62]">5 transaksi terbaru yang perlu diawasi dari website maupun WhatsApp manual.</p>
                        </div>
                        <a href="{{ route('admin.orders.index') }}"
                            class="rounded-xl border border-[#efe2ce] bg-[#fcf3e3] px-3.5 py-2 text-xs font-semibold text-[#593822] hover:bg-[#f7ebd5]">
                            Kelola Semua &rarr;
                        </a>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#efe2ce] text-left text-xs">
                            <thead class="text-[11px] font-semibold text-[#8f6743]">
                                <tr>
                                    <th class="px-3 py-2.5">No. Pesanan</th>
                                    <th class="px-3 py-2.5">Pelanggan</th>
                                    <th class="px-3 py-2.5">Status</th>
                                    <th class="px-3 py-2.5">Total</th>
                                    <th class="px-3 py-2.5">Jadwal</th>
                                    <th class="px-3 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f3e8d8]">
                                @forelse($latestOrders as $order)
                                    @php
                                        $statusColor = match($order->order_status) {
                                            'sampai_tujuan', 'sudah_diambil' => 'bg-emerald-50 text-emerald-800',
                                            'menunggu_pembayaran' => 'bg-amber-50 text-amber-800',
                                            'diproses', 'dikemas' => 'bg-[#f7ebd5] text-[#593822]',
                                            'dalam_pengiriman', 'siap_diambil' => 'bg-violet-50 text-violet-800',
                                            default => 'bg-red-50 text-red-800',
                                        };
                                    @endphp
                                    <tr class="hover:bg-[#fcf3e3]/50">
                                        <td class="px-3 py-3.5 align-top">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#593822] hover:underline">
                                                {{ $order->order_number }}
                                            </a>
                                            <span class="mt-0.5 block text-[10px] text-[#8f7968]">
                                                {{ $order->source === 'whatsapp' ? 'WhatsApp' : 'Website' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3.5 align-top">
                                            <p class="font-bold text-[#2f2118]">{{ $order->customer_name }}</p>
                                            <p class="text-[11px] text-[#796b62]">
                                                {{ $order->items->sum('quantity') }} item &bull; {{ $order->delivery_type === 'pickup' ? 'Pickup' : 'Pengiriman' }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-3.5 align-top">
                                            <span class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $statusColor }}">
                                                {{ $order->orderStatusLabel() }}
                                            </span>
                                            <span class="mt-1 block text-[10px] font-semibold {{ $order->payment_status === 'lunas' ? 'text-emerald-700' : 'text-amber-700' }}">
                                                {{ $order->paymentStatusLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3.5 align-top font-bold text-[#2f2118]">
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-3.5 align-top text-[#5e4d3e]">
                                            <p>{{ $order->scheduled_date?->locale('id')->isoFormat('D MMM') }}</p>
                                            <p class="text-[10px] text-[#8f7968]">{{ $order->created_at->format('H:i') }} WIB</p>
                                        </td>
                                        <td class="px-3 py-3.5 text-right align-top">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#593822] hover:underline">
                                                Kelola
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-[#796b62]">
                                            Tidak ada pesanan yang cocok dengan pencarian Anda.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-[#efe2ce] pt-4 text-xs">
                        <span class="text-[#796b62]">Menampilkan {{ $latestOrders->count() }} dari {{ $totalOrders }} pesanan</span>
                        <a href="{{ route('admin.orders.index') }}" class="rounded-xl bg-[#f7ebd5] px-4 py-2 font-bold text-[#593822] hover:bg-[#e8decb]">
                            Lihat Semua &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Operasional Hari Ini (Menunggu Pembayaran, Inbox, Jadwal Produksi) --}}
            <div class="xl:col-span-4">
                <div class="rounded-2xl border border-[#efe2ce] bg-white p-6 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-serif text-xl font-bold text-[#593822]">Operasional Hari Ini</h2>
                            <p class="text-xs text-[#796b62]">Pembayaran, inbox, dan produksi</p>
                        </div>
                        <a href="{{ route('admin.orders.index') }}"
                            class="rounded-xl border border-[#efe2ce] bg-[#fcf3e3] px-3 py-1.5 text-[11px] font-semibold text-[#593822] hover:bg-[#f7ebd5]">
                            Lihat
                        </a>
                    </div>

                    <div class="mt-4 space-y-3.5">
                        {{-- Subcard 1: Pembayaran Menunggu --}}
                        <div class="rounded-xl bg-[#f7ebd5]/80 p-4 text-xs">
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-[#593822]">Pembayaran Menunggu</h3>
                                <span class="font-bold text-amber-700">&#9733; {{ $pendingPaymentCount }}</span>
                            </div>
                            @if($pendingOrders->isNotEmpty())
                                @foreach($pendingOrders->take(2) as $pending)
                                    <a href="{{ route('admin.orders.show', $pending) }}" class="mt-2 block text-[#2f2118] hover:underline">
                                        <span class="font-bold">{{ $pending->order_number }}</span> &middot; {{ $pending->customer_name }}<br>
                                        <span class="text-[11px] text-[#6f5a4a]">
                                            {{ $pending->latestPayment?->channel_label ?? 'Midtrans' }} &middot; Rp {{ number_format($pending->total_amount, 0, ',', '.') }}
                                        </span>
                                    </a>
                                @endforeach
                            @else
                                <p class="mt-1.5 text-[11px] text-[#6f5a4a]">Tidak ada tagihan yang menunggu pembayaran.</p>
                            @endif
                        </div>

                        {{-- Subcard 2: Notifikasi Masuk --}}
                        <div class="rounded-xl bg-[#f7ebd5]/80 p-4 text-xs">
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-[#593822]">Notifikasi Masuk</h3>
                                <span class="font-bold text-amber-700">&#9733; {{ $unreadNotificationsCount }}</span>
                            </div>
                            <p class="mt-1 text-[#2f2118]">{{ $unreadNotificationsCount }} belum dibaca</p>
                            @foreach($unreadNotifications->take(2) as $notif)
                                <a href="{{ route('admin.notifications.index', ['read' => $notif->id]) }}" class="mt-1 block truncate text-[11px] text-[#6f5a4a] hover:text-[#593822] hover:underline">
                                    &bull; {{ $notif->title }}: {{ $notif->message }}
                                </a>
                            @endforeach
                            <a href="{{ route('admin.notifications.index') }}" class="mt-2 inline-block text-[11px] font-bold text-[#593822] hover:underline">
                                Buka Inbox Admin
                            </a>
                        </div>

                        {{-- Subcard 3: Jadwal Produksi --}}
                        <div class="rounded-xl bg-[#f7ebd5]/80 p-4 text-xs">
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-[#593822]">Jadwal Produksi</h3>
                                <span class="font-bold text-amber-700">&#9733; {{ count($scheduleSummary) }} hari</span>
                            </div>
                            <div class="mt-2 space-y-1.5 text-[11px] text-[#2f2118]">
                                @foreach($scheduleSummary as $summary)
                                    <p>
                                        <strong>{{ $summary['label'] }}:</strong> {{ $summary['total_pcs'] }} pcs
                                        <span class="block text-[#6f5a4a]">{{ $summary['delivery_pcs'] }} kirim / {{ $summary['pickup_pcs'] }} pickup ({{ $summary['orders_count'] }}/{{ $storeSetting->daily_quota }} order)</span>
                                    </p>
                                @endforeach
                            </div>
                        </div>

                        <a href="{{ route('admin.orders.recap') }}"
                            class="flex w-full items-center justify-between rounded-xl bg-[#f7ebd5] px-4 py-2.5 text-xs font-bold text-[#593822] transition hover:bg-[#e8decb]">
                            <span>Buka Rekap Produksi</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
