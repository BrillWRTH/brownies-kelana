<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Admin — Brownies Kelana</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#fcf3e3] text-[#2f2118] antialiased">
    <div class="flex min-h-screen flex-col lg:flex-row">
        {{-- Panel Kiri Cokelat Hangat ala UI/UX --}}
        <div class="flex flex-col justify-between bg-[#674732] px-8 py-10 text-white lg:w-[40%] lg:px-14 lg:py-14">
            <div>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/kelana/logo-kelana.png') }}" alt="Logo Brownies Kelana"
                        class="h-10 w-10 rounded-full border border-[#d9c8b4] bg-white object-cover">
                    <div>
                        <span class="block font-serif text-lg font-bold text-white">Brownies Kelana</span>
                        <span class="block text-[11px] text-[#e8decb]/85">Login Admin UMKM Kelana</span>
                    </div>
                </div>
            </div>

            <div class="my-10 lg:my-0">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl border border-[#e8decb]/30 text-[#f7ebd5]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h1 class="font-serif text-3xl leading-snug font-bold text-white sm:text-4xl">
                    Akses khusus admin UMKM Kelana
                </h1>
                <p class="mt-3 max-w-xs text-xs leading-relaxed text-[#f7ebd5]/85">
                    Kelola pesanan, jadwal produksi H-1, konfirmasi Midtrans, dan pengaturan UMKM Kelana melalui panel admin.
                </p>
            </div>

            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-[#f7ebd5]/85 transition hover:text-white">
                    &larr; Kembali ke Storefront Pelanggan
                </a>
            </div>
        </div>

        {{-- Panel Kanan Form Login Admin di atas Krem --}}
        <div class="flex flex-1 items-center justify-center px-4 py-12 sm:px-8">
            <div class="w-full max-w-md rounded-2xl border border-[#efe2ce] bg-white p-8 shadow-sm sm:p-10">
                <h2 class="font-serif text-3xl leading-tight font-bold text-[#593822]">
                    Login Admin UMKM Kelana
                </h2>
                <p class="mt-2 text-xs text-[#6f5a4a]">
                    Masuk dengan akun admin UMKM Kelana. Halaman ini terpisah dari login pelanggan.
                </p>

                @if(session('success'))
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-xs font-medium text-emerald-900">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-900">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="kelana-label">Email / Username *</label>
                        <input id="email" name="email" type="text" value="{{ old('email') }}" required autofocus
                            placeholder="Masukkan email atau username admin" class="kelana-input">
                        @error('email')
                            <p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="kelana-label">Password *</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required
                                placeholder="Masukkan password" class="kelana-input pr-10">
                            <button type="button" data-toggle-password="password" aria-label="Tampilkan password"
                                class="absolute inset-y-0 right-3 flex items-center text-[#8f7968] transition hover:text-[#593822]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="inline-flex items-center gap-2 text-xs text-[#5e4d3e]">
                        <input type="checkbox" name="remember" value="1" class="rounded border-[#8f6743] text-[#593822] focus:ring-[#a4804f]">
                        <span>Ingat sesi admin di perangkat ini</span>
                    </label>

                    <button type="submit" class="btn-kelana-primary w-full rounded-lg py-2.5 text-sm">
                        Masuk sebagai Admin UMKM Kelana
                    </button>
                </form>

                <hr class="my-6 border-[#efe2ce]">

                <p class="text-[11px] leading-relaxed text-[#796b62]">
                    Hanya akun admin UMKM Kelana yang dapat mengakses panel ini. Tidak tersedia pendaftaran pelanggan.
                </p>

                <div class="mt-4 rounded-xl border border-[#efe2ce] bg-[#fcf3e3] p-3 text-[11px] text-[#5e4d3e]">
                    <p class="font-bold text-[#593822]">Akun Demo Admin:</p>
                    <p class="mt-0.5">Email / Username: <code class="font-semibold text-[#2f2118]">admin@kelana.test</code> / <code class="font-semibold text-[#2f2118]">admin.kelana</code></p>
                    <p>Password: <code class="font-semibold text-[#2f2118]">password</code></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
