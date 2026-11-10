@extends('layouts.app')

@section('title', 'Notifikasi Pelanggan — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('partials.customer-sidebar')

            <div class="min-w-0 flex-1 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="font-serif text-2xl font-bold text-[#52311a]">Notifikasi</h1>
                        <p class="text-xs text-[#796b62]">Semua informasi terbaru mengenai pesanan dan sistem akunmu.</p>
                    </div>
                    @if($notifications->isNotEmpty())
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="btn-kelana-outline rounded-full text-xs">Tandai Semua Dibaca</button>
                        </form>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('notifications.index') }}"
                        class="rounded-full px-4 py-1.5 text-xs font-semibold {{ $categoryFilter === 'semua' ? 'bg-[#52311a] text-white' : 'bg-[#f9eddd] text-[#52311a]' }}">
                        Semua ({{ $counts['all'] }})
                    </a>
                    <a href="{{ route('notifications.index', ['kategori' => 'pesanan']) }}"
                        class="rounded-full px-4 py-1.5 text-xs font-semibold {{ $categoryFilter === 'pesanan' ? 'bg-[#52311a] text-white' : 'bg-[#f9eddd] text-[#52311a]' }}">
                        Pesanan ({{ $counts['pesanan'] }})
                    </a>
                    <a href="{{ route('notifications.index', ['kategori' => 'sistem']) }}"
                        class="rounded-full px-4 py-1.5 text-xs font-semibold {{ $categoryFilter === 'sistem' ? 'bg-[#52311a] text-white' : 'bg-[#f9eddd] text-[#52311a]' }}">
                        Sistem ({{ $counts['sistem'] }})
                    </a>
                </div>

                @if($notifications->isEmpty())
                    <div class="rounded-2xl border border-[#e3d5c3] bg-white p-10 text-center shadow-xs">
                        <h2 class="text-base font-bold text-[#52311a]">Belum ada notifikasi</h2>
                        <p class="mt-1 text-xs text-[#796b62]">Informasi terbaru tentang pesanan dan sistem akan tampil di sini.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($notifications as $notif)
                            <form method="POST" action="{{ route('notifications.read', $notif) }}">
                                @csrf
                                <button type="submit"
                                    class="w-full rounded-2xl border p-4 text-left transition {{ $notif->read_at ? 'border-[#e3d5c3] bg-white hover:bg-[#fdfbf6]' : 'border-[#b18950] bg-[#f9eddd]/80 hover:bg-[#f2e4cd]' }}">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="rounded-full bg-[#52311a] px-2.5 py-0.5 text-[10px] font-bold text-white uppercase">{{ $notif->category }}</span>
                                            <h3 class="text-sm font-bold text-[#28211c]">{{ $notif->title }}</h3>
                                            @if(! $notif->read_at)
                                                <span class="h-2 w-2 rounded-full bg-red-600"></span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-[#796b62]">{{ $notif->created_at->locale('id')->isoFormat('D MMM YYYY · HH:mm') }} WIB</span>
                                    </div>
                                    <p class="mt-2 text-xs leading-relaxed text-[#5e524a]">{{ $notif->message }}</p>
                                </button>
                            </form>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $notifications->links() }}
                    </div>
                @endif

                @include('partials.customer-help-banner')
            </div>
        </div>
    </section>
@endsection

