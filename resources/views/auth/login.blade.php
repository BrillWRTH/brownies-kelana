@extends('layouts.app')

@section('title', 'Masuk — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="overflow-hidden rounded-3xl border border-[#e3d5c3] bg-[#ede2cf] p-5 shadow-md sm:p-8">
            <div class="grid items-center gap-8 md:grid-cols-12">
                <div class="md:col-span-5">
                    <img src="{{ asset('images/kelana/auth-brownies.png') }}" alt="Brownies Kelana"
                        class="h-64 w-full rounded-2xl object-cover shadow-sm md:h-96">
                </div>

                <div class="md:col-span-7">
                    <h1 class="font-serif text-2xl font-bold text-[#52311a] sm:text-3xl">Masuk</h1>
                    <p class="mt-1 text-xs text-[#796b62]">Masuk ke akunmu untuk memesan dan memantau progres pesanan.</p>

                    <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="email" class="kelana-label">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                placeholder="contoh@email.com" class="kelana-input rounded-full px-4">
                            @error('email')
                                <p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="kelana-label">Kata Sandi</label>
                            <input id="password" name="password" type="password" required
                                placeholder="Masukkan kata sandi" class="kelana-input rounded-full px-4">
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <label class="inline-flex items-center gap-2 text-[#5e524a]">
                                <input type="checkbox" name="remember" value="1" class="rounded border-[#b18950] text-[#52311a] focus:ring-[#52311a]">
                                <span>Ingat Saya</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="font-semibold text-[#52311a] hover:underline">Lupa Kata Sandi?</a>
                        </div>

                        <button type="submit" class="btn-kelana-primary w-full rounded-full py-2.5 text-sm">
                            Masuk
                        </button>
                    </form>

                    <div class="mt-5 rounded-xl border border-[#dccab3] bg-[#fdfbf6]/80 p-3 text-[11px] text-[#5e524a]">
                        <p class="font-bold text-[#52311a]">Akun Demo Pelanggan:</p>
                        <p>Email: <code class="font-semibold">sinta@example.com</code> &middot; Sandi: <code class="font-semibold">password</code></p>
                    </div>

                    <p class="mt-5 text-center text-xs text-[#5e524a]">
                        Belum punya akun?
                        <a href="{{ route('register') }}" class="font-bold text-[#52311a] hover:underline">Daftar di sini</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection

