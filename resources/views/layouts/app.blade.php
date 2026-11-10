<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Brownies Kelana — Dibuat Untuk Dinikmati')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-[#fdfbf6] text-[#28211c] antialiased">
    @php
        $store = $sharedStoreSetting ?? \App\Models\StoreSetting::current();
        $cart = $sharedCart ?? null;
        $cartCount = $cart ? $cart->totalQuantity() : 0;
        $cartSubtotal = $cart ? $cart->subtotalAmount() : 0;
        $recentNotifications = $sharedCustomerNotifications ?? collect();
        $unreadNotifCount = $sharedCustomerUnreadCount ?? 0;
    @endphp

    {{-- Navigasi Utama Pelanggan --}}
    <header class="sticky top-0 z-40 border-b border-[#ebdecb] bg-[#fdfbf6]/95 backdrop-blur-sm">
        <div class="relative mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            {{-- Ornamen Pita Sudut Khas Brand --}}
            <a href="{{ route('home') }}" class="group flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full border border-[#d6c5b3] bg-white p-1 shadow-xs transition group-hover:scale-105">
                    <img src="{{ asset('images/kelana/logo-kelana.png') }}" alt="Logo Brownies Kelana" class="h-full w-full rounded-full object-cover">
                </div>
                <div class="leading-tight">
                    <span class="block text-sm font-bold tracking-tight text-[#52311a] sm:text-base">{{ $store->store_name }}</span>
                    <span class="hidden text-[10px] font-medium tracking-widest text-[#936b49] uppercase sm:block">Sidoarjo &bull; Surabaya</span>
                </div>
            </a>

            {{-- Menu Navigasi Tengah Desktop --}}
            <nav class="hidden items-center gap-8 text-xs font-bold tracking-wider uppercase md:flex">
                <a href="{{ route('home') }}"
                    class="{{ request()->routeIs('home') ? 'border-b-2 border-[#52311a] pb-1 text-[#52311a]' : 'text-[#796b62] hover:text-[#52311a]' }}">
                    Home
                </a>
                <a href="{{ route('products.index') }}"
                    class="{{ request()->routeIs('products.*') ? 'border-b-2 border-[#52311a] pb-1 text-[#52311a]' : 'text-[#796b62] hover:text-[#52311a]' }}">
                    Produk
                </a>
                <a href="{{ route('about') }}"
                    class="{{ request()->routeIs('about') ? 'border-b-2 border-[#52311a] pb-1 text-[#52311a]' : 'text-[#796b62] hover:text-[#52311a]' }}">
                    Tentang Kami
                </a>
            </nav>

            {{-- Aksi Kanan: Autentikasi, Lonceng Notifikasi, Keranjang Drawer --}}
            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-full bg-[#52311a] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#492d1a]">
                            Panel Admin
                        </a>
                    @else
                        {{-- Popover Notifikasi Lonceng Pelanggan --}}
                        <div class="relative">
                            <button type="button" data-dropdown-toggle="customer-notif-popover"
                                class="relative flex h-9 w-9 items-center justify-center rounded-full border border-[#dfd2c0] bg-white text-[#52311a] transition hover:bg-[#f9eddd]"
                                aria-label="Notifikasi Pelanggan">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                @if($unreadNotifCount > 0)
                                    <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                                        {{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}
                                    </span>
                                @endif
                            </button>

                            <div id="customer-notif-popover" data-dropdown-menu class="hidden absolute right-0 mt-2 w-80 rounded-2xl border border-[#e3d5c3] bg-white p-3 shadow-xl">
                                <div class="mb-2 flex items-center justify-between border-b border-[#f0e5d6] pb-2">
                                    <span class="text-xs font-bold text-[#52311a]">Notifikasi Terbaru</span>
                                    @if($unreadNotifCount > 0)
                                        <form method="POST" action="{{ route('notifications.read-all') }}">
                                            @csrf
                                            <button type="submit" class="text-[11px] font-semibold text-[#936b49] hover:text-[#52311a]">Tandai semua dibaca</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="space-y-2">
                                    @forelse($recentNotifications as $notif)
                                        <form method="POST" action="{{ route('notifications.read', $notif) }}">
                                            @csrf
                                            <button type="submit" class="w-full rounded-xl p-2.5 text-left transition {{ $notif->read_at ? 'bg-[#fdfbf6] hover:bg-[#f9eddd]' : 'bg-[#f9eddd]/70 hover:bg-[#f2e4cd]' }}">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-xs font-bold text-[#52311a]">{{ $notif->title }}</span>
                                                    <span class="text-[10px] text-[#8d7d70]">{{ $notif->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="mt-0.5 line-clamp-2 text-[11px] text-[#5e524a]">{{ $notif->message }}</p>
                                            </button>
                                        </form>
                                    @empty
                                        <p class="py-4 text-center text-xs text-[#8d7d70]">Belum ada notifikasi pesanan.</p>
                                    @endforelse
                                </div>
                                <a href="{{ route('notifications.index') }}" class="mt-2 block rounded-lg bg-[#f9eddd] py-1.5 text-center text-xs font-semibold text-[#52311a] hover:bg-[#e9dcc7]">
                                    Lihat Semua Notifikasi
                                </a>
                            </div>
                        </div>

                        {{-- Dropdown Profil Cepat Pelanggan --}}
                        <div class="relative">
                            <button type="button" data-dropdown-toggle="customer-user-menu"
                                class="flex items-center gap-2 rounded-full border border-[#dfd2c0] bg-white px-3 py-1.5 text-xs font-semibold text-[#52311a] transition hover:bg-[#f9eddd]">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#52311a] text-[11px] font-bold text-white">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden max-w-[110px] truncate sm:inline">{{ explode(' ', auth()->user()->name)[0] }}</span>
                            </button>

                            <div id="customer-user-menu" data-dropdown-menu class="hidden absolute right-0 mt-2 w-56 rounded-2xl border border-[#e3d5c3] bg-white p-2 shadow-xl">
                                <div class="border-b border-[#f0e5d6] px-3 py-2">
                                    <p class="truncate text-xs font-bold text-[#28211c]">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-[11px] text-[#8d7d70]">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('dashboard') }}" class="mt-1 block rounded-lg px-3 py-2 text-xs font-medium text-[#28211c] hover:bg-[#f9eddd]">Dashboard Pesanan</a>
                                <a href="{{ route('notifications.index') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-[#28211c] hover:bg-[#f9eddd]">Notifikasi</a>
                                <a href="{{ route('profile.show') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-[#28211c] hover:bg-[#f9eddd]">Profil Saya</a>
                                <button type="button" data-open-modal="customer-logout-modal" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-red-700 hover:bg-red-50">
                                    Keluar
                                </button>
                            </div>
                        </div>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="rounded-full border border-[#52311a] bg-white px-4 py-1.5 text-xs font-semibold text-[#52311a] transition hover:bg-[#f9eddd]">
                        Masuk/Daftar
                    </a>
                @endauth

                {{-- Tombol Keranjang (Slide-over Drawer) --}}
                <button type="button" data-open-cart-drawer
                    class="relative flex h-9 items-center gap-1.5 rounded-full border border-[#dfd2c0] bg-white px-3 text-xs font-semibold text-[#52311a] transition hover:bg-[#f9eddd]"
                    aria-label="Buka Keranjang Belanja">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.4.4-.1 1.2.5 1.2H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    @if($cartCount > 0)
                        <span class="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#52311a] px-1.5 text-[10px] font-bold text-white">
                            {{ $cartCount }}
                        </span>
                    @endif
                </button>
            </div>
        </div>

        {{-- Navigasi Mobile Sederhana --}}
        <div class="flex items-center justify-center gap-6 border-t border-[#efe4d4] bg-[#fbf4e9] py-2 text-[11px] font-bold uppercase md:hidden">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-[#52311a]' : 'text-[#796b62]' }}">Home</a>
            <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'text-[#52311a]' : 'text-[#796b62]' }}">Produk</a>
            <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-[#52311a]' : 'text-[#796b62]' }}">Tentang Kami</a>
            <a href="{{ route('cart.index') }}" class="text-[#796b62]">Keranjang</a>
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
    <footer class="relative mt-16 text-[#f9eddd]">
        <div class="overflow-hidden leading-none text-[#52311a]">
            <svg viewBox="0 0 1440 80" preserveAspectRatio="none" class="block h-10 w-full sm:h-14">
                <path fill="currentColor" d="M0,32 C320,85 580,-10 920,38 C1180,74 1320,42 1440,18 L1440,80 L0,80 Z"></path>
            </svg>
        </div>

        <div class="bg-[#52311a] pb-10 pt-4">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 md:grid-cols-4 lg:px-8">
                <div>
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/kelana/logo-kelana.png') }}" alt="Brownies Kelana" class="h-10 w-10 rounded-full border border-[#b18950] object-cover">
                        <div>
                            <h3 class="text-base font-bold text-white">{{ $store->store_name }}</h3>
                            <p class="font-serif text-[10px] tracking-widest text-[#e9dcc7] italic uppercase">{{ $store->tagline }}</p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs leading-relaxed text-[#e9dcc7]/90">
                        Kelezatan yang mengajakmu berhenti sejenak. Untuk diri sendiri, untuk berbagi, untuk momen yang berarti.
                    </p>
                </div>

                <div>
                    <h4 class="text-xs font-bold tracking-wider text-white uppercase">Jelajahi Kelana</h4>
                    <ul class="mt-3 space-y-2 text-xs text-[#e9dcc7]/90">
                        <li><a href="{{ route('about') }}" class="hover:text-white">Tentang kami</a></li>
                        <li><a href="{{ route('products.index') }}" class="hover:text-white">Koleksi kue &amp; jajanan terkini</a></li>
                        <li><a href="{{ route('products.index', ['k3mart' => 1]) }}" class="hover:text-white">K3Mart Corner</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-xs font-bold tracking-wider text-white uppercase">Bantuan Pesanan</h4>
                    <ul class="mt-3 space-y-2 text-xs text-[#e9dcc7]/90">
                        <li><a href="{{ route('orders.guide') }}" class="hover:text-white">Cara pemesanan</a></li>
                        <li><a href="{{ route('shipping.info') }}" class="hover:text-white">Pengiriman &amp; pengambilan</a></li>
                        <li><a href="{{ route('allergen.info') }}" class="hover:text-white">Informasi alergen</a></li>
                        <li><a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="hover:text-white">Akun saya</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-xs font-bold tracking-wider text-white uppercase">Hubungi Kami</h4>
                    <ul class="mt-3 space-y-2 text-xs text-[#e9dcc7]/90">
                        <li>WhatsApp CS: {{ $store->whatsapp_number }}</li>
                        <li>Email: {{ $store->email }}</li>
                    </ul>
                    <a href="{{ $store->whatsappUrl('Halo Brownies Kelana, saya ingin bertanya mengenai produk & pemesanan.') }}" target="_blank" rel="noopener"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg border border-[#b18950] bg-[#67442f] px-4 py-2 text-[11px] font-bold tracking-wide text-white uppercase transition hover:bg-[#7d563c]">
                        Mulai Percakapan &rarr;
                    </a>
                </div>
            </div>

            <div class="mx-auto mt-10 flex max-w-7xl flex-col items-center justify-between gap-3 border-t border-white/15 px-4 pt-6 text-[11px] text-[#e9dcc7]/80 sm:flex-row sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} {{ $store->store_name }}. Seluruh hak cipta dilindungi.</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('privacy') }}" class="hover:text-white">Kebijakan privasi</a>
                    <span>&middot;</span>
                    <a href="{{ route('terms') }}" class="hover:text-white">Syarat &amp; ketentuan</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Slide-Over Cart Drawer (Global) --}}
    <div id="kelana-cart-backdrop" class="hidden fixed inset-0 z-50 bg-black/45 backdrop-blur-[1px]"></div>
    <aside id="kelana-cart-drawer" data-auto-open="{{ session('open_cart_drawer') ? 'true' : 'false' }}"
        class="fixed right-0 top-0 z-50 flex h-full w-full max-w-md translate-x-full flex-col bg-[#fdfbf6] shadow-2xl transition-transform duration-300">
        <div class="flex items-center justify-between border-b border-[#e9dcc7] bg-[#f9eddd] px-5 py-4">
            <div>
                <h2 class="text-base font-bold text-[#52311a]">Keranjang Belanja</h2>
                <p class="text-xs text-[#796b62]">{{ $cartCount }} item siap dipesan</p>
            </div>
            <button type="button" data-close-cart-drawer class="rounded-lg p-1.5 text-[#52311a] hover:bg-[#e9dcc7]" aria-label="Tutup Keranjang">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5">
            @if($cart && $cart->items->isNotEmpty())
                <div class="space-y-4">
                    @foreach($cart->items as $item)
                        <div class="flex gap-3 rounded-xl border border-[#e9dcc7] bg-white p-3">
                            <img src="{{ $item->product->primaryImageUrl() }}" alt="{{ $item->product->name }}" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm font-bold text-[#28211c]">{{ $item->product->name }}</h3>
                                <p class="text-xs text-[#796b62]">
                                    {{ $item->variant?->name ?? 'Reguler' }} &middot; Rp {{ number_format($item->unitPrice(), 0, ',', '.') }}
                                </p>
                                @if($item->custom_note)
                                    <p class="mt-0.5 text-[11px] font-medium text-[#936b49]">Tulisan: "{{ $item->custom_note }}"</p>
                                @endif

                                <div class="mt-2 flex items-center justify-between">
                                    <div class="inline-flex items-center rounded-lg border border-[#dfd2c0] bg-[#fdfbf6]">
                                        <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ max(0, $item->quantity - 1) }}">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-bold text-[#52311a] hover:bg-[#f2e4cd]">&minus;</button>
                                        </form>
                                        <span class="px-2.5 text-xs font-bold">{{ $item->quantity }}</span>
                                        <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-bold text-[#52311a] hover:bg-[#f2e4cd]">+</button>
                                        </form>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-[#52311a]">Rp {{ number_format($item->lineSubtotal(), 0, ',', '.') }}</span>
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
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#f2e4cd] text-[#52311a]">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-sm font-bold text-[#52311a]">Keranjang Masih Kosong</h3>
                    <p class="mt-1 max-w-xs text-xs text-[#796b62]">Temukan rasa favoritmu di katalog produk Brownies Kelana!</p>
                    <a href="{{ route('products.index') }}" class="btn-kelana-primary mt-5 text-xs">Jelajahi Produk</a>
                </div>
            @endif
        </div>

        @if($cart && $cart->items->isNotEmpty())
            <div class="border-t border-[#e9dcc7] bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs font-medium text-[#796b62]">Subtotal sementara</span>
                    <span class="text-base font-bold text-[#52311a]">Rp {{ number_format($cartSubtotal, 0, ',', '.') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <a href="{{ route('cart.index') }}" class="btn-kelana-outline w-full text-xs">Lihat Keranjang</a>
                    <a href="{{ route('checkout.index') }}" class="btn-kelana-primary w-full text-xs">Lanjut Checkout</a>
                </div>
            </div>
        @endif
    </aside>

    {{-- Modal Konfirmasi Logout Pelanggan (dengan Maskot Brownies) --}}
    @auth
        @if(! auth()->user()->isAdmin())
            <div id="customer-logout-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md rounded-2xl border border-[#e3d5c3] bg-[#fdfbf6] p-6 text-center shadow-2xl">
                    <img src="{{ asset('images/kelana/mascot-brownie.png') }}" alt="Maskot Brownies Kelana" class="mx-auto h-24 w-24 object-contain">
                    <h3 class="mt-3 text-lg font-bold text-[#52311a]">Ingin Keluar dari Akun?</h3>
                    <p class="mt-1 text-xs leading-relaxed text-[#796b62]">
                        Kamu harus masuk kembali nanti untuk memantau status pesanan atau melakukan pemesanan baru.
                    </p>
                    <div class="mt-5 flex items-center justify-center gap-3">
                        <button type="button" data-close-modal="customer-logout-modal" class="btn-kelana-outline text-xs">
                            Tetap di Sini
                        </button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-kelana-primary bg-red-700 text-xs hover:bg-red-800">
                                Ya, Keluar
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

