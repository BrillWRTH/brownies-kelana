@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan — Brownies Kelana')

@section('content')
    <section class="mx-auto flex max-w-3xl flex-col items-center justify-center px-4 py-20 text-center">
        <span class="rounded-full bg-[#f2e4cd] px-4 py-1 text-xs font-bold tracking-wide text-[#52311a]">404 NOT FOUND</span>
        <h1 class="mt-4 font-serif text-3xl font-bold text-[#52311a] sm:text-4xl">Halaman Tidak Ditemukan</h1>
        <p class="mt-3 max-w-md text-sm text-[#796b62]">
            Halaman yang kamu cari tidak tersedia atau telah dipindahkan. Yuk jelajahi koleksi brownies segar kami!
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-kelana-primary">Kembali ke Beranda</a>
            <a href="{{ route('products.index') }}" class="btn-kelana-outline">Jelajahi Katalog</a>
        </div>
    </section>
@endsection

