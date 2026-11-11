@extends('layouts.admin')

@section('title', 'Direktori Pelanggan — Panel Admin')
@section('kicker', 'MANAJEMEN PELANGGAN')

@section('content')
    <div class="space-y-5">
        <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
            <h1 class="font-serif text-xl font-bold text-[#28211c]">Direktori Akun Pelanggan</h1>
            <p class="mt-1 text-xs text-[#796b62]">
                Pantau riwayat transaksi pelanggan, verifikasi email, pencocokan pesanan WhatsApp, serta bantuan pemulihan akun.
            </p>
        </div>

        <form method="GET" action="{{ route('admin.customers.index') }}"
            class="grid gap-3 rounded-2xl border border-[#e8dfd3] bg-white p-4 shadow-xs sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="kelana-label">Cari Nama / Kode / Email / HP</label>
                <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="CUS-0045, Sinta, 0812..." class="kelana-input py-2 text-xs">
            </div>
            <div>
                <label class="kelana-label">Status Akun</label>
                <select name="status" class="kelana-input py-2 text-xs">
                    <option value="semua" @selected($statusFilter === 'semua')>Semua Status</option>
                    <option value="aktif" @selected($statusFilter === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected($statusFilter === 'nonaktif')>Dinonaktifkan</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="kelana-label">Verifikasi Email</label>
                    <select name="verified" class="kelana-input py-2 text-xs">
                        <option value="semua" @selected($verifiedFilter === 'semua')>Semua</option>
                        <option value="ya" @selected($verifiedFilter === 'ya')>Terverifikasi</option>
                        <option value="belum" @selected($verifiedFilter === 'belum')>Belum Verifikasi</option>
                    </select>
                </div>
                <button type="submit" class="btn-kelana-primary px-4 py-2 text-xs">Filter</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-[#e8dfd3] bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#efe7dc] text-left text-xs">
                    <thead class="bg-[#fdfbf6] text-[11px] font-bold text-[#936b49] uppercase">
                        <tr>
                            <th class="px-4 py-3">Kode &amp; Nama Pelanggan</th>
                            <th class="px-4 py-3">Kontak &amp; Verifikasi</th>
                            <th class="px-4 py-3">Total Order</th>
                            <th class="px-4 py-3">Belanja Lunas</th>
                            <th class="px-4 py-3">Status Akun</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f3ece2]">
                        @forelse($customers as $customer)
                            <tr class="hover:bg-[#fdfbf6]">
                                <td class="px-4 py-3.5">
                                    <p class="text-[10px] font-bold text-[#936b49]">{{ $customer->customer_code ?? '-' }}</p>
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="font-bold text-[#28211c] hover:text-[#52311a] hover:underline">
                                        {{ $customer->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3.5 text-[#5e524a]">
                                    <p>{{ $customer->email }}</p>
                                    <div class="mt-0.5 flex items-center gap-2 text-[11px]">
                                        <span>{{ $customer->phone ?: '-' }}</span>
                                        @if($customer->email_verified_at)
                                            <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">Terverifikasi</span>
                                        @else
                                            <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">Belum Verifikasi</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 font-semibold text-[#28211c]">
                                    {{ $customer->orders_count }} pesanan
                                </td>
                                <td class="px-4 py-3.5 font-bold text-[#52311a]">
                                    Rp {{ number_format((int) ($customer->total_spent ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($customer->is_active)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-800">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-[10px] font-bold text-red-800">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <a href="{{ route('admin.customers.show', $customer) }}"
                                        class="inline-flex rounded-lg border border-[#dfd2c0] px-3 py-1 text-[11px] font-bold text-[#52311a] hover:bg-[#f9eddd]">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-[#796b62]">
                                    Tidak ada pelanggan yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="border-t border-[#efe7dc] px-4 py-3">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

