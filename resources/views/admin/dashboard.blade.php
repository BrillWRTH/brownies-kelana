@extends('layouts.admin')

@section('title', 'Dashboard Admin — Brownies Kelana')
@section('kicker', 'RINGKASAN OPERASIONAL')

@section('content')
    <div class="space-y-6">
        {{-- Header & Aksi Cepat --}}
        <div class="flex flex-col gap-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-[#28211c]">Ringkasan Toko &amp; Antrean Hari Ini</h1>
                <p class="mt-1 text-xs text-[#796b62]">
                    Pantau pembayaran Midtrans, pesanan H-1 yang harus diproduksi, dan aktivitas terbaru pelanggan.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.orders.export') }}" class="btn-kelana-outline px-4 py-2 text-xs">
                    Ekspor CSV Pesanan
                </a>
                <a href="{{ route('admin.orders.recap') }}" class="btn-kelana-outline px-4 py-2 text-xs">
                    Rekap Produksi
                </a>
                <a href="{{ route('admin.orders.create') }}" class="btn-kelana-primary px-4 py-2 text-xs">
                    + Catat Pesanan WhatsApp
                </a>
            </div>
        </div>

        {{-- Kartu Statistik Utama (KPI) --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                <p class="text-[11px] font-bold tracking-wider text-[#936b49] uppercase">Total Pendapatan Lunas</p>
                <p class="mt-2 text-2xl font-extrabold text-[#52311a]">
                    Rp {{ number_format($paidTotalRevenue, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-[#796b62]">
                    Dari {{ $paidOrdersCount }} pesanan lunas (total {{ $totalOrders }} pesanan)
                </p>
            </div>

            <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                <p class="text-[11px] font-bold tracking-wider text-amber-700 uppercase">Menunggu Pembayaran</p>
                <p class="mt-2 text-2xl font-extrabold text-amber-800">{{ $pendingPaymentCount }}</p>
                <p class="mt-1 text-xs text-[#796b62]">
                    Batas otomatis {{ $storeSetting->payment_timeout_minutes }} menit Midtrans
                </p>
            </div>

            <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                <p class="text-[11px] font-bold tracking-wider text-blue-700 uppercase">Perlu Diproses &amp; Kirim</p>
                <p class="mt-2 text-2xl font-extrabold text-blue-900">{{ $needsActionCount }}</p>
                <p class="mt-1 text-xs text-[#796b62]">
                    {{ $processingCount }} diproduksi/kemas &bull; {{ $inTransitCount }} kirim/siap ambil
                </p>
            </div>

            <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                <p class="text-[11px] font-bold tracking-wider text-emerald-700 uppercase">Pesanan Selesai</p>
                <p class="mt-2 text-2xl font-extrabold text-emerald-800">{{ $completedCount }}</p>
                <p class="mt-1 text-xs text-[#796b62]">
                    Sampai tujuan atau telah diambil pelanggan
                </p>
            </div>
        </div>

        {{-- Jadwal Produksi Hari Ini & Besok --}}
        <div class="grid gap-4 md:grid-cols-2">
            @foreach($scheduleSummary as $summary)
                @php
                    $quotaPercent = $storeSetting->daily_quota > 0
                        ? min(100, (int) round(($summary['orders_count'] / $storeSetting->daily_quota) * 100))
                        : 0;
                @endphp
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold tracking-wide text-[#936b49] uppercase">Jadwal Produksi</p>
                            <h2 class="mt-0.5 text-base font-bold text-[#28211c]">{{ $summary['label'] }}</h2>
                        </div>
                        <a href="{{ route('admin.orders.recap', ['start_date' => $summary['date'], 'end_date' => $summary['date']]) }}"
                            class="text-xs font-semibold text-[#52311a] hover:underline">
                            Lihat Rekap &rarr;
                        </a>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-[#5e524a]">
                        <span class="rounded-lg bg-[#fdfbf6] px-3 py-1.5 font-semibold text-[#52311a]">
                            {{ $summary['orders_count'] }} / {{ $storeSetting->daily_quota }} Kuota Order
                        </span>
                        <span><strong>{{ $summary['total_pcs'] }}</strong> total item pcs</span>
                        <span>Delivery: <strong>{{ $summary['delivery_pcs'] }} pcs</strong></span>
                        <span>Pickup: <strong>{{ $summary['pickup_pcs'] }} pcs</strong></span>
                    </div>

                    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-[#efe7dc]">
                        <div class="h-full rounded-full bg-[#52311a]" style="width: {{ $quotaPercent }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-12">
            {{-- Kolom Kiri: 5 Pesanan Terbaru + Pencarian --}}
            <div class="space-y-4 xl:col-span-8">
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-bold text-[#28211c]">Pesanan Terbaru</h2>
                            <p class="text-xs text-[#796b62]">Menampilkan 5 transaksi terakhir dari website maupun WhatsApp manual.</p>
                        </div>
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex gap-2">
                            <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="Cari no. order / nama..."
                                class="kelana-input w-48 py-1.5 text-xs">
                            <button type="submit" class="btn-kelana-outline px-3 py-1.5 text-xs">Cari</button>
                        </form>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#efe7dc] text-left text-xs">
                            <thead class="bg-[#fdfbf6] text-[11px] font-bold tracking-wider text-[#936b49] uppercase">
                                <tr>
                                    <th class="px-3 py-2.5">Order</th>
                                    <th class="px-3 py-2.5">Pelanggan</th>
                                    <th class="px-3 py-2.5">Jadwal</th>
                                    <th class="px-3 py-2.5">Total</th>
                                    <th class="px-3 py-2.5">Pembayaran</th>
                                    <th class="px-3 py-2.5">Status</th>
                                    <th class="px-3 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f3ece2]">
                                @forelse($latestOrders as $order)
                                    @php
                                        $payColor = match($order->payment_status) {
                                            'lunas' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                            'belum_dibayar' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            default => 'bg-red-50 text-red-800 border-red-200',
                                        };
                                        $statusColor = match($order->order_status) {
                                            'sampai_tujuan', 'sudah_diambil' => 'bg-emerald-50 text-emerald-800',
                                            'menunggu_pembayaran' => 'bg-amber-50 text-amber-800',
                                            'diproses', 'dikemas' => 'bg-blue-50 text-blue-800',
                                            'dalam_pengiriman', 'siap_diambil' => 'bg-violet-50 text-violet-800',
                                            default => 'bg-red-50 text-red-800',
                                        };
                                    @endphp
                                    <tr class="hover:bg-[#fdfbf6]">
                                        <td class="px-3 py-3 align-top">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#52311a] hover:underline">
                                                {{ $order->order_number }}
                                            </a>
                                            <span class="mt-0.5 block text-[10px] text-[#8d7d70]">
                                                {{ $order->source === 'whatsapp' ? 'WhatsApp Manual' : 'Website' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 align-top">
                                            <p class="font-semibold text-[#28211c]">{{ $order->customer_name }}</p>
                                            <p class="text-[11px] text-[#796b62]">
                                                {{ $order->items->sum('quantity') }} item &bull; {{ $order->delivery_type === 'pickup' ? 'Pickup' : 'Delivery' }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-3 align-top text-[#5e524a]">
                                            <p>{{ $order->scheduled_date?->locale('id')->isoFormat('D MMM YYYY') }}</p>
                                            <p class="text-[10px] text-[#8d7d70]">{{ $order->scheduled_time_slot }}</p>
                                        </td>
                                        <td class="px-3 py-3 align-top font-bold text-[#28211c]">
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-3 align-top">
                                            <span class="inline-block rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $payColor }}">
                                                {{ $order->paymentStatusLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 align-top">
                                            <span class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $statusColor }}">
                                                {{ $order->orderStatusLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-right align-top">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-[#52311a] hover:underline">
                                                Kelola
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-6 text-center text-[#796b62]">
                                            Tidak ada pesanan yang cocok dengan pencarian Anda.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 border-t border-[#efe7dc] pt-3 text-right">
                        <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-[#52311a] hover:underline">
                            Lihat Semua Pesanan &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Menunggu Bayar & Inbox Notifikasi --}}
            <div class="space-y-4 xl:col-span-4">
                {{-- Menunggu Pembayaran Midtrans --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-[#28211c]">Menunggu Pembayaran</h2>
                    <div class="mt-3 space-y-2.5">
                        @forelse($pendingOrders as $pending)
                            <a href="{{ route('admin.orders.show', $pending) }}"
                                class="block rounded-xl border border-amber-200 bg-amber-50/50 p-3 transition hover:bg-amber-50">
                                <div class="flex items-center justify-between text-xs font-bold text-[#28211c]">
                                    <span>{{ $pending->order_number }} &bull; {{ $pending->customer_name }}</span>
                                    <span class="text-[#52311a]">Rp {{ number_format($pending->total_amount, 0, ',', '.') }}</span>
                                </div>
                                <p class="mt-1 text-[11px] text-amber-800">
                                    {{ $pending->latestPayment?->channel_label ?? 'Midtrans' }}
                                    @if($pending->payment_deadline_at)
                                        &middot; Batas {{ $pending->payment_deadline_at->format('H:i') }} WIB
                                    @endif
                                </p>
                            </a>
                        @empty
                            <p class="rounded-xl bg-[#fdfbf6] p-3 text-xs text-[#796b62]">
                                Tidak ada tagihan yang menunggu pembayaran saat ini.
                            </p>
                        @endforelse
                    </div>
                </div>

                {{-- Inbox Notifikasi Admin Terbaru --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-[#28211c]">Inbox Admin Terbaru</h2>
                        @if($unreadNotificationsCount > 0)
                            <span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $unreadNotificationsCount }} baru</span>
                        @endif
                    </div>

                    <div class="mt-3 space-y-2.5">
                        @forelse($unreadNotifications as $notif)
                            <a href="{{ route('admin.notifications.index', ['read' => $notif->id]) }}"
                                class="block rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-3 transition hover:border-[#b18950]">
                                <p class="text-xs font-bold text-[#52311a]">{{ $notif->title }}</p>
                                <p class="mt-0.5 text-[11px] text-[#5e524a]">{{ Str::limit($notif->message, 85) }}</p>
                                <p class="mt-1 text-[10px] text-[#8d7d70]">{{ $notif->created_at->diffForHumans() }}</p>
                            </a>
                        @empty
                            <p class="rounded-xl bg-[#fdfbf6] p-3 text-xs text-[#796b62]">
                                Semua notifikasi operasional sudah terbaca.
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-[#efe7dc] pt-3 text-xs">
                        <a href="{{ route('admin.notifications.index') }}" class="font-bold text-[#52311a] hover:underline">Buka Inbox</a>
                        <a href="{{ route('admin.settings.index') }}" class="font-semibold text-[#796b62] hover:text-[#52311a]">Pengaturan Toko</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

