@extends('layouts.admin')

@section('title', 'Profil Pelanggan '.$customer->name.' — Panel Admin')
@section('kicker', 'DETAIL & PEMULIHAN PELANGGAN')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="rounded bg-[#f9eddd] px-2.5 py-0.5 text-xs font-bold text-[#52311a]">{{ $customer->customer_code ?? 'CUS' }}</span>
                    <h1 class="font-serif text-xl font-semibold text-[#28211c]">{{ $customer->name }}</h1>
                    @if($customer->is_active)
                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-800">Akun Aktif</span>
                    @else
                        <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-bold text-red-800">Akun Dinonaktifkan</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-[#796b62]">
                    Bergabung sejak {{ $customer->created_at?->locale('id')->isoFormat('D MMMM YYYY') }}
                    &bull; Email: <strong>{{ $customer->email }}</strong>
                    ({{ $customer->email_verified_at ? 'Terverifikasi' : 'Belum terverifikasi' }})
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.customers.index') }}" class="btn-kelana-outline px-3 py-2 text-xs">&larr; Direktori</a>
                @if($whatsappRecoveryUrl !== '#')
                    <a href="{{ $whatsappRecoveryUrl }}" target="_blank" rel="noopener"
                        class="inline-flex items-center rounded-lg bg-emerald-700 px-3.5 py-2 text-xs font-bold text-white hover:bg-emerald-800">
                        Hubungi via WhatsApp &nearr;
                    </a>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            {{-- Kolom Kiri: Kontak, Pesanan Pelanggan & Cocokkan WA Manual --}}
            <div class="space-y-6 lg:col-span-8">
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs text-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Informasi Kontak &amp; Alamat Tersimpan</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <p><span class="text-[#796b62]">No. WhatsApp Utama:</span> <strong class="text-[#28211c]">{{ $customer->phone ?: '-' }}</strong> (Normalisasi: {{ $normalizedPhone ?: '-' }})</p>
                        <p><span class="text-[#796b62]">No. HP Cadangan:</span> <strong class="text-[#28211c]">{{ $customer->secondary_phone ?: '-' }}</strong></p>
                        <p class="sm:col-span-2"><span class="text-[#796b62]">Alamat Tersimpan:</span> <strong class="text-[#28211c]">{{ $customer->saved_address ?: 'Belum ada alamat tersimpan' }}</strong></p>
                        @if($customer->saved_address_detail)
                            <p class="sm:col-span-2"><span class="text-[#796b62]">Patokan:</span> {{ $customer->saved_address_detail }}</p>
                        @endif
                    </div>
                </div>

                {{-- Pesanan Akun Terdaftar --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Riwayat Pesanan Akun ({{ $customer->orders->count() }})</h2>
                    <div class="mt-3 space-y-2.5">
                        @forelse($customer->orders as $order)
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-3 text-xs">
                                <div>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#52311a] hover:underline">
                                        {{ $order->order_number }}
                                    </a>
                                    <span class="ml-2 text-[11px] text-[#796b62]">{{ $order->created_at?->format('d M Y') }} &bull; {{ $order->items->sum('quantity') }} item</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-[#28211c]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                    <span class="rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold text-[#52311a]">
                                        {{ $order->orderStatusLabel() }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-[#796b62]">Pelanggan ini belum memiliki riwayat pesanan di akunnya.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Kecocokan Pesanan Manual WhatsApp Tanpa User ID --}}
                @if($matchedGuestOrders->isNotEmpty())
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5 shadow-xs">
                        <h2 class="text-sm font-semibold text-amber-900">Pesanan Tamu/WA Cocok Berdasarkan Nomor HP ({{ $matchedGuestOrders->count() }})</h2>
                        <p class="mt-1 text-xs text-amber-800">
                            Pesanan berikut dibuat tanpa tautan akun tetapi nomor WhatsApp-nya cocok dengan {{ $customer->phone }}.
                        </p>
                        <div class="mt-3 space-y-2">
                            @foreach($matchedGuestOrders as $guestOrder)
                                <div class="flex items-center justify-between rounded-xl border border-amber-200 bg-white px-3.5 py-2.5 text-xs">
                                    <a href="{{ route('admin.orders.show', $guestOrder) }}" class="font-bold text-[#52311a] hover:underline">
                                        {{ $guestOrder->order_number }} ({{ $guestOrder->scheduled_date?->format('d M Y') }})
                                    </a>
                                    <span class="font-bold text-[#28211c]">Rp {{ number_format($guestOrder->total_amount, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Kolom Kanan: Toggle Aktif/Nonaktif & Log Bantuan Pemulihan WA --}}
            <div class="space-y-5 lg:col-span-4">
                <div class="rounded-2xl border {{ $customer->is_active ? 'border-red-200' : 'border-emerald-200' }} bg-white p-5 shadow-xs">
                    <h3 class="text-sm font-semibold {{ $customer->is_active ? 'text-red-800' : 'text-emerald-800' }}">
                        {{ $customer->is_active ? 'Nonaktifkan Akun Pelanggan' : 'Aktifkan Kembali Akun' }}
                    </h3>

                    @if($customer->is_active)
                        <p class="mt-1 text-xs text-[#796b62]">
                            Akun yang dinonaktifkan otomatis diblokir saat login atau checkout hingga diaktifkan kembali oleh Admin.
                        </p>
                        <form method="POST" action="{{ route('admin.customers.toggle-active', $customer) }}" class="mt-3 space-y-3">
                            @csrf
                            <div>
                                <label for="deactivated_reason" class="kelana-label text-red-800">Alasan Penonaktifan *</label>
                                <input id="deactivated_reason" name="deactivated_reason" type="text" required
                                    placeholder="Contoh: Aktivitas pesanan fiktif berulang" class="kelana-input text-xs">
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-red-700 px-4 py-2 text-xs font-bold text-white hover:bg-red-800">
                                Nonaktifkan Akun Ini
                            </button>
                        </form>
                    @else
                        <p class="mt-1 text-xs text-red-700">
                            Alasan sebelumnya: <strong>{{ $customer->deactivated_reason ?? '-' }}</strong>
                        </p>
                        <form method="POST" action="{{ route('admin.customers.toggle-active', $customer) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800">
                                Aktifkan Kembali Akun
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Catatan Bantuan Pemulihan Akun --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h3 class="text-sm font-semibold text-[#52311a]">Catat Bantuan Pemulihan Akun</h3>
                    <p class="mt-1 text-xs text-[#796b62]">
                        Catat bantuan verifikasi atau kendala login yang dibantu via WhatsApp.
                    </p>
                    <form method="POST" action="{{ route('admin.customers.recovery', $customer) }}" class="mt-3 space-y-3">
                        @csrf
                        <textarea name="note" rows="2" placeholder="Contoh: Sudah dibantu verifikasi email manual lewat WhatsApp." class="kelana-input text-xs"></textarea>
                        <button type="submit" class="btn-kelana-outline w-full py-2 text-xs">
                            Simpan Catatan Pemulihan
                        </button>
                    </form>

                    @if($recoveryLogs->isNotEmpty())
                        <div class="mt-4 space-y-2 border-t border-[#efe7dc] pt-3">
                            <p class="text-[11px] font-bold text-[#936b49] uppercase">Log Pemulihan Terakhir</p>
                            @foreach($recoveryLogs as $recLog)
                                <div class="rounded-lg bg-[#fdfbf6] p-2.5 text-[11px] text-[#5e524a]">
                                    <p>{{ $recLog->message }}</p>
                                    <span class="text-[10px] text-[#8d7d70]">{{ $recLog->created_at?->format('d M Y H:i') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

