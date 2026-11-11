@extends('layouts.admin')

@section('title', 'Detail Ulasan Pelanggan — Panel Admin')
@section('kicker', 'DETAIL & BALAS ULASAN')

@section('content')
    <div class="mx-auto max-w-4xl space-y-5">
        <div class="flex items-center justify-between">
            <h1 class="font-serif text-xl font-bold text-[#28211c]">Detail Ulasan Pelanggan</h1>
            <a href="{{ route('admin.reviews.index') }}" class="btn-kelana-outline px-4 py-2 text-xs">&larr; Semua Ulasan</a>
        </div>

        <div class="rounded-2xl border border-[#e8dfd3] bg-white p-6 shadow-xs space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-[#efe7dc] pb-4">
                <div>
                    <p class="text-base font-bold text-[#28211c]">{{ $review->user?->name ?? 'Pelanggan' }}</p>
                    <p class="text-xs text-[#796b62]">
                        Produk: <strong class="text-[#52311a]">{{ $review->product?->name ?? '-' }}</strong>
                        @if($review->order)
                            &bull; Pesanan <a href="{{ route('admin.orders.show', $review->order) }}" class="font-bold text-[#52311a] hover:underline">{{ $review->order->order_number }}</a>
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-amber-500">{{ str_repeat('★', $review->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
                    <p class="text-[11px] font-semibold text-[#796b62]">Status: {{ ucfirst(str_replace('_', ' ', $review->moderation_status)) }}</p>
                </div>
            </div>

            <blockquote class="rounded-xl bg-[#fdfbf6] p-4 text-sm leading-relaxed text-[#28211c]">
                "{{ $review->comment }}"
            </blockquote>

            @if($review->hide_reason)
                <p class="rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">
                    Alasan disembunyikan: {{ $review->hide_reason }}
                </p>
            @endif

            <div class="flex flex-wrap gap-2.5 pt-2">
                @if($review->moderation_status !== 'disetujui')
                    <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800">
                            Setujui Tampil di Publik
                        </button>
                    </form>
                @endif

                @if($review->moderation_status === 'disetujui')
                    <form method="POST" action="{{ route('admin.reviews.toggle-home', $review) }}">
                        @csrf
                        <button type="submit" class="btn-kelana-outline px-4 py-2 text-xs">
                            {{ $review->is_featured_home ? 'Lepas dari Halaman Home' : 'Tampilkan di Halaman Home' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            {{-- Form Balas Ulasan --}}
            <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                <h2 class="text-sm font-semibold text-[#52311a]">Balasan Resmi Brownies Kelana</h2>
                <form method="POST" action="{{ route('admin.reviews.reply', $review) }}" class="mt-3 space-y-3">
                    @csrf
                    <div>
                        <label for="admin_reply" class="kelana-label">Isi Balasan *</label>
                        <textarea id="admin_reply" name="admin_reply" rows="3" required
                            placeholder="Terima kasih Kak atas ulasannya! Senang sekali cocok di selera keluarga."
                            class="kelana-input text-xs">{{ old('admin_reply', $review->admin_reply) }}</textarea>
                    </div>
                    <button type="submit" class="btn-kelana-primary px-4 py-2 text-xs">
                        Simpan &amp; Kirim Notifikasi Balasan
                    </button>
                </form>
            </div>

            {{-- Form Sembunyikan Ulasan --}}
            <div class="rounded-2xl border border-red-200 bg-white p-5 shadow-xs">
                <h2 class="text-sm font-semibold text-red-800">Sembunyikan Ulasan</h2>
                <p class="mt-1 text-xs text-[#796b62]">Ulasan yang disembunyikan tidak tampil di halaman produk ataupun Home.</p>
                <form method="POST" action="{{ route('admin.reviews.hide', $review) }}" class="mt-3 space-y-3">
                    @csrf
                    <div>
                        <label for="hide_reason" class="kelana-label text-red-800">Alasan Moderasi *</label>
                        <input id="hide_reason" name="hide_reason" type="text" required value="{{ old('hide_reason', $review->hide_reason) }}"
                            placeholder="Contoh: Mengandung kata tidak pantas / spam" class="kelana-input text-xs">
                    </div>
                    <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-xs font-bold text-white hover:bg-red-800">
                        Sembunyikan Ulasan
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

