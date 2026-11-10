@extends('layouts.admin')

@section('title', 'Inbox Notifikasi Admin — Panel Admin')
@section('kicker', 'NOTIFIKASI & TEMPLATE PESAN')

@section('content')
    <div class="space-y-5">
        {{-- Sub-navigasi Modul Notifikasi --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#e8dfd3] bg-white p-4 shadow-xs">
            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                <a href="{{ route('admin.notifications.index') }}"
                    class="rounded-xl bg-[#52311a] px-4 py-2 text-white">
                    Inbox Admin @if($unreadCount > 0) ({{ $unreadCount }}) @endif
                </a>
                <a href="{{ route('admin.notifications.templates') }}"
                    class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">
                    Template Pesan
                </a>
                <a href="{{ route('admin.notifications.create') }}"
                    class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">
                    Kirim Notifikasi Khusus
                </a>
                <a href="{{ route('admin.notifications.history') }}"
                    class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">
                    Riwayat Kirim
                </a>
            </div>

            @if($unreadCount > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn-kelana-outline px-3.5 py-1.5 text-xs">
                        Tandai Semua Sudah Dibaca
                    </button>
                </form>
            @endif
        </div>

        {{-- Filter Inbox --}}
        <form method="GET" action="{{ route('admin.notifications.index') }}"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#e8dfd3] bg-white p-4 shadow-xs text-xs">
            <div class="flex flex-wrap gap-2 font-semibold">
                <a href="{{ route('admin.notifications.index', ['category' => 'semua']) }}"
                    class="rounded-full px-3.5 py-1.5 {{ $categoryFilter === 'semua' ? 'bg-[#52311a] text-white' : 'bg-[#fdfbf6] text-[#5e524a]' }}">
                    Semua
                </a>
                <a href="{{ route('admin.notifications.index', ['category' => 'pesanan']) }}"
                    class="rounded-full px-3.5 py-1.5 {{ $categoryFilter === 'pesanan' ? 'bg-[#52311a] text-white' : 'bg-[#fdfbf6] text-[#5e524a]' }}">
                    Kejadian Pesanan
                </a>
                <a href="{{ route('admin.notifications.index', ['category' => 'sistem']) }}"
                    class="rounded-full px-3.5 py-1.5 {{ $categoryFilter === 'sistem' ? 'bg-[#52311a] text-white' : 'bg-[#fdfbf6] text-[#5e524a]' }}">
                    Sistem &amp; Akun
                </a>
            </div>

            <label class="inline-flex items-center gap-2 font-semibold text-[#5e524a]">
                <input type="checkbox" name="unread" value="1" onchange="this.form.submit()" @checked($unreadOnly)
                    class="rounded border-[#b18950] text-[#52311a]">
                <span>Tampilkan belum dibaca saja</span>
                <input type="hidden" name="category" value="{{ $categoryFilter }}">
            </label>
        </form>

        {{-- Daftar Notifikasi Inbox --}}
        <div class="space-y-3">
            @forelse($notifications as $notif)
                <div class="flex flex-col gap-3 rounded-2xl border {{ $notif->read_at ? 'border-[#e8dfd3] bg-white' : 'border-[#b18950] bg-[#fdfbf6]' }} p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            @if(!$notif->read_at)
                                <span class="h-2 w-2 rounded-full bg-red-600"></span>
                            @endif
                            <span class="rounded bg-[#f9eddd] px-2 py-0.5 text-[10px] font-bold text-[#52311a] uppercase">
                                {{ $notif->category }}
                            </span>
                            <h2 class="text-sm font-bold text-[#28211c]">{{ $notif->title }}</h2>
                        </div>
                        <p class="mt-1 text-xs text-[#5e524a]">{{ $notif->message }}</p>
                        <p class="mt-1 text-[11px] text-[#8d7d70]">
                            {{ $notif->created_at?->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                            @if($notif->order)
                                &bull; Order {{ $notif->order->order_number }}
                            @endif
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if(!$notif->read_at)
                            <a href="{{ route('admin.notifications.index', array_merge(request()->query(), ['read' => $notif->id])) }}"
                                class="btn-kelana-outline px-3 py-1.5 text-[11px]">
                                Tandai Dibaca
                            </a>
                        @endif
                        @if($notif->action_url)
                            <a href="{{ $notif->action_url }}" class="btn-kelana-primary px-3.5 py-1.5 text-[11px]">
                                Buka Detail &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-10 text-center text-sm text-[#796b62]">
                    Tidak ada notifikasi pada Inbox Admin untuk filter ini.
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div>{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection

