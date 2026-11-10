@extends('layouts.app')

@section('title', 'Pesanan Tidak Ditemukan — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('partials.customer-sidebar')

            <div class="min-w-0 flex-1 space-y-6">
                <div class="rounded-3xl border border-[#e3d5c3] bg-white p-10 text-center shadow-xs">
                    <span class="rounded-full bg-[#f2e4cd] px-3.5 py-1 text-[11px] font-bold text-[#52311a]">Tidak Ditemukan</span>
                    <h1 class="mt-3 font-serif text-2xl font-bold text-[#52311a]">Pesanan Tidak Ditemukan</h1>
                    <p class="mx-auto mt-2 max-w-md text-xs leading-relaxed text-[#796b62]">
                        Pesanan <strong>{{ $orderNumber }}</strong> tidak ditemukan atau tidak tersedia untuk akun <strong>{{ $user->name }}</strong> ({{ $user->email }}). Periksa nomor pesanan atau kembali ke dashboard.
                    </p>
                    <a href="{{ route('dashboard') }}" class="btn-kelana-primary mt-6 rounded-full text-xs">Kembali ke Dashboard</a>
                </div>

                @include('partials.customer-help-banner')
            </div>
        </div>
    </section>
@endsection

