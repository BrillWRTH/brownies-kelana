@extends('layouts.app')

@section('title', 'Profil Saya — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('partials.customer-sidebar')

            <div class="min-w-0 flex-1 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="font-serif text-2xl font-bold text-[#52311a]">Profil Saya</h1>
                        <p class="text-xs text-[#796b62]">Informasi akun pelanggan yang digunakan untuk checkout dan pengantaran.</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn-kelana-primary rounded-full text-xs">Edit Profil</a>
                </div>

                <div class="rounded-2xl border border-[#e3d5c3] bg-white p-6 shadow-xs">
                    <dl class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-[11px] font-semibold text-[#796b62]">Kode Pelanggan</dt>
                            <dd class="mt-1 text-sm font-bold text-[#52311a]">{{ $user->customer_code ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-[#796b62]">Status Verifikasi Email</dt>
                            <dd class="mt-1">
                                @if($user->hasVerifiedEmail())
                                    <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-bold text-emerald-800">Terverifikasi</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-3 py-0.5 text-xs font-bold text-amber-800">Belum Terverifikasi</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-[#796b62]">Nama Lengkap</dt>
                            <dd class="mt-1 text-sm font-bold text-[#28211c]">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-[#796b62]">Email</dt>
                            <dd class="mt-1 text-sm font-semibold text-[#28211c]">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-[#796b62]">Nomor Telepon / WhatsApp</dt>
                            <dd class="mt-1 text-sm font-semibold text-[#28211c]">{{ $user->phone ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-[#796b62]">Nomor Telepon Kedua (Opsional)</dt>
                            <dd class="mt-1 text-sm font-semibold text-[#28211c]">{{ $user->secondary_phone ?: 'Belum diisi' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-[11px] font-semibold text-[#796b62]">Alamat Pengiriman Tersimpan</dt>
                            <dd class="mt-1 text-sm text-[#28211c]">{{ $user->saved_address ?: 'Belum diisi' }}</dd>
                        </div>
                    </dl>
                </div>

                @include('partials.customer-help-banner')
            </div>
        </div>
    </section>
@endsection

