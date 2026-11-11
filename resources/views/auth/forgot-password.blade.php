@extends('layouts.app')

@section('title', 'Lupa Kata Sandi — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="overflow-hidden rounded-[2rem] border border-[#dcccb7] bg-[#e8decb] p-6 shadow-sm sm:p-10">
            <div class="grid items-center gap-8 md:grid-cols-12">
                <div class="md:col-span-5">
                    <img src="{{ asset('images/kelana/auth-brownies.png') }}" alt="Brownies Kelana"
                        class="h-64 w-full rounded-[1.5rem] object-cover shadow-sm md:h-80">
                </div>
                <div class="md:col-span-7">
                    <h1 class="text-2xl font-extrabold text-[#593822]">Lupa Kata Sandi</h1>
                    <p class="mt-2 text-xs leading-relaxed text-[#5e4d3e]">
                        Masukkan alamat email yang terdaftar di akun Brownies Kelana. Kami akan mengirimkan tautan untuk mengatur ulang kata sandimu.
                    </p>

                    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="email" class="kelana-label font-serif text-sm">Email Terdaftar</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                placeholder="contoh@email.com" class="kelana-input-pill">
                            @error('email')
                                <p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn-kelana-primary w-full rounded-lg py-2.5 text-sm">
                            Kirim Tautan Reset
                        </button>
                    </form>

                    <p class="mt-5 text-center text-xs text-[#5e4d3e]">
                        Ingat kata sandimu?
                        <a href="{{ route('login') }}" class="font-bold text-[#593822] hover:underline">Kembali ke Masuk</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection

