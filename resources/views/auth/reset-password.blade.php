@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="overflow-hidden rounded-3xl border border-[#e3d5c3] bg-[#ede2cf] p-5 shadow-md sm:p-8">
            <div class="grid items-center gap-8 md:grid-cols-12">
                <div class="md:col-span-5">
                    <img src="{{ asset('images/kelana/auth-brownies.png') }}" alt="Brownies Kelana"
                        class="h-64 w-full rounded-2xl object-cover shadow-sm md:h-80">
                </div>
                <div class="md:col-span-7">
                    <h1 class="font-serif text-2xl font-bold text-[#52311a]">Atur Ulang Kata Sandi</h1>
                    <p class="mt-2 text-xs text-[#5e524a]">Buat kata sandi baru minimal 8 karakter untuk akunmu.</p>

                    <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div>
                            <label for="email" class="kelana-label">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required
                                class="kelana-input rounded-full px-4">
                            @error('email')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="kelana-label">Kata Sandi Baru</label>
                            <input id="password" name="password" type="password" required
                                placeholder="Minimal 8 karakter" class="kelana-input rounded-full px-4">
                            @error('password')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="kelana-label">Konfirmasi Kata Sandi Baru</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required
                                placeholder="Ulangi kata sandi baru" class="kelana-input rounded-full px-4">
                        </div>

                        <button type="submit" class="btn-kelana-primary w-full rounded-full py-2.5 text-sm">
                            Simpan Kata Sandi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

