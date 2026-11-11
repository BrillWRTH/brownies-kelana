<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Admin — Brownies Kelana')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-[#fcf3e3] text-[#2f2118] antialiased">
    @php
        $adminUnread = $sharedAdminUnreadCount ?? 0;
    @endphp

    <div class="flex min-h-screen">
        {{-- Sidebar Kiri Admin Panel (Cocoa Brown sesuai UI/UX) --}}
        <aside class="no-print hidden w-60 shrink-0 flex-col bg-[#674732] text-[#f7ebd5] lg:flex">
            <div class="px-6 pb-6 pt-7">
                <a href="{{ route('admin.dashboard') }}" class="block leading-tight">
                    <span class="block font-serif text-xl font-bold text-white">Brownies Kelana</span>
                    <span class="mt-0.5 block text-[11px] font-medium text-[#e8decb]/85">Panel Admin</span>
                </a>
            </div>

            <nav class="flex-1 space-y-1.5 px-4 text-xs font-semibold">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center justify-between rounded-xl px-4 py-2.5 transition {{ request()->routeIs('admin.dashboard') ? 'bg-[#492d1a] font-bold text-white shadow-xs' : 'text-[#f7ebd5] hover:bg-[#593822] hover:text-white' }}">
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.orders.index') }}"
                    class="flex items-center justify-between rounded-xl px-4 py-2.5 transition {{ request()->routeIs('admin.orders.index', 'admin.orders.create', 'admin.orders.show', 'admin.orders.edit') ? 'bg-[#492d1a] font-bold text-white shadow-xs' : 'text-[#f7ebd5] hover:bg-[#593822] hover:text-white' }}">
                    <span>Pesanan</span>
                </a>
                <a href="{{ route('admin.orders.recap') }}"
                    class="flex items-center justify-between rounded-xl px-4 py-2.5 transition {{ request()->routeIs('admin.orders.recap') ? 'bg-[#492d1a] font-bold text-white shadow-xs' : 'text-[#f7ebd5] hover:bg-[#593822] hover:text-white' }}">
                    <span>Rekap Produksi</span>
                </a>
                <a href="{{ route('admin.products.index') }}"
                    class="flex items-center justify-between rounded-xl px-4 py-2.5 transition {{ request()->routeIs('admin.products.*') ? 'bg-[#492d1a] font-bold text-white shadow-xs' : 'text-[#f7ebd5] hover:bg-[#593822] hover:text-white' }}">
                    <span>Produk</span>
                </a>
                <a href="{{ route('admin.reviews.index') }}"
                    class="flex items-center justify-between rounded-xl px-4 py-2.5 transition {{ request()->routeIs('admin.reviews.*') ? 'bg-[#492d1a] font-bold text-white shadow-xs' : 'text-[#f7ebd5] hover:bg-[#593822] hover:text-white' }}">
                    <span>Ulasan</span>
                </a>
                <a href="{{ route('admin.settings.index') }}"
                    class="flex items-center justify-between rounded-xl px-4 py-2.5 transition {{ request()->routeIs('admin.settings.*') ? 'bg-[#492d1a] font-bold text-white shadow-xs' : 'text-[#f7ebd5] hover:bg-[#593822] hover:text-white' }}">
                    <span>Pengaturan</span>
                </a>
                <a href="{{ route('admin.customers.index') }}"
                    class="flex items-center justify-between rounded-xl px-4 py-2.5 transition {{ request()->routeIs('admin.customers.*') ? 'bg-[#492d1a] font-bold text-white shadow-xs' : 'text-[#f7ebd5] hover:bg-[#593822] hover:text-white' }}">
                    <span>Pelanggan</span>
                </a>
                <a href="{{ route('admin.notifications.index') }}"
                    class="flex items-center justify-between rounded-xl px-4 py-2.5 transition {{ request()->routeIs('admin.notifications.*') ? 'bg-[#492d1a] font-bold text-white shadow-xs' : 'text-[#f7ebd5] hover:bg-[#593822] hover:text-white' }}">
                    <span>Notifikasi</span>
                    @if($adminUnread > 0)
                        <span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $adminUnread }}</span>
                    @endif
                </a>
            </nav>

            <div class="border-t border-[#7d563c]/60 p-4 space-y-2">
                <a href="{{ route('home') }}" target="_blank"
                    class="flex items-center justify-center gap-1.5 rounded-xl border border-[#8f6743] bg-[#593822]/60 px-3 py-2 text-xs font-semibold text-[#fcf3e3] transition hover:bg-[#492d1a]">
                    <span>Lihat Storefront</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                    </svg>
                </a>
                <button type="button" data-open-modal="admin-logout-modal"
                    class="w-full rounded-xl bg-[#492d1a]/70 px-3 py-2 text-xs font-semibold text-[#f7ebd5] transition hover:bg-red-800 hover:text-white">
                    Logout Admin
                </button>
            </div>
        </aside>

        {{-- Konten Utama Panel Admin --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Topbar Ringkas --}}
            <header class="no-print flex items-center justify-between border-b border-[#efe2ce] bg-[#fcf3e3]/90 px-4 py-3 sm:px-8 lg:py-4">
                <div class="flex items-center gap-3">
                    <span class="font-serif text-base font-bold text-[#593822] lg:hidden">Brownies Kelana</span>
                    <span class="hidden text-[11px] font-bold tracking-widest text-[#8f6743] uppercase lg:inline">@yield('kicker', 'ADMIN PANEL')</span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.notifications.index') }}" class="relative rounded-full border border-[#d9c8b4] bg-white p-2 text-[#593822] transition hover:bg-[#f7ebd5]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($adminUnread > 0)
                            <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-600 px-1 text-[9px] font-bold text-white">{{ $adminUnread }}</span>
                        @endif
                    </a>
                    <div class="text-right">
                        <p class="text-xs font-bold text-[#2f2118]">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-[#796b62]">Admin UMKM Kelana</p>
                    </div>
                </div>
            </header>

            {{-- Menu Mobile Horizontal Admin --}}
            <nav class="no-print flex gap-2 overflow-x-auto border-b border-[#efe2ce] bg-[#f7ebd5] px-4 py-2 text-xs font-semibold lg:hidden">
                <a href="{{ route('admin.dashboard') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.dashboard') ? 'bg-[#593822] text-white' : 'bg-white text-[#593822]' }}">Dashboard</a>
                <a href="{{ route('admin.orders.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.orders.*') && !request()->routeIs('admin.orders.recap') ? 'bg-[#593822] text-white' : 'bg-white text-[#593822]' }}">Pesanan</a>
                <a href="{{ route('admin.orders.recap') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.orders.recap') ? 'bg-[#593822] text-white' : 'bg-white text-[#593822]' }}">Rekap</a>
                <a href="{{ route('admin.products.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.products.*') ? 'bg-[#593822] text-white' : 'bg-white text-[#593822]' }}">Produk</a>
                <a href="{{ route('admin.reviews.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.reviews.*') ? 'bg-[#593822] text-white' : 'bg-white text-[#593822]' }}">Ulasan</a>
                <a href="{{ route('admin.settings.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.settings.*') ? 'bg-[#593822] text-white' : 'bg-white text-[#593822]' }}">Pengaturan</a>
                <a href="{{ route('admin.customers.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.customers.*') ? 'bg-[#593822] text-white' : 'bg-white text-[#593822]' }}">Pelanggan</a>
                <a href="{{ route('admin.notifications.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.notifications.*') ? 'bg-[#593822] text-white' : 'bg-white text-[#593822]' }}">Notifikasi</a>
            </nav>

            {{-- Flash Notifications Admin --}}
            <div class="no-print px-4 pt-4 sm:px-8">
                @if(session('success'))
                    <div class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                        {{ session('success') }}
                        @if(session('wa_launch_url'))
                            <a href="{{ session('wa_launch_url') }}" target="_blank" rel="noopener" class="ml-3 inline-flex items-center gap-1 rounded-lg bg-emerald-700 px-3 py-1 text-xs font-bold text-white hover:bg-emerald-800">
                                <span>Buka Chat WhatsApp Sekarang</span>
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900">
                        {{ session('error') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                        <p class="font-bold">Terjadi kesalahan validasi:</p>
                        <ul class="mt-1 list-disc pl-4">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <main class="flex-1 p-4 sm:px-8 sm:py-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Modal Konfirmasi Logout Admin --}}
    <div id="admin-logout-modal" class="no-print hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-2xl border border-[#8f6743] bg-[#fcf3e3] p-6 shadow-2xl">
            <h3 class="font-serif text-lg font-bold text-[#593822]">Logout dari Panel Admin?</h3>
            <p class="mt-1 text-xs text-[#5e4d3e]">
                Sesi admin Anda ({{ auth()->user()->email }}) akan diakhiri. Anda perlu login kembali untuk mengelola pesanan.
            </p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" data-close-modal="admin-logout-modal" class="btn-kelana-outline text-xs">Tetap di Panel</button>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-red-700 px-4 py-2 text-xs font-bold text-white hover:bg-red-800">Logout Admin</button>
                </form>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
