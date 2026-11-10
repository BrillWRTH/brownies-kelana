@extends('layouts.app')

@section('title', 'Daftar Akun — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="relative overflow-hidden rounded-3xl border border-[#e3d5c3] bg-[#ede2cf] p-6 shadow-md sm:p-10">
            {{-- Aksen Tetesan Cokelat di Pojok Kanan Atas --}}
            <img src="{{ asset('images/kelana/chocolate-drip.png') }}" alt=""
                class="pointer-events-none absolute -top-2 right-4 hidden w-44 select-none opacity-95 sm:block">

            <div class="relative z-10">
                <h1 class="font-serif text-3xl font-bold tracking-wide text-[#52311a]">DAFTAR</h1>
                <p class="mt-1 text-xs text-[#796b62]">Buat akun pelanggan Brownies Kelana untuk checkout mudah dan pantau pesanan.</p>

                <form method="POST" action="{{ route('register.store') }}" class="mt-7 grid gap-5 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="name" class="kelana-label">Nama Lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required
                            placeholder="Nama lengkap kamu" class="kelana-input rounded-full px-4">
                        @error('name')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="kelana-label">Kata Sandi</label>
                        <input id="password" name="password" type="password" required
                            placeholder="Minimal 8 karakter" class="kelana-input rounded-full px-4">
                        @error('password')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="kelana-label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            placeholder="email@contoh.com" class="kelana-input rounded-full px-4">
                        @error('email')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="kelana-label">Konfirmasi Kata Sandi</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            placeholder="Ulangi kata sandi" class="kelana-input rounded-full px-4">
                    </div>

                    <div>
                        <label for="phone" class="kelana-label">No. HP / WhatsApp</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required
                            placeholder="0812xxxxxxxx" class="kelana-input rounded-full px-4">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col justify-end gap-3">
                        <label class="flex items-start gap-2 text-xs text-[#5e524a]">
                            <input type="checkbox" name="agree_terms" value="1" {{ old('agree_terms') ? 'checked' : '' }} required
                                class="mt-0.5 rounded border-[#b18950] text-[#52311a] focus:ring-[#52311a]">
                            <span>
                                Saya menyetujui <a href="{{ route('terms') }}" class="font-bold text-[#52311a] underline">Syarat &amp; Ketentuan</a>
                                serta <a href="{{ route('privacy') }}" class="font-bold text-[#52311a] underline">Kebijakan Privasi</a>.
                            </span>
                        </label>
                        @error('agree_terms')
                            <p class="text-xs text-red-700">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="btn-kelana-primary w-full rounded-full py-2.5 text-sm">
                            Daftar Sekarang
                        </button>
                    </div>
                </form>

                <p class="mt-6 text-center text-xs text-[#5e524a]">
                    Sudah memiliki akun?
                    <a href="{{ route('login') }}" class="font-bold text-[#52311a] hover:underline">Masuk di sini</a>
                </p>
            </div>
        </div>
    </section>
@endsection

