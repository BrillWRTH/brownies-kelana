@extends('layouts.app')

@section('title', 'Terjadi Gangguan Server — Brownies Kelana')

@section('content')
    <section class="mx-auto flex max-w-3xl flex-col items-center justify-center px-4 py-20 text-center">
        <span class="rounded-full bg-red-100 px-4 py-1 text-xs font-bold tracking-wide text-red-700">ERROR 500</span>
        <h1 class="mt-4 font-serif text-3xl font-bold text-[#52311a] sm:text-4xl">Terjadi Gangguan Server</h1>
        <p class="mt-3 max-w-md text-sm text-[#796b62]">
            Kami belum dapat memuat halaman ini saat ini. Silakan muat ulang atau coba beberapa saat lagi.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url()->current() }}" class="btn-kelana-primary">Coba Lagi</a>
            <a href="{{ route('home') }}" class="btn-kelana-outline">Kembali ke Beranda</a>
        </div>
    </section>
@endsection

