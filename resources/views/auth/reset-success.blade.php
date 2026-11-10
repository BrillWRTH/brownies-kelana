@extends('layouts.app')

@section('title', 'Reset Kata Sandi Berhasil — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="overflow-hidden rounded-3xl border border-[#e3d5c3] bg-[#ede2cf] p-5 shadow-md sm:p-8">
            <div class="grid items-center gap-8 md:grid-cols-12">
                <div class="md:col-span-5">
                    <img src="{{ asset('images/kelana/auth-brownies.png') }}" alt="Brownies Kelana"
                        class="h-64 w-full rounded-2xl object-cover shadow-sm md:h-80">
                </div>
                <div class="md:col-span-7">
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold text-emerald-800">Berhasil</span>
                    <h1 class="mt-3 font-serif text-2xl font-bold text-[#52311a]">Reset Berhasil</h1>
                    <p class="mt-2 text-xs leading-relaxed text-[#5e524a]">
                        Kata sandi untuk akun <strong>{{ $email }}</strong> telah diperbarui. Silakan masuk kembali menggunakan kata sandi barumu.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('login') }}" class="btn-kelana-primary rounded-full text-xs">Masuk ke Akun</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

