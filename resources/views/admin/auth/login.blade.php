<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Admin — Brownies Kelana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-[#28211c] px-4 py-10 text-[#28211c] antialiased">
    <div class="w-full max-w-md">
        {{-- Identitas Merek Portal Admin --}}
        <div class="mb-6 flex flex-col items-center text-center">
            <img src="{{ asset('images/kelana/logo-kelana.png') }}" alt="Logo Brownies Kelana"
                class="h-14 w-14 rounded-full border-2 border-[#b18950] bg-white object-cover shadow-md">
            <span class="mt-3 rounded-full border border-[#b18950]/40 bg-[#52311a] px-3 py-1 text-[10px] font-bold tracking-widest text-[#f2e4cd] uppercase">
                Portal Pengelola &amp; Owner
            </span>
            <h1 class="mt-3 font-serif text-2xl font-bold text-[#fdfbf6]">Login Admin UMKM Kelana</h1>
            <p class="mt-1 text-xs text-[#d6c5b3]">
                Halaman ini terpisah dari login pelanggan storefront.
            </p>
        </div>

        {{-- Kartu Form Login Admin --}}
        <div class="rounded-2xl border border-[#e8dfd3] bg-white p-6 shadow-2xl sm:p-8">
            @if(session('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-xs font-medium text-emerald-900">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-900">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="kelana-label">Email atau Username Admin</label>
                    <input id="email" name="email" type="text" value="{{ old('email') }}" required autofocus
                        placeholder="admin@kelana.test atau admin.kelana" class="kelana-input">
                    @error('email')
                        <p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="kelana-label">Password</label>
                    <input id="password" name="password" type="password" required
                        placeholder="Masukkan password admin" class="kelana-input">
                    @error('password')
                        <p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <label class="inline-flex items-center gap-2 text-xs text-[#5e524a]">
                    <input type="checkbox" name="remember" value="1" class="rounded border-[#b18950] text-[#52311a] focus:ring-[#52311a]">
                    <span>Ingat sesi admin di perangkat ini</span>
                </label>

                <button type="submit" class="btn-kelana-primary w-full py-2.5 text-sm">
                    Masuk ke Panel Admin
                </button>
            </form>

            {{-- Informasi Kredensial Demo Pengujian --}}
            <div class="mt-5 rounded-xl border border-[#e8dfd3] bg-[#fdfbf6] p-3.5 text-[11px] text-[#5e524a]">
                <p class="font-bold text-[#52311a]">Akun Demo Admin (Owner):</p>
                <p class="mt-0.5">Email: <code class="font-semibold text-[#28211c]">admin@kelana.test</code> atau <code class="font-semibold text-[#28211c]">admin.kelana</code></p>
                <p>Password: <code class="font-semibold text-[#28211c]">password</code></p>
            </div>
        </div>

        <div class="mt-5 text-center">
            <a href="{{ route('home') }}" class="text-xs font-medium text-[#d6c5b3] transition hover:text-white">
                &larr; Kembali ke Storefront Pelanggan
            </a>
        </div>
    </div>
</body>
</html>

