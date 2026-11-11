@extends('layouts.app')

@section('title', 'Email Terverifikasi — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="overflow-hidden rounded-[2rem] border border-[#dcccb7] bg-[#e8decb] p-6 shadow-sm sm:p-10">
            <div class="grid items-center gap-8 md:grid-cols-12">
                <div class="md:col-span-5">
                    <img src="{{ asset('images/kelana/auth-brownies.png') }}" alt="Brownies Kelana"
                        class="h-64 w-full rounded-[1.5rem] object-cover shadow-sm md:h-80">
                </div>
                <div class="md:col-span-7">
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold text-emerald-800">Terverifikasi</span>
                    <h1 class="mt-3 font-serif text-2xl font-bold text-[#593822]">Email Terverifikasi</h1>
                    <p class="mt-2 text-xs leading-relaxed text-[#5e4d3e]">
                        Halo, <strong>{{ $user->name }}</strong>. Email kamu (<strong>{{ $user->email }}</strong>) berhasil diverifikasi. Akun kamu sekarang sudah terkonfirmasi aktif.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('dashboard') }}" class="btn-kelana-primary rounded-lg text-xs">Ke Dashboard</a>
                        <a href="{{ route('products.index') }}" class="btn-kelana-outline rounded-lg text-xs">Jelajahi Produk</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

