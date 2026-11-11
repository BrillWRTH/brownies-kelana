@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="overflow-hidden rounded-[2rem] border border-[#dcccb7] bg-[#e8decb] p-6 shadow-sm sm:p-10">
            <div class="grid items-center gap-8 md:grid-cols-12">
                <div class="md:col-span-5">
                    <img src="{{ asset('images/kelana/auth-brownies.png') }}" alt="Brownies Kelana"
                        class="h-64 w-full rounded-[1.5rem] object-cover shadow-sm md:h-80">
                </div>
                <div class="md:col-span-7">
                    <h1 class="font-serif text-2xl font-bold text-[#593822]">Atur Ulang Kata Sandi</h1>
                    <p class="mt-2 text-xs text-[#5e4d3e]">Buat kata sandi baru minimal 8 karakter untuk akunmu.</p>

                    <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div>
                            <label for="email" class="kelana-label text-sm">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required
                                class="kelana-input-pill">
                            @error('email')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="kelana-label text-sm">Kata Sandi Baru</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required
                                    placeholder="Minimal 8 karakter" class="kelana-input-pill pr-11">
                                <button type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi baru"
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
                            <label for="password_confirmation" class="kelana-label text-sm">Konfirmasi Kata Sandi Baru</label>
                            <div class="relative">
                                <input id="password_confirmation" name="password_confirmation" type="password" required
                                    placeholder="Ulangi kata sandi baru" class="kelana-input-pill pr-11">
                                <button type="button" data-toggle-password="password_confirmation" aria-label="Tampilkan konfirmasi kata sandi"
                                    class="absolute inset-y-0 right-3.5 flex items-center text-[#8f7968] transition hover:text-[#593822]">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-kelana-primary w-full rounded-lg py-2.5 text-sm">
                            Simpan Kata Sandi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

