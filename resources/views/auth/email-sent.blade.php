@extends('layouts.app')

@section('title', 'Email Terkirim — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="overflow-hidden rounded-3xl border border-[#e3d5c3] bg-[#ede2cf] p-5 shadow-md sm:p-8">
            <div class="grid items-center gap-8 md:grid-cols-12">
                <div class="md:col-span-5">
                    <img src="{{ asset('images/kelana/auth-brownies.png') }}" alt="Brownies Kelana"
                        class="h-64 w-full rounded-2xl object-cover shadow-sm md:h-80">
                </div>
                <div class="md:col-span-7">
                    <span class="inline-flex rounded-full bg-[#52311a] px-3 py-1 text-[11px] font-bold text-white">Tautan Terkirim</span>
                    <h1 class="mt-3 font-serif text-2xl font-bold text-[#52311a]">Email Terkirim</h1>
                    <p class="mt-2 text-xs leading-relaxed text-[#5e524a]">
                        Kami telah mengirimkan tautan atur ulang kata sandi ke <strong>{{ $email }}</strong>. Periksa kotak masuk atau folder spam email kamu.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="btn-kelana-primary rounded-full text-xs">Kembali ke Masuk</a>
                        <a href="{{ route('password.request') }}" class="btn-kelana-outline rounded-full text-xs">Kirim Ulang Email</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

