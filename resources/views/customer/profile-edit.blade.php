@extends('layouts.app')

@section('title', 'Edit Profil — Brownies Kelana')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('partials.customer-sidebar')

            <div class="min-w-0 flex-1 space-y-6">
                <div>
                    <a href="{{ route('profile.show') }}" class="text-xs font-semibold text-[#52311a] hover:underline">&larr; Kembali ke Profil</a>
                    <h1 class="mt-1 font-serif text-2xl font-bold text-[#52311a]">Edit Profil &amp; Kata Sandi</h1>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-6 rounded-2xl border border-[#e3d5c3] bg-white p-6 shadow-xs">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="kelana-label">Nama Lengkap *</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="kelana-input">
                        </div>
                        <div>
                            <label for="email" class="kelana-label">Email *</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="kelana-input">
                        </div>
                        <div>
                            <label for="phone" class="kelana-label">No. HP / WhatsApp *</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" required class="kelana-input">
                        </div>
                        <div>
                            <label for="secondary_phone" class="kelana-label">No. HP Kedua (Opsional)</label>
                            <input id="secondary_phone" name="secondary_phone" type="text" value="{{ old('secondary_phone', $user->secondary_phone) }}" class="kelana-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="saved_address" class="kelana-label">Alamat Pengiriman Utama</label>
                            <textarea id="saved_address" name="saved_address" rows="2" class="kelana-input">{{ old('saved_address', $user->saved_address) }}</textarea>
                        </div>
                    </div>

                    <div class="border-t border-[#f0e5d6] pt-5">
                        <h2 class="text-sm font-bold text-[#52311a]">Ubah Kata Sandi (Opsional)</h2>
                        <p class="mt-0.5 text-[11px] text-[#796b62]">Kosongkan bagian ini apabila tidak ingin mengubah kata sandi.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="current_password" class="kelana-label">Kata Sandi Lama</label>
                                <input id="current_password" name="current_password" type="password" class="kelana-input">
                            </div>
                            <div>
                                <label for="password" class="kelana-label">Kata Sandi Baru</label>
                                <input id="password" name="password" type="password" class="kelana-input">
                            </div>
                            <div>
                                <label for="password_confirmation" class="kelana-label">Konfirmasi Sandi Baru</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" class="kelana-input">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="btn-kelana-primary rounded-full text-xs">Simpan Perubahan</button>
                        <a href="{{ route('profile.show') }}" class="btn-kelana-outline rounded-full text-xs">Batal</a>
                    </div>
                </form>

                @include('partials.customer-help-banner')
            </div>
        </div>
    </section>
@endsection

