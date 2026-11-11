@extends('layouts.app')

@section('title', 'Daftar Akun — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="relative overflow-hidden rounded-[2rem] border border-[#dcccb7] bg-[#e8decb] p-7 shadow-sm sm:px-14 sm:py-12">
            {{-- Aksen Tetesan Cokelat di Pojok Kanan Atas --}}
            <img src="{{ asset('images/kelana/chocolate-drip.png') }}" alt=""
                class="pointer-events-none absolute -top-2 right-6 hidden w-56 select-none opacity-95 sm:block">

            <div class="relative z-10">
                <h1 class="font-serif text-3xl font-bold text-[#593822]">DAFTAR</h1>
                <p class="mt-1 text-xs text-[#6f5a4a]">Buat akun pelanggan Brownies Kelana untuk checkout mudah dan pantau pesanan.</p>

                <form method="POST" action="{{ route('register.store') }}" class="mt-8 grid gap-x-10 gap-y-5 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="name" class="kelana-label text-sm">Nama Lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required
                            placeholder="Masukkan nama lengkap" class="kelana-input-pill">
                        @error('name')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="kelana-label text-sm">Kata Sandi</label>
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
                        @error('password')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="kelana-label text-sm">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            placeholder="Masukkan email" class="kelana-input-pill">
                        @error('email')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="kelana-label text-sm">Konfirmasi Kata Sandi</label>
                        <div class="relative">
                            <input id="password_confirmation" name="password_confirmation" type="password" required
                                placeholder="Masukkan kata sandi" class="kelana-input-pill pr-11">
                            <button type="button" data-toggle-password="password_confirmation" aria-label="Tampilkan konfirmasi kata sandi"
                                class="absolute inset-y-0 right-3.5 flex items-center text-[#8f7968] transition hover:text-[#593822]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="kelana-label text-sm">No HP/Telepon</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required
                            placeholder="Masukkan no telepon" class="kelana-input-pill">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col justify-end gap-3">
                        <button type="submit" class="btn-kelana-primary w-full rounded-lg py-2.5 text-sm">
                            Daftar
                        </button>

                        <label class="flex items-start gap-2 text-xs text-[#593822]">
                            <input type="checkbox" name="agree_terms" value="1" {{ old('agree_terms') ? 'checked' : '' }} required
                                class="mt-0.5 rounded border-[#8f6743] text-[#593822] focus:ring-[#a4804f]">
                            <span>
                                Saya menyetujui <a href="{{ route('terms') }}" class="font-bold text-[#593822] underline">syarat dan ketentuan</a>
                                serta <a href="{{ route('privacy') }}" class="font-bold text-[#593822] underline">kebijakan privasi</a> yang berlaku
                            </span>
                        </label>
                        @error('agree_terms')
                            <p class="text-xs text-red-700">{{ $message }}</p>
                        @enderror

                        <p class="pt-1 text-center text-xs text-[#5e4d3e]">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" class="font-bold text-[#593822] hover:underline">Masuk</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

