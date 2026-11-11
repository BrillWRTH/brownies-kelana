@extends('layouts.admin')

@section('title', 'Rekap Produksi Harian — Panel Admin')
@section('kicker', 'REKAP PRODUKSI & JADWAL DAPUR')

@section('content')
    <div class="space-y-5">
        {{-- Header & Filter Rentang Tanggal --}}
        <div class="no-print flex flex-col gap-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="font-serif text-xl font-bold text-[#28211c]">Rekap Produksi per Tanggal Jadwal</h1>
                <p class="mt-1 text-xs text-[#796b62]">
                    Ringkasan otomatis jumlah loyang/item yang harus dibuat dapur per tanggal H-1, slot waktu, dan catatan kue.
                </p>
            </div>

            <form method="GET" action="{{ route('admin.orders.recap') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="start_date" class="kelana-label">Dari Tanggal</label>
                    <input id="start_date" name="start_date" type="date" value="{{ $startDate }}" class="kelana-input py-2 text-xs">
                </div>
                <div>
                    <label for="end_date" class="kelana-label">Sampai Tanggal</label>
                    <input id="end_date" name="end_date" type="date" value="{{ $endDate }}" class="kelana-input py-2 text-xs">
                </div>
                <button type="submit" class="btn-kelana-primary px-4 py-2 text-xs">Tampilkan</button>
                <button type="button" onclick="window.print()" class="btn-kelana-outline px-4 py-2 text-xs">Cetak Rekap</button>
            </form>
        </div>

        @forelse($dailyGroups as $group)
            <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#efe7dc] pb-4">
                    <div>
                        <h2 class="font-serif text-base font-semibold text-[#52311a]">{{ $group['date_formatted'] }}</h2>
                        <p class="mt-0.5 text-xs text-[#796b62]">
                            {{ $group['orders_count'] }} / {{ $group['daily_quota'] }} Kuota Harian &bull;
                            <strong>{{ $group['total_pcs'] }} pcs</strong> total &bull;
                            Delivery: {{ $group['delivery_count'] }} order &bull; Pickup: {{ $group['pickup_count'] }} order
                        </p>
                    </div>
                    <a href="{{ route('admin.orders.index', ['date' => $group['date']]) }}" class="no-print text-xs font-bold text-[#52311a] hover:underline">
                        Filter Pesanan Tanggal Ini &rarr;
                    </a>
                </div>

                <div class="mt-4 grid gap-6 lg:grid-cols-12">
                    {{-- Rekap Jumlah Produksi per Produk & Varian --}}
                    <div class="lg:col-span-5">
                        <h3 class="text-xs font-semibold text-[#936b49] uppercase">Total Item yang Harus Dibuat</h3>
                        <ul class="mt-2.5 divide-y divide-[#f3ece2] rounded-xl border border-[#efe7dc] bg-[#fdfbf6]">
                            @foreach($group['item_summary'] as $itemName => $qty)
                                <li class="flex items-center justify-between px-3.5 py-2.5 text-xs">
                                    <span class="font-semibold text-[#28211c]">{{ $itemName }}</span>
                                    <span class="rounded-full bg-[#52311a] px-2.5 py-0.5 text-[11px] font-bold text-white">{{ $qty }} pcs</span>
                                </li>
                            @endforeach
                        </ul>

                        @if(!empty($group['custom_notes']))
                            <h3 class="mt-4 text-xs font-semibold text-amber-800 uppercase">Daftar Tulisan di Atas Kue</h3>
                            <ul class="mt-2 space-y-1.5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-950">
                                @foreach($group['custom_notes'] as $custom)
                                    <li>
                                        <strong>{{ $custom['order_number'] }}</strong> ({{ $custom['product_name'] }}):
                                        "<em>{{ $custom['note'] }}</em>"
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Checklist Pesanan per Slot Jam --}}
                    <div class="lg:col-span-7">
                        <h3 class="text-xs font-semibold text-[#936b49] uppercase">Antrean Pengiriman / Pengambilan</h3>
                        <div class="mt-2.5 space-y-2.5">
                            @foreach($group['orders'] as $order)
                                <div class="rounded-xl border border-[#efe7dc] p-3 text-xs">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#52311a] hover:underline">
                                                {{ $order->order_number }}
                                            </a>
                                            <span class="rounded bg-[#f9eddd] px-2 py-0.5 text-[10px] font-bold text-[#52311a]">
                                                {{ $order->scheduled_time_slot }}
                                            </span>
                                            <span class="font-semibold text-[#28211c]">{{ $order->customer_name }}</span>
                                        </div>
                                        <span class="rounded-full bg-[#fdfbf6] px-2.5 py-0.5 text-[10px] font-bold text-[#5e524a]">
                                            {{ $order->delivery_type === 'pickup' ? 'Pickup · '.$order->pickupLocation?->name : 'Delivery' }}
                                            &middot; {{ $order->orderStatusLabel() }}
                                        </span>
                                    </div>
                                    <p class="mt-1.5 text-[11px] text-[#5e524a]">
                                        {{ $order->items->map(fn ($i) => $i->quantity.'x '.$i->product_name.($i->variant_name ? ' ('.$i->variant_name.')' : ''))->implode(', ') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-[#e8dfd3] bg-white p-10 text-center text-sm text-[#796b62]">
                Tidak ada pesanan aktif pada rentang tanggal {{ $startDate }} sampai {{ $endDate }}.
            </div>
        @endforelse
    </div>
@endsection

