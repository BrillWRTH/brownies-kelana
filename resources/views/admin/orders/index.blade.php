@extends('layouts.admin')

@section('title', 'Daftar Pesanan — Panel Admin')
@section('kicker', 'MANAJEMEN PESANAN')

@section('content')
    <div class="space-y-5">
        {{-- Header Daftar Pesanan --}}
        <div class="flex flex-col gap-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-[#28211c]">Daftar Semua Pesanan</h1>
                <p class="mt-1 text-xs text-[#796b62]">
                    <span class="font-semibold text-amber-800">{{ $unpaidCount }} menunggu pembayaran</span> &bull;
                    <span class="font-semibold text-blue-800">{{ $processingCount }} sedang diproduksi/dikemas</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.orders.export', request()->query()) }}" class="btn-kelana-outline px-4 py-2 text-xs">
                    Ekspor CSV
                </a>
                <a href="{{ route('admin.orders.recap') }}" class="btn-kelana-outline px-4 py-2 text-xs">
                    Rekap Produksi
                </a>
                <a href="{{ route('admin.orders.create') }}" class="btn-kelana-primary px-4 py-2 text-xs">
                    + Pesanan WhatsApp
                </a>
            </div>
        </div>

        {{-- Baris Filter Pesanan --}}
        <form method="GET" action="{{ route('admin.orders.index') }}"
            class="grid gap-3 rounded-2xl border border-[#e8dfd3] bg-white p-4 shadow-xs sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <label class="kelana-label">Cari Order / Nama / HP</label>
                <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="BC-1040 atau nama..." class="kelana-input py-2 text-xs">
            </div>

            <div>
                <label class="kelana-label">Sumber Order</label>
                <select name="source" class="kelana-input py-2 text-xs">
                    <option value="">Semua Sumber</option>
                    <option value="website" @selected($sourceFilter === 'website')>Website</option>
                    <option value="whatsapp" @selected($sourceFilter === 'whatsapp')>WhatsApp Manual</option>
                </select>
            </div>

            <div>
                <label class="kelana-label">Metode</label>
                <select name="delivery_type" class="kelana-input py-2 text-xs">
                    <option value="">Semua Metode</option>
                    <option value="delivery" @selected($deliveryFilter === 'delivery')>Delivery / Kirim</option>
                    <option value="pickup" @selected($deliveryFilter === 'pickup')>Pickup / Ambil</option>
                </select>
            </div>

            <div>
                <label class="kelana-label">Status Bayar</label>
                <select name="payment_status" class="kelana-input py-2 text-xs">
                    <option value="">Semua Status Bayar</option>
                    <option value="belum_dibayar" @selected($paymentFilter === 'belum_dibayar')>Belum Dibayar</option>
                    <option value="lunas" @selected($paymentFilter === 'lunas')>Lunas</option>
                    <option value="gagal" @selected($paymentFilter === 'gagal')>Gagal</option>
                    <option value="kedaluwarsa" @selected($paymentFilter === 'kedaluwarsa')>Kedaluwarsa</option>
                    <option value="dibatalkan" @selected($paymentFilter === 'dibatalkan')>Dibatalkan</option>
                    <option value="refund" @selected($paymentFilter === 'refund')>Refund</option>
                </select>
            </div>

            <div>
                <label class="kelana-label">Status Pesanan</label>
                <select name="status" class="kelana-input py-2 text-xs">
                    <option value="">Semua Status</option>
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

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="kelana-label">Tanggal Jadwal</label>
                    <input type="date" name="date" value="{{ $dateFilter }}" class="kelana-input py-2 text-xs">
                </div>
                <button type="submit" class="btn-kelana-primary px-3 py-2 text-xs">Filter</button>
                @if($searchKeyword || $sourceFilter || $deliveryFilter || $paymentFilter || $statusFilter || $dateFilter)
                    <a href="{{ route('admin.orders.index') }}" class="btn-kelana-outline px-2.5 py-2 text-xs" title="Reset Filter">Reset</a>
                @endif
            </div>
        </form>

        {{-- Tabel Pesanan --}}
        <div class="overflow-hidden rounded-2xl border border-[#e8dfd3] bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#efe7dc] text-left text-xs">
                    <thead class="bg-[#fdfbf6] text-[11px] font-bold tracking-wider text-[#936b49] uppercase">
                        <tr>
                            <th class="px-4 py-3">No. Order &amp; Sumber</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Penerimaan</th>
                            <th class="px-4 py-3">Jadwal H-1</th>
                            <th class="px-4 py-3">Item &amp; Total</th>
                            <th class="px-4 py-3">Pembayaran</th>
                            <th class="px-4 py-3">Status Pesanan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f3ece2]">
                        @forelse($orders as $order)
                            @php
                                $payBadge = match($order->payment_status) {
                                    'lunas' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                    'belum_dibayar' => 'border-amber-200 bg-amber-50 text-amber-800',
                                    default => 'border-red-200 bg-red-50 text-red-800',
                                };
                                $orderBadge = match($order->order_status) {
                                    'sampai_tujuan', 'sudah_diambil' => 'bg-emerald-50 text-emerald-800',
                                    'menunggu_pembayaran' => 'bg-amber-50 text-amber-800',
                                    'diproses', 'dikemas' => 'bg-blue-50 text-blue-800',
                                    'dalam_pengiriman', 'siap_diambil' => 'bg-violet-50 text-violet-800',
                                    default => 'bg-red-50 text-red-800',
                                };
                            @endphp
                            <tr class="hover:bg-[#fdfbf6]">
                                <td class="px-4 py-3.5 align-top">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#52311a] hover:underline">
                                        {{ $order->order_number }}
                                    </a>
                                    <div class="mt-1">
                                        @if($order->source === 'whatsapp')
                                            <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800">WA Manual</span>
                                        @else
                                            <span class="rounded bg-[#f9eddd] px-1.5 py-0.5 text-[10px] font-semibold text-[#52311a]">Website</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <p class="font-semibold text-[#28211c]">{{ $order->customer_name }}</p>
                                    <p class="text-[11px] text-[#796b62]">{{ $order->customer_phone }}</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="font-semibold text-[#52311a]">
                                        {{ $order->delivery_type === 'pickup' ? 'Pickup' : 'Delivery' }}
                                    </span>
                                    <p class="mt-0.5 max-w-[180px] truncate text-[11px] text-[#796b62]">
                                        {{ $order->delivery_type === 'pickup' ? ($order->pickupLocation?->name ?? '-') : ($order->delivery_address ?? '-') }}
                                    </p>
                                </td>
                                <td class="px-4 py-3.5 align-top text-[#5e524a]">
                                    <p class="font-medium">{{ $order->scheduled_date?->locale('id')->isoFormat('D MMM YYYY') }}</p>
                                    <p class="text-[11px] text-[#8d7d70]">{{ $order->scheduled_time_slot }}</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <p class="font-bold text-[#28211c]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                                    <p class="text-[11px] text-[#796b62]">{{ $order->items->sum('quantity') }} pcs</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="inline-block rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $payBadge }}">
                                        {{ $order->paymentStatusLabel() }}
                                    </span>
                                    <p class="mt-1 text-[10px] text-[#8d7d70]">{{ $order->latestPayment?->channel_label ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $orderBadge }}">
                                        {{ $order->orderStatusLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right align-top whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                        class="inline-flex rounded-lg border border-[#dfd2c0] px-2.5 py-1 text-[11px] font-semibold text-[#52311a] hover:bg-[#f9eddd]">
                                        Detail
                                    </a>
                                    <a href="{{ route('admin.orders.receipt', $order) }}"
                                        class="ml-1 inline-flex rounded-lg border border-[#dfd2c0] px-2.5 py-1 text-[11px] font-semibold text-[#5e524a] hover:bg-[#fdfbf6]">
                                        Nota
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-[#796b62]">
                                    Belum ada pesanan yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="border-t border-[#efe7dc] px-4 py-3">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

