@extends('layouts.admin')

@section('title', 'Template Notifikasi — Panel Admin')
@section('kicker', 'PENGATURAN TEMPLATE PESAN')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-wrap gap-2 rounded-2xl border border-[#e8dfd3] bg-white p-4 text-xs font-semibold shadow-xs">
            <a href="{{ route('admin.notifications.index') }}" class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">Inbox Admin</a>
            <a href="{{ route('admin.notifications.templates') }}" class="rounded-xl bg-[#52311a] px-4 py-2 text-white">Template Pesan</a>
            <a href="{{ route('admin.notifications.create') }}" class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">Kirim Notifikasi Khusus</a>
            <a href="{{ route('admin.notifications.history') }}" class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">Riwayat Kirim</a>
        </div>

        <div class="rounded-2xl border border-[#e8dfd3] bg-amber-50/60 p-4 text-xs text-[#5e524a]">
            <p class="font-bold text-[#52311a]">Variabel Otomatis yang Didukung:</p>
            <p class="mt-1">
                Gunakan token <code>{nama_pelanggan}</code>, <code>{nomor_pesanan}</code>, <code>{jadwal}</code>, dan <code>{alasan_batal}</code> agar terisi otomatis saat notifikasi dikirim.
            </p>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            @foreach($templates as $template)
                @php
                    $channels = $template->channels ?? ['dashboard'];
                @endphp
                <form method="POST" action="{{ route('admin.notifications.templates.update', $template) }}"
                    class="space-y-3 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center justify-between">
                        <div>
                            <span class="rounded bg-[#f9eddd] px-2 py-0.5 text-[10px] font-bold text-[#52311a] uppercase">
                                {{ $template->category }} &bull; {{ $template->trigger_key }}
                            </span>
                        </div>
                        <label class="inline-flex items-center gap-1.5 text-xs font-semibold">
                            <input type="checkbox" name="is_active" value="1" @checked($template->is_active)
                                class="rounded border-[#b18950] text-[#52311a]">
                            <span>Aktif</span>
                        </label>
                    </div>

                    <div>
                        <label class="kelana-label">Judul Notifikasi *</label>
                        <input type="text" name="title" value="{{ $template->title }}" required class="kelana-input text-xs">
                    </div>

                    <div>
                        <label class="kelana-label">Isi Pesan Template *</label>
                        <textarea name="message_template" rows="3" required class="kelana-input text-xs">{{ $template->message_template }}</textarea>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1 text-xs">
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center gap-1.5">
                                <input type="checkbox" name="channels[]" value="dashboard" @checked(in_array('dashboard', $channels, true))
                                    class="rounded border-[#b18950] text-[#52311a]">
                                <span>Dashboard</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5">
                                <input type="checkbox" name="channels[]" value="whatsapp" @checked(in_array('whatsapp', $channels, true))
                                    class="rounded border-[#b18950] text-[#52311a]">
                                <span>WhatsApp</span>
                            </label>
                        </div>

                        <button type="submit" class="btn-kelana-primary px-4 py-1.5 text-xs">
                            Simpan Template
                        </button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
@endsection

