@extends('layouts.app')

@section('title', 'Masuk — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="overflow-hidden rounded-[2rem] border border-[#dcccb7] bg-[#e8decb] p-6 shadow-sm sm:p-10">
            <div class="grid items-center gap-8 md:grid-cols-12">
                <div class="md:col-span-6">
                    <img src="{{ asset('images/kelana/auth-brownies.png') }}" alt="Brownies Kelana"
                        class="h-72 w-full rounded-[1.5rem] object-cover shadow-sm md:h-[420px]">
                </div>

                <div class="md:col-span-6 md:px-2">
                    <h1 class="text-3xl font-extrabold tracking-tight text-[#593822]">Masuk</h1>
                    <p class="mt-1 text-xs text-[#6f5a4a]">Masuk ke akunmu untuk memesan dan memantau progres pesanan.</p>

                    <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="email" class="kelana-label font-serif text-sm">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                placeholder="Masukkan email" class="kelana-input-pill">
                            @error('email')
                                <p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="kelana-label font-serif text-sm">Kata Sandi</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required
                                    placeholder="Masukkan kata sandi" class="kelana-input-pill pr-11">
                                <button type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi"
                                    class="absolute inset-y-0 right-3.5 flex items-center text-[#8f7968] transition hover:text-[#593822]">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-0.5 text-xs">
                            <label class="inline-flex cursor-pointer items-center gap-2 text-[#593822]">
                                <input type="checkbox" name="remember" value="1" class="rounded border-[#8f6743] text-[#593822] focus:ring-[#a4804f]">
                                <span>Ingat Saya</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="font-medium text-[#593822] hover:underline">Lupa Kata Sandi</a>
                        </div>

                        <button type="submit" class="btn-kelana-primary w-full rounded-lg py-2.5 text-sm">
                            Masuk
                        </button>
                    </form>

                    <p class="mt-6 text-center text-xs text-[#5e4d3e]">
                        Belum punya akun?
                        <a href="{{ route('register') }}" class="font-bold text-[#593822] hover:underline">Daftar</a>
                    </p>

                    <div class="mt-4 rounded-2xl border border-[#d6c2a8] bg-[#f7ebd5]/80 px-3.5 py-2.5 text-[11px] text-[#5e4d3e]">
                        <span class="font-bold text-[#593822]">Akun Demo:</span>
                        <code class="font-semibold">sinta@example.com</code> &middot; Sandi: <code class="font-semibold">password</code>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

