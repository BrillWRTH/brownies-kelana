<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Brownies Kelana — Dibuat Untuk Dinikmati')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-[#fcf3e3] text-[#2f2118] antialiased">
    @php
        $store = $sharedStoreSetting ?? \App\Models\StoreSetting::current();
        $cart = $sharedCart ?? null;
        $cartCount = $cart ? $cart->totalQuantity() : 0;
        $cartSubtotal = $cart ? $cart->subtotalAmount() : 0;
        $recentNotifications = $sharedCustomerNotifications ?? collect();
        $unreadNotifCount = $sharedCustomerUnreadCount ?? 0;
    @endphp

    {{-- Navigasi Utama Pelanggan sesuai UI/UX Brownies Kelana --}}
    <header class="sticky top-0 z-40 border-b border-[#e5d5bf] bg-[#fcf3e3]/95 backdrop-blur-sm">
        {{-- Ornamen Pita Sudut Cokelat-Emas di Kiri Atas --}}
        <div class="pointer-events-none absolute left-0 top-0 h-16 w-20 overflow-hidden sm:h-[72px] sm:w-24">
            <svg viewBox="0 0 96 72" fill="none" class="h-full w-full">
                <polygon points="0,0 88,0 44,36 0,0" fill="#d9c6af" />
                <polygon points="0,0 44,36 0,72" fill="#593822" />
                <polygon points="44,36 88,0 96,0 52,44" fill="#c7a26d" opacity="0.55" />
            </svg>
        </div>

        <div class="relative mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
            {{-- Logo Circular + Wordmark Serif --}}
            <a href="{{ route('home') }}" class="group relative z-10 flex items-center gap-3 pl-6 sm:pl-8">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-[#a4804f] bg-white p-0.5 shadow-sm transition group-hover:scale-105 sm:h-11 sm:w-11">
                    <img src="{{ asset('images/kelana/logo-kelana.png') }}" alt="Logo Brownies Kelana" class="h-full w-full rounded-full object-cover">
                </div>
                <span class="font-serif text-lg font-bold tracking-tight text-[#593822] sm:text-2xl">
                    {{ $store->store_name }}
                </span>
            </a>

            {{-- Menu Navigasi Tengah Desktop --}}
            <nav class="hidden items-center gap-8 text-xs tracking-wider uppercase md:flex">
                <a href="{{ route('home') }}"
                    class="transition {{ request()->routeIs('home') ? 'font-extrabold text-[#593822]' : 'font-semibold text-[#6f5d50] hover:text-[#593822]' }}">
                    Home
                </a>
                <a href="{{ route('products.index') }}"
                    class="transition {{ request()->routeIs('products.*') ? 'font-extrabold text-[#593822]' : 'font-semibold text-[#6f5d50] hover:text-[#593822]' }}">
                    Produk
                </a>
                <a href="{{ route('about') }}"
                    class="transition {{ request()->routeIs('about') ? 'font-extrabold text-[#593822]' : 'font-semibold text-[#6f5d50] hover:text-[#593822]' }}">
                    Tentang Kami
                </a>
            </nav>

            {{-- Aksi Kanan: Autentikasi, Keranjang & Lonceng Notifikasi --}}
            <div class="flex items-center gap-2 sm:gap-2.5">
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-xl bg-[#593822] px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-[#492d1a]">
                            Panel Admin
                        </a>
                    @else
                        {{-- Tombol Keranjang (Slide-over Drawer) --}}
                        <button type="button" data-open-cart-drawer
                            class="relative flex h-9 w-9 items-center justify-center rounded-xl border border-[#dfcfb9] bg-[#f7ebd5]/80 text-[#593822] transition hover:bg-[#efe2ce]"
                            aria-label="Buka Keranjang Belanja">
                            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.4.4-.1 1.2.5 1.2H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            @if($cartCount > 0)
                                <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-[#593822] px-1 text-[10px] font-bold text-white">
                                    {{ $cartCount }}
                                </span>
                            @endif
                        </button>

                        {{-- Popover Notifikasi Lonceng Pelanggan --}}
                        <div class="relative">
                            <button type="button" data-dropdown-toggle="customer-notif-popover"
                                class="relative flex h-9 w-9 items-center justify-center rounded-xl border border-[#dfcfb9] bg-[#f7ebd5]/80 text-[#593822] transition hover:bg-[#efe2ce]"
                                aria-label="Notifikasi Pelanggan">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                @if($unreadNotifCount > 0)
                                    <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                                        {{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}
                                    </span>
                                @endif
                            </button>

                            <div id="customer-notif-popover" data-dropdown-menu class="hidden absolute right-0 mt-2 w-80 rounded-2xl border border-[#e3d5c3] bg-white p-3.5 shadow-xl">
                                <div class="mb-2 flex items-center justify-between border-b border-[#f0e5d6] pb-2">
                                    <span class="text-xs font-bold text-[#593822]">Notifikasi Terbaru</span>
                                    @if($unreadNotifCount > 0)
                                        <form method="POST" action="{{ route('notifications.read-all') }}">
                                            @csrf
                                            <button type="submit" class="text-[11px] font-semibold text-[#936b49] hover:text-[#593822]">Tandai semua dibaca</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="space-y-2">
                                    @forelse($recentNotifications as $notif)
                                        <form method="POST" action="{{ route('notifications.read', $notif) }}">
                                            @csrf
                                            <button type="submit" class="w-full rounded-xl p-2.5 text-left transition {{ $notif->read_at ? 'bg-[#fcf3e3]/60 hover:bg-[#f7ebd5]' : 'bg-[#f7ebd5] hover:bg-[#efe2ce]' }}">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-xs font-bold text-[#593822]">{{ $notif->title }}</span>
                                                    <span class="text-[10px] text-[#8d7d70]">{{ $notif->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="mt-0.5 line-clamp-2 text-[11px] text-[#5e524a]">{{ $notif->message }}</p>
                                            </button>
                                        </form>
                                    @empty
                                        <p class="py-4 text-center text-xs text-[#8d7d70]">Belum ada notifikasi pesanan.</p>
                                    @endforelse
                                </div>
                                <a href="{{ route('notifications.index') }}" class="mt-2 block rounded-lg bg-[#f7ebd5] py-1.5 text-center text-xs font-semibold text-[#593822] hover:bg-[#efe2ce]">
                                    Lihat Semua Notifikasi
                                </a>
                            </div>
                        </div>

                        {{-- Dropdown Profil Cepat Pelanggan --}}
                        <div class="relative">
                            <button type="button" data-dropdown-toggle="customer-user-menu"
                                class="flex items-center gap-2 rounded-full border border-[#dfcfb9] bg-[#efe2ce] py-1 pl-1.5 pr-3 text-xs font-semibold text-[#593822] transition hover:bg-[#e5d2b5]">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#593822] text-[11px] font-bold text-white">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden max-w-[100px] truncate sm:inline">{{ explode(' ', auth()->user()->name)[0] }}</span>
                            </button>

                            <div id="customer-user-menu" data-dropdown-menu class="hidden absolute right-0 mt-2 w-56 rounded-2xl border border-[#e3d5c3] bg-white p-2 shadow-xl">
                                <div class="border-b border-[#f0e5d6] px-3 py-2">
                                    <p class="truncate text-xs font-bold text-[#2f2118]">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-[11px] text-[#8d7d70]">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('dashboard') }}" class="mt-1 block rounded-lg px-3 py-2 text-xs font-medium text-[#2f2118] hover:bg-[#f7ebd5]">Dashboard Pesanan</a>
                                <a href="{{ route('notifications.index') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-[#2f2118] hover:bg-[#f7ebd5]">Notifikasi</a>
                                <a href="{{ route('profile.show') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-[#2f2118] hover:bg-[#f7ebd5]">Profil Saya</a>
                                <button type="button" data-open-modal="customer-logout-modal" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-red-700 hover:bg-red-50">
                                    Keluar
                                </button>
                            </div>
                        </div>
                    @endif
                @else
                    {{-- Pill Masuk/Daftar + Ikon Keranjang & Notifikasi sesuai UI/UX --}}
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-[#dfcfb9] bg-[#efe2ce] px-3.5 py-2 text-xs font-semibold text-[#593822] transition hover:bg-[#e5d2b5]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                        <span>Masuk/Daftar</span>
                    </a>

                    <button type="button" data-open-cart-drawer
                        class="relative flex h-9 w-9 items-center justify-center rounded-xl border border-[#dfcfb9] bg-[#f7ebd5]/80 text-[#593822] transition hover:bg-[#efe2ce]"
                        aria-label="Buka Keranjang Belanja">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.4.4-.1 1.2.5 1.2H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        @if($cartCount > 0)
                            <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-[#593822] px-1 text-[10px] font-bold text-white">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </button>

                    <a href="{{ route('login') }}"
                        class="hidden h-9 w-9 items-center justify-center rounded-xl border border-[#dfcfb9] bg-[#f7ebd5]/80 text-[#593822] transition hover:bg-[#efe2ce] sm:flex"
                        aria-label="Notifikasi Pesanan" title="Masuk untuk melihat notifikasi">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </a>
                @endauth
            </div>
        </div>

        {{-- Navigasi Mobile Sederhana --}}
        <div class="flex items-center justify-center gap-6 border-t border-[#e9d9c2] bg-[#f7ebd5] py-2 text-[11px] font-bold uppercase md:hidden">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-[#593822]' : 'text-[#6f5d50]' }}">Home</a>
            <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'text-[#593822]' : 'text-[#6f5d50]' }}">Produk</a>
            <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-[#593822]' : 'text-[#6f5d50]' }}">Tentang Kami</a>
            <a href="{{ route('cart.index') }}" class="text-[#6f5d50]">Keranjang</a>
        </div>
    </header>

    {{-- Notifikasi Flash Session --}}
    @if(session('success') || session('error') || $errors->any())
        <div class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 shadow-xs">
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900 shadow-xs">
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="mt-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                    <p class="font-bold">Periksa kembali input Anda:</p>
                    <ul class="mt-1 list-disc pl-4">
                        @foreach($errors->all() as $errorMessage)
                            <li>{{ $errorMessage }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- Konten Utama Halaman --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer Brand Brownies Kelana dengan Kurva Gelombang Cocoa --}}
    <footer class="relative mt-16 text-[#f7ebd5]">
        <div class="overflow-hidden leading-none text-[#593822]">
            <svg viewBox="0 0 1440 84" preserveAspectRatio="none" class="block h-12 w-full sm:h-16">
                <path fill="currentColor" d="M0,42 C240,0 480,0 740,38 C1000,76 1240,68 1440,22 L1440,84 L0,84 Z"></path>
            </svg>
        </div>

        <div class="bg-[#593822] pb-10 pt-5">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 md:grid-cols-4 lg:px-8">
                <div>
                    <h3 class="font-serif text-xl font-bold text-white">{{ $store->store_name }}</h3>
                    <p class="text-[9px] font-semibold tracking-[0.2em] text-[#e8decb] uppercase">{{ $store->tagline }}</p>
                    <p class="mt-4 text-xs leading-relaxed text-[#e8decb]/90">
                        Kelezatan yang mengajakmu berhenti sejenak. Untuk diri sendiri, untuk berbagi, untuk momen yang berarti.
                    </p>
                    <div class="mt-5 flex items-center gap-3">
                        <a href="{{ $store->whatsappUrl('Halo Brownies Kelana, saya ingin bertanya tentang produk.') }}" target="_blank" rel="noopener"
                            aria-label="WhatsApp Brownies Kelana"
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-[#c7a26d]/60 text-[#f7ebd5] transition hover:bg-[#a4804f] hover:text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </a>
                        <a href="mailto:{{ $store->email }}" aria-label="Email Brownies Kelana"
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-[#c7a26d]/60 text-[#f7ebd5] transition hover:bg-[#a4804f] hover:text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-[11px] font-bold tracking-wider text-white uppercase">Jelajahi Kelana</h4>
                    <ul class="mt-3.5 space-y-2 text-xs text-[#e8decb]/90">
                        <li><a href="{{ route('about') }}" class="hover:text-white">Tentang kami</a></li>
                        <li><a href="{{ route('products.index') }}" class="hover:text-white">Koleksi kue &amp; jajanan terkini</a></li>
                        <li><a href="{{ route('products.index', ['k3mart' => 1]) }}" class="hover:text-white">K3Mart Corner</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-[11px] font-bold tracking-wider text-white uppercase">Bantuan Pesanan</h4>
                    <ul class="mt-3.5 space-y-2 text-xs text-[#e8decb]/90">
                        <li><a href="{{ route('orders.guide') }}" class="hover:text-white">Cara pemesanan</a></li>
                        <li><a href="{{ route('shipping.info') }}" class="hover:text-white">Pengiriman &amp; pengambilan</a></li>
                        <li><a href="{{ route('allergen.info') }}" class="hover:text-white">Informasi alergen</a></li>
                        <li><a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="hover:text-white">Akun saya</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-[11px] font-bold tracking-wider text-white uppercase">Hubungi Kami</h4>
                    <ul class="mt-3.5 space-y-2 text-xs text-[#e8decb]/90">
                        <li>WhatsApp CS: {{ $store->whatsapp_number }}</li>
                        <li>Email: {{ $store->email }}</li>
                    </ul>
                    <a href="{{ $store->whatsappUrl('Halo Brownies Kelana, saya ingin bertanya mengenai produk & pemesanan.') }}" target="_blank" rel="noopener"
                        class="mt-5 inline-flex items-center gap-2 text-[11px] font-bold tracking-wider text-white uppercase transition hover:text-[#e9dcc7]">
                        <span>Mulai Percakapan</span>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M7 7h10v10"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="mx-auto mt-10 flex max-w-7xl flex-col items-center justify-between gap-3 border-t border-[#c7a26d]/35 px-4 pt-6 text-[11px] text-[#e8decb]/80 sm:flex-row sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} {{ $store->store_name }}. Seluruh hak cipta dilindungi.</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('privacy') }}" class="hover:text-white">Kebijakan privasi</a>
                    <span>&middot;</span>
                    <a href="{{ route('terms') }}" class="hover:text-white">Syarat &amp; ketentuan</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Slide-Over Cart Drawer (Global) sesuai UI/UX --}}
    <div id="kelana-cart-backdrop" class="hidden fixed inset-0 z-50 bg-black/45 backdrop-blur-[1px]"></div>
    <aside id="kelana-cart-drawer" data-auto-open="{{ session('open_cart_drawer') ? 'true' : 'false' }}"
        class="fixed right-0 top-0 z-50 flex h-full w-full max-w-md translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300">
        <div class="flex items-center justify-between border-b border-[#efe2ce] bg-white px-6 py-4">
            <h2 class="text-sm font-semibold text-[#2f2118]">Keranjang</h2>
            <button type="button" data-close-cart-drawer class="rounded-lg p-1 text-[#2f2118] transition hover:bg-[#f7ebd5]" aria-label="Tutup Keranjang">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            @if($cart && $cart->items->isNotEmpty())
                <div class="space-y-4">
                    @foreach($cart->items as $item)
                        <div class="flex gap-3 rounded-2xl border border-[#e3d5c3] bg-[#fcf3e3]/50 p-3">
                            <img src="{{ $item->product->primaryImageUrl() }}" alt="{{ $item->product->name }}" class="h-16 w-16 shrink-0 rounded-xl object-cover">
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate font-serif text-sm font-bold text-[#2f2118]">{{ $item->product->name }}</h3>
                                <p class="text-xs text-[#6f5d50]">
                                    {{ $item->variant?->name ?? 'Reguler' }} &middot; Rp {{ number_format($item->unitPrice(), 0, ',', '.') }}
                                </p>
                                @if($item->custom_note)
                                    <p class="mt-0.5 text-[11px] font-medium text-[#936b49]">Tulisan: "{{ $item->custom_note }}"</p>
                                @endif

                                <div class="mt-2 flex items-center justify-between">
                                    <div class="inline-flex items-center rounded-lg border border-[#d9c8b4] bg-white">
                                        <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ max(0, $item->quantity - 1) }}">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-bold text-[#593822] hover:bg-[#f7ebd5]">&minus;</button>
                                        </form>
                                        <span class="px-2.5 text-xs font-bold">{{ $item->quantity }}</span>
                                        <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-bold text-[#593822] hover:bg-[#f7ebd5]">+</button>
                                        </form>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-[#593822]">Rp {{ number_format($item->lineSubtotal(), 0, ',', '.') }}</span>
                                        <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[11px] font-semibold text-red-600 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex h-full flex-col items-center justify-center py-12 text-center">
                    <div class="text-[#b5aba2]">
                        <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-medium text-[#9e948b]">Keranjang Kosong</p>
                </div>
            @endif
        </div>

        <div class="border-t border-[#efe2ce] bg-white px-6 py-5">
            @if($cart && $cart->items->isNotEmpty())
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-xs font-medium text-[#6f5d50]">Subtotal sementara ({{ $cartCount }} item)</span>
                    <span class="text-base font-extrabold text-[#593822]">Rp {{ number_format($cartSubtotal, 0, ',', '.') }}</span>
                </div>
                <div class="space-y-2.5">
                    <a href="{{ route('cart.index') }}" class="flex w-full items-center justify-center rounded-xl border border-[#a67251] bg-white py-2.5 text-xs font-semibold text-[#593822] transition hover:bg-[#fcf3e3]">
                        Lihat Keranjang
                    </a>
                    <div class="flex justify-end">
                        <a href="{{ route('checkout.index') }}" class="flex w-full items-center justify-center rounded-xl bg-[#593822] px-6 py-3 text-xs font-bold tracking-wider text-white uppercase shadow-xs transition hover:bg-[#492d1a]">
                            Beli Sekarang
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-2.5">
                    <a href="{{ route('products.index') }}" class="flex w-full items-center justify-center rounded-xl border border-[#a67251] bg-white py-2.5 text-xs font-semibold text-[#593822] transition hover:bg-[#fcf3e3]">
                        Lanjut Belanja
                    </a>
                    <div class="flex justify-end">
                        <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center rounded-xl bg-[#593822] px-7 py-2.5 text-xs font-bold tracking-wider text-white uppercase shadow-xs transition hover:bg-[#492d1a]">
                            Beli Sekarang
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </aside>

    {{-- Modal Konfirmasi Logout Pelanggan (dengan Maskot Brownies) --}}
    @auth
        @if(! auth()->user()->isAdmin())
            <div id="customer-logout-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md rounded-2xl border border-[#e3d5c3] bg-[#fcf3e3] p-6 text-center shadow-2xl">
                    <img src="{{ asset('images/kelana/mascot-brownie.png') }}" alt="Maskot Brownies Kelana" class="mx-auto h-24 w-24 object-contain">
                    <h3 class="mt-3 font-serif text-xl font-bold text-[#593822]">Yakin ingin keluar dari akun?</h3>
                    <p class="mt-1 text-xs leading-relaxed text-[#6f5d50]">
                        Kamu akan keluar dari akun Brownies Kelana dan perlu masuk kembali untuk memantau progres pesanan.
                    </p>
                    <div class="mt-5 flex items-center justify-center gap-3">
                        <button type="button" data-close-modal="customer-logout-modal" class="btn-kelana-outline text-xs">
                            Batal
                        </button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-kelana-primary bg-red-700 text-xs hover:bg-red-800">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    @stack('scripts')
</body>
</html>
