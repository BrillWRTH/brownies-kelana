@extends('layouts.app')

@section('title', 'Verifikasi Email — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="overflow-hidden rounded-3xl border border-[#e3d5c3] bg-[#ede2cf] p-5 shadow-md sm:p-8">
            <div class="grid items-center gap-8 md:grid-cols-12">
                <div class="md:col-span-5">
                    <img src="{{ asset('images/kelana/auth-brownies.png') }}" alt="Brownies Kelana"
                        class="h-64 w-full rounded-2xl object-cover shadow-sm md:h-80">
                </div>
                <div class="md:col-span-7">
                    <h1 class="font-serif text-2xl font-bold text-[#52311a]">Verifikasi Email</h1>
                    <p class="mt-2 text-xs leading-relaxed text-[#5e524a]">
                        Halo, <strong>{{ $user->name }}</strong>. Tautan verifikasi email telah dikirimkan ke:
                    </p>
                    <p class="mt-1 inline-block rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-[#52311a]">
                        {{ $user->email }}
                    </p>
                    <p class="mt-3 text-xs leading-relaxed text-[#796b62]">
                        Periksa kotak masuk atau folder spam email kamu. Jika belum menerima email, kamu tetap dapat mengakses Dashboard atau mengirim ulang tautan verifikasi.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('dashboard') }}" class="btn-kelana-primary rounded-full text-xs">Ke Dashboard</a>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn-kelana-outline rounded-full text-xs">Kirim Ulang Email</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

