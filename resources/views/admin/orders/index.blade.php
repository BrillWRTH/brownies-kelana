@extends('layouts.admin')

@section('title', 'Daftar Pesanan — Panel Admin')
@section('kicker', 'PESANAN')

@section('content')
    <div class="space-y-5">
        {{-- Header Kelola Pesanan --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="font-serif text-3xl font-bold text-[#593822]">Kelola Pesanan</h1>
                <p class="mt-1 text-xs text-[#796b62]">
                    {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY · HH:mm') }} WIB &middot;
                    <span class="font-semibold text-amber-800">{{ $unpaidCount }} menunggu pembayaran</span> &bull;
                    <span class="font-semibold text-[#593822]">{{ $processingCount }} sedang diproduksi/dikemas</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.orders.recap') }}" class="btn-kelana-outline rounded-xl px-4 py-2 text-xs">
                    Rekap Produksi
                </a>
                <a href="{{ route('admin.orders.export', request()->query()) }}" class="btn-kelana-outline rounded-xl px-4 py-2 text-xs">
                    Export CSV
                </a>
                <a href="{{ route('admin.orders.create') }}" class="btn-kelana-primary rounded-xl px-4 py-2 text-xs">
                    + Buat Pesanan Manual WhatsApp
                </a>
            </div>
        </div>

        {{-- Baris Filter Pesanan --}}
        <form method="GET" action="{{ route('admin.orders.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-2xl border border-[#efe2ce] bg-white p-4 shadow-xs">
            <div class="min-w-[170px] flex-1">
                <label for="filter-order-q" class="kelana-label">Cari Order / Nama / HP</label>
                <input id="filter-order-q" type="text" name="q" value="{{ $searchKeyword }}" placeholder="BC-1040 atau nama..." class="kelana-input py-2 text-xs">
            </div>

            <div class="min-w-[140px] flex-1">
                <label for="filter-order-source" class="kelana-label">Sumber</label>
                <select id="filter-order-source" name="source" class="kelana-input py-2 text-xs">
                    <option value="">Sumber: Semua</option>
                    <option value="website" @selected($sourceFilter === 'website')>Website</option>
                    <option value="whatsapp" @selected($sourceFilter === 'whatsapp')>WhatsApp Manual</option>
                </select>
            </div>

            <div class="min-w-[140px] flex-1">
                <label for="filter-order-delivery" class="kelana-label">Metode</label>
                <select id="filter-order-delivery" name="delivery_type" class="kelana-input py-2 text-xs">
                    <option value="">Metode: Semua</option>
                    <option value="delivery" @selected($deliveryFilter === 'delivery')>Delivery / Kirim</option>
                    <option value="pickup" @selected($deliveryFilter === 'pickup')>Pickup / Ambil</option>
                </select>
            </div>

            <div class="min-w-[140px] flex-1">
                <label for="filter-order-payment" class="kelana-label">Status Bayar</label>
                <select id="filter-order-payment" name="payment_status" class="kelana-input py-2 text-xs">
                    <option value="">Bayar: Semua</option>
                    <option value="belum_dibayar" @selected($paymentFilter === 'belum_dibayar')>Belum Dibayar</option>
                    <option value="lunas" @selected($paymentFilter === 'lunas')>Lunas</option>
                    <option value="gagal" @selected($paymentFilter === 'gagal')>Gagal</option>
                    <option value="kedaluwarsa" @selected($paymentFilter === 'kedaluwarsa')>Kedaluwarsa</option>
                    <option value="dibatalkan" @selected($paymentFilter === 'dibatalkan')>Dibatalkan</option>
                    <option value="refund" @selected($paymentFilter === 'refund')>Refund</option>
                </select>
            </div>

            <div class="min-w-[145px] flex-1">
                <label for="filter-order-status" class="kelana-label">Status Pesanan</label>
                <select id="filter-order-status" name="status" class="kelana-input py-2 text-xs">
                    <option value="">Status: Semua</option>
                    <option value="menunggu_pembayaran" @selected($statusFilter === 'menunggu_pembayaran')>Menunggu Pembayaran</option>
                    <option value="diproses" @selected($statusFilter === 'diproses')>Diproses</option>
                    <option value="dikemas" @selected($statusFilter === 'dikemas')>Dikemas</option>
                    <option value="dalam_pengiriman" @selected($statusFilter === 'dalam_pengiriman')>Dalam Pengiriman</option>
                    <option value="siap_diambil" @selected($statusFilter === 'siap_diambil')>Siap Diambil</option>
                    <option value="sampai_tujuan" @selected($statusFilter === 'sampai_tujuan')>Sampai Tujuan</option>
                    <option value="sudah_diambil" @selected($statusFilter === 'sudah_diambil')>Sudah Diambil</option>
                    <option value="dibatalkan" @selected($statusFilter === 'dibatalkan')>Dibatalkan</option>
                    <option value="kedaluwarsa" @selected($statusFilter === 'kedaluwarsa')>Kedaluwarsa</option>
                </select>
            </div>

            <div class="min-w-[140px] flex-1">
                <label for="filter-order-date" class="kelana-label">Tanggal</label>
                <input id="filter-order-date" type="date" name="date" value="{{ $dateFilter }}" class="kelana-input py-2 text-xs">
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <button type="submit" class="btn-kelana-primary rounded-xl px-4 py-2 text-xs">Filter</button>
                @if($searchKeyword || $sourceFilter || $deliveryFilter || $paymentFilter || $statusFilter || $dateFilter)
                    <a href="{{ route('admin.orders.index') }}" class="btn-kelana-outline rounded-xl px-3 py-2 text-xs" title="Reset Filter">Reset</a>
                @endif
            </div>
        </form>

        {{-- Daftar Pesanan --}}
        <div class="overflow-hidden rounded-2xl border border-[#efe2ce] bg-white shadow-xs">
            <div class="flex items-center justify-between border-b border-[#efe2ce] px-6 py-4">
                <div>
                    <h2 class="font-serif text-xl font-bold text-[#593822]">Daftar Pesanan</h2>
                    <p class="text-xs text-[#796b62]">{{ $orders->total() }} transaksi ditemukan untuk dipantau.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#efe2ce] text-left text-xs">
                    <thead class="bg-[#fcf3e3]/60 text-[11px] font-semibold text-[#8f6743]">
                        <tr>
                            <th class="px-5 py-3">No.</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Metode &amp; Sumber</th>
                            <th class="px-4 py-3">Tanggal Jadwal</th>
                            <th class="px-4 py-3">Total &amp; Item</th>
                            <th class="px-4 py-3">Bayar</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f3e8d8]">
                        @forelse($orders as $order)
                            @php
                                $payBadge = match($order->payment_status) {
                                    'lunas' => 'text-emerald-800',
                                    'belum_dibayar' => 'text-amber-800',
                                    default => 'text-red-700',
                                };
                                $orderBadge = match($order->order_status) {
                                    'sampai_tujuan', 'sudah_diambil' => 'bg-emerald-50 text-emerald-800',
                                    'menunggu_pembayaran' => 'bg-amber-50 text-amber-800',
                                    'diproses', 'dikemas' => 'bg-[#f7ebd5] text-[#593822]',
                                    'dalam_pengiriman', 'siap_diambil' => 'bg-violet-50 text-violet-800',
                                    default => 'bg-red-50 text-red-800',
                                };
                            @endphp
                            <tr class="hover:bg-[#fcf3e3]/50">
                                <td class="px-5 py-3.5 align-top">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#593822] hover:underline">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <p class="font-bold text-[#2f2118]">{{ $order->customer_name }}</p>
                                    <p class="text-[11px] text-[#796b62]">{{ $order->customer_phone }}</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="font-semibold text-[#593822]">
                                        {{ $order->delivery_type === 'pickup' ? 'Ambil di toko' : 'Pengiriman' }}
                                    </span>
                                    <span class="mt-0.5 block text-[10px] text-[#8f6743]">
                                        {{ $order->source === 'whatsapp' ? 'WhatsApp' : 'Website' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 align-top text-[#5e4d3e]">
                                    <p class="font-medium">{{ $order->scheduled_date?->locale('id')->isoFormat('D MMM YYYY') }}</p>
                                    <p class="text-[10px] text-[#8f7968]">{{ $order->scheduled_time_slot }}</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <p class="font-bold text-[#2f2118]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                                    <p class="text-[11px] text-[#796b62]">{{ $order->items->sum('quantity') }} item</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="font-bold {{ $payBadge }}">
                                        {{ $order->paymentStatusLabel() }}
                                    </span>
                                    <p class="mt-0.5 text-[10px] text-[#8f7968]">{{ $order->latestPayment?->channel_label ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $orderBadge }}">
                                        {{ $order->orderStatusLabel() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right align-top whitespace-nowrap">
                                    <div class="inline-flex flex-col gap-1">
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                            class="rounded-full bg-[#f7ebd5] px-3 py-1 text-center text-[11px] font-bold text-[#593822] hover:bg-[#e8decb]">
                                            Ubah Status / Detail
                                        </a>
                                        <a href="{{ route('admin.orders.receipt', $order) }}"
                                            class="rounded-full border border-[#d9c8b4] bg-white px-3 py-0.5 text-center text-[10px] font-bold text-[#5e4d3e] hover:bg-[#fcf3e3]">
                                            Nota
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-10 text-center text-sm text-[#796b62]">
                                    Belum ada pesanan yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="border-t border-[#efe2ce] px-5 py-3">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
