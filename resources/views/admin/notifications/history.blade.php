@extends('layouts.admin')

@section('title', 'Riwayat Kirim Notifikasi — Panel Admin')
@section('kicker', 'RIWAYAT LOG NOTIFIKASI')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-wrap gap-2 rounded-2xl border border-[#e8dfd3] bg-white p-4 text-xs font-semibold shadow-xs">
            <a href="{{ route('admin.notifications.index') }}" class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">Inbox Admin</a>
            <a href="{{ route('admin.notifications.templates') }}" class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">Template Pesan</a>
            <a href="{{ route('admin.notifications.create') }}" class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">Kirim Notifikasi Khusus</a>
            <a href="{{ route('admin.notifications.history') }}" class="rounded-xl bg-[#52311a] px-4 py-2 text-white">Riwayat Kirim</a>
        </div>

        <form method="GET" action="{{ route('admin.notifications.history') }}"
            class="grid gap-3 rounded-2xl border border-[#e8dfd3] bg-white p-4 shadow-xs sm:grid-cols-4">
            <div>
                <label class="kelana-label">Cari Pelanggan / Pesan</label>
                <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="Nama atau judul..." class="kelana-input py-2 text-xs">
            </div>
            <div>
                <label class="kelana-label">Kategori</label>
                <select name="category" class="kelana-input py-2 text-xs">
                    <option value="semua" @selected($categoryFilter === 'semua')>Semua Kategori</option>
                    <option value="pesanan" @selected($categoryFilter === 'pesanan')>Pesanan</option>
                    <option value="sistem" @selected($categoryFilter === 'sistem')>Sistem</option>
                </select>
            </div>
            <div>
                <label class="kelana-label">Kanal</label>
                <select name="channel" class="kelana-input py-2 text-xs">
                    <option value="semua" @selected($channelFilter === 'semua')>Semua Kanal</option>
                    <option value="dashboard" @selected($channelFilter === 'dashboard')>Dashboard</option>
                    <option value="whatsapp_manual" @selected($channelFilter === 'whatsapp_manual')>WhatsApp Manual</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-kelana-primary flex-1 py-2 text-xs">Filter</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-[#e8dfd3] bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#efe7dc] text-left text-xs">
                    <thead class="bg-[#fdfbf6] text-[11px] font-bold text-[#936b49] uppercase">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Judul &amp; Isi Pesan</th>
                            <th class="px-4 py-3">Kanal &amp; Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f3ece2]">
                        @forelse($logs as $log)
                            <tr class="hover:bg-[#fdfbf6]">
                                <td class="px-4 py-3.5 align-top whitespace-nowrap text-[#796b62]">
                                    {{ $log->created_at?->format('d M Y H:i') }} WIB
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <p class="font-bold text-[#28211c]">{{ $log->user?->name ?? 'Pelanggan' }}</p>
                                    @if($log->order)
                                        <a href="{{ route('admin.orders.show', $log->order) }}" class="text-[11px] font-semibold text-[#52311a] hover:underline">
                                            {{ $log->order->order_number }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <p class="font-bold text-[#52311a]">{{ $log->title }}</p>
                                    <p class="mt-0.5 text-[#5e524a]">{{ $log->message }}</p>
                                </td>
                                <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                    <span class="rounded bg-[#f9eddd] px-2 py-0.5 text-[10px] font-bold text-[#52311a]">
                                        {{ $log->channel === 'whatsapp_manual' ? 'WhatsApp Manual' : 'Dashboard' }}
                                    </span>
                                    <p class="mt-1 text-[10px] font-semibold text-emerald-700">{{ ucfirst(str_replace('_', ' ', $log->delivery_status)) }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-[#796b62]">
                                    Belum ada riwayat notifikasi terkirim.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="border-t border-[#efe7dc] px-4 py-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

