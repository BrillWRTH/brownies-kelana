<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Admin — Brownies Kelana')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-[#f8f5f1] text-[#28211c] antialiased">
    @php
        $adminUnread = $sharedAdminUnreadCount ?? 0;
    @endphp

    <div class="flex min-h-screen">
        {{-- Sidebar Kiri Admin Panel --}}
        <aside class="no-print hidden w-64 shrink-0 flex-col border-r border-[#e8dfd3] bg-white lg:flex">
            <div class="flex items-center gap-3 border-b border-[#efe7dc] px-5 py-4">
                <img src="{{ asset('images/kelana/logo-kelana.png') }}" alt="Logo Brownies Kelana" class="h-9 w-9 rounded-full border border-[#d6c5b3] object-cover">
                <div class="leading-tight">
                    <span class="block text-sm font-bold text-[#52311a]">Brownies Kelana</span>
                    <span class="block text-[10px] font-semibold tracking-wider text-[#936b49] uppercase">Panel Admin</span>
                </div>
            </div>

            <nav class="flex-1 space-y-1 p-4 text-xs font-semibold">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('admin.dashboard') ? 'bg-[#52311a] text-white' : 'text-[#5e524a] hover:bg-[#f9eddd] hover:text-[#52311a]' }}">
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.orders.index') }}"
                    class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('admin.orders.index', 'admin.orders.create', 'admin.orders.show', 'admin.orders.edit') ? 'bg-[#52311a] text-white' : 'text-[#5e524a] hover:bg-[#f9eddd] hover:text-[#52311a]' }}">
                    <span>Pesanan</span>
                </a>
                <a href="{{ route('admin.orders.recap') }}"
                    class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('admin.orders.recap') ? 'bg-[#52311a] text-white' : 'text-[#5e524a] hover:bg-[#f9eddd] hover:text-[#52311a]' }}">
                    <span>Rekap Produksi</span>
                </a>
                <a href="{{ route('admin.products.index') }}"
                    class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('admin.products.*') ? 'bg-[#52311a] text-white' : 'text-[#5e524a] hover:bg-[#f9eddd] hover:text-[#52311a]' }}">
                    <span>Produk</span>
                </a>
                <a href="{{ route('admin.reviews.index') }}"
                    class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('admin.reviews.*') ? 'bg-[#52311a] text-white' : 'text-[#5e524a] hover:bg-[#f9eddd] hover:text-[#52311a]' }}">
                    <span>Ulasan</span>
                </a>
                <a href="{{ route('admin.customers.index') }}"
                    class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('admin.customers.*') ? 'bg-[#52311a] text-white' : 'text-[#5e524a] hover:bg-[#f9eddd] hover:text-[#52311a]' }}">
                    <span>Pelanggan</span>
                </a>
                <a href="{{ route('admin.notifications.index') }}"
                    class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('admin.notifications.*') ? 'bg-[#52311a] text-white' : 'text-[#5e524a] hover:bg-[#f9eddd] hover:text-[#52311a]' }}">
                    <span>Notifikasi</span>
                    @if($adminUnread > 0)
                        <span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $adminUnread }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.settings.index') }}"
                    class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('admin.settings.*') ? 'bg-[#52311a] text-white' : 'text-[#5e524a] hover:bg-[#f9eddd] hover:text-[#52311a]' }}">
                    <span>Pengaturan</span>
                </a>
            </nav>

            <div class="border-t border-[#efe7dc] p-4">
                <a href="{{ route('home') }}" target="_blank" class="mb-2 block rounded-lg border border-[#dfd2c0] px-3 py-2 text-center text-xs font-semibold text-[#52311a] hover:bg-[#f9eddd]">
                    Lihat Storefront &nearr;
                </a>
                <button type="button" data-open-modal="admin-logout-modal"
                    class="w-full rounded-lg bg-[#f9eddd] px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                    Logout Admin
                </button>
            </div>
        </aside>

        {{-- Konten Utama Panel Admin --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Header Mobile Admin & Topbar --}}
            <header class="no-print flex items-center justify-between border-b border-[#e8dfd3] bg-white px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold tracking-wider text-[#936b49] uppercase">@yield('kicker', 'ADMIN PANEL')</span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.notifications.index') }}" class="relative rounded-full border border-[#e3d5c3] p-2 text-[#52311a] hover:bg-[#f9eddd]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($adminUnread > 0)
                            <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-600 px-1 text-[9px] font-bold text-white">{{ $adminUnread }}</span>
                        @endif
                    </a>
                    <div class="text-right">
                        <p class="text-xs font-bold text-[#28211c]">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-[#8d7d70]">Admin UMKM Kelana</p>
                    </div>
                </div>
            </header>

            {{-- Menu Mobile Horizontal Admin --}}
            <nav class="no-print flex gap-2 overflow-x-auto border-b border-[#e8dfd3] bg-[#fdfbf6] px-4 py-2 text-xs font-semibold lg:hidden">
                <a href="{{ route('admin.dashboard') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.dashboard') ? 'bg-[#52311a] text-white' : 'bg-white text-[#52311a]' }}">Dashboard</a>
                <a href="{{ route('admin.orders.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.orders.*') && !request()->routeIs('admin.orders.recap') ? 'bg-[#52311a] text-white' : 'bg-white text-[#52311a]' }}">Pesanan</a>
                <a href="{{ route('admin.orders.recap') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.orders.recap') ? 'bg-[#52311a] text-white' : 'bg-white text-[#52311a]' }}">Rekap</a>
                <a href="{{ route('admin.products.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.products.*') ? 'bg-[#52311a] text-white' : 'bg-white text-[#52311a]' }}">Produk</a>
                <a href="{{ route('admin.reviews.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.reviews.*') ? 'bg-[#52311a] text-white' : 'bg-white text-[#52311a]' }}">Ulasan</a>
                <a href="{{ route('admin.customers.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.customers.*') ? 'bg-[#52311a] text-white' : 'bg-white text-[#52311a]' }}">Pelanggan</a>
                <a href="{{ route('admin.notifications.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.notifications.*') ? 'bg-[#52311a] text-white' : 'bg-white text-[#52311a]' }}">Notifikasi</a>
                <a href="{{ route('admin.settings.index') }}" class="shrink-0 rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.settings.*') ? 'bg-[#52311a] text-white' : 'bg-white text-[#52311a]' }}">Pengaturan</a>
            </nav>

            {{-- Flash Notifications Admin --}}
            <div class="no-print px-4 pt-4 sm:px-6">
                @if(session('success'))
                    <div class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                        {{ session('success') }}
                        @if(session('wa_launch_url'))
                            <a href="{{ session('wa_launch_url') }}" target="_blank" rel="noopener" class="ml-3 inline-flex items-center rounded-lg bg-emerald-700 px-3 py-1 text-xs font-bold text-white hover:bg-emerald-800">
                                Buka Chat WhatsApp Sekarang &nearr;
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

            <main class="flex-1 p-4 sm:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Modal Konfirmasi Logout Admin --}}
    <div id="admin-logout-modal" class="no-print hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-2xl border border-[#e3d5c3] bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-[#28211c]">Logout dari Panel Admin?</h3>
            <p class="mt-1 text-xs text-[#796b62]">
                Sesi admin Anda ({{ auth()->user()->email }}) akan diakhiri. Anda perlu login kembali untuk mengelola pesanan.
            </p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" data-close-modal="admin-logout-modal" class="btn-kelana-outline text-xs">Tetap di Panel</button>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn-kelana-primary bg-red-700 text-xs hover:bg-red-800">Logout Admin</button>
                </form>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

