@extends('layouts.admin')

@section('title', 'Moderasi Ulasan Pelanggan — Panel Admin')
@section('kicker', 'MODERASI & BALASAN ULASAN')

@section('content')
    <div class="space-y-5">
        <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
            <h1 class="font-serif text-xl font-bold text-[#28211c]">Moderasi Ulasan Pelanggan</h1>
            <p class="mt-1 text-xs text-[#796b62]">
                Setujui ulasan yang masuk, pilih testimoni terbaik untuk halaman Home, sembunyikan ulasan tidak pantas dengan alasan, atau kirim balasan resmi admin.
            </p>

            {{-- Tab Status Moderasi --}}
            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), ['status' => 'semua'])) }}"
                    class="rounded-full px-3.5 py-1.5 {{ $statusFilter === 'semua' ? 'bg-[#52311a] text-white' : 'bg-[#fdfbf6] text-[#5e524a] hover:bg-[#f9eddd]' }}">
                    Semua ({{ $counts['all'] }})
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), ['status' => 'menunggu_persetujuan'])) }}"
                    class="rounded-full px-3.5 py-1.5 {{ $statusFilter === 'menunggu_persetujuan' ? 'bg-[#52311a] text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                    Menunggu Persetujuan ({{ $counts['pending'] }})
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), ['status' => 'disetujui'])) }}"
                    class="rounded-full px-3.5 py-1.5 {{ $statusFilter === 'disetujui' ? 'bg-[#52311a] text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                    Disetujui ({{ $counts['approved'] }})
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), ['status' => 'disembunyikan'])) }}"
                    class="rounded-full px-3.5 py-1.5 {{ $statusFilter === 'disembunyikan' ? 'bg-[#52311a] text-white' : 'bg-red-50 text-red-800 hover:bg-red-100' }}">
                    Disembunyikan ({{ $counts['hidden'] }})
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), ['status' => 'home'])) }}"
                    class="rounded-full px-3.5 py-1.5 {{ $statusFilter === 'home' ? 'bg-[#52311a] text-white' : 'bg-[#f9eddd] text-[#52311a]' }}">
                    Tampil di Home ({{ $counts['home'] }})
                </a>
            </div>
        </div>

        {{-- Filter Rating & Pencarian --}}
        <form method="GET" action="{{ route('admin.reviews.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-2xl border border-[#e8dfd3] bg-white p-4 shadow-xs">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="min-w-[200px] flex-1">
                <label class="kelana-label">Cari Isi Ulasan / Pelanggan / Produk</label>
                <input type="text" name="q" value="{{ $searchKeyword }}" placeholder="Ketik kata kunci..." class="kelana-input py-2 text-xs">
            </div>
            <div class="w-40">
                <label class="kelana-label">Filter Rating</label>
                <select name="rating" class="kelana-input py-2 text-xs">
                    <option value="">Semua Bintang</option>
                    @for($star = 5; $star >= 1; $star--)
                        <option value="{{ $star }}" @selected((string) $ratingFilter === (string) $star)>{{ $star }} Bintang</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="btn-kelana-primary px-4 py-2 text-xs">Cari</button>
        </form>

        {{-- Daftar Kartu Ulasan --}}
        <div class="space-y-4">
            @forelse($reviews as $review)
                @php
                    $modBadge = match($review->moderation_status) {
                        'disetujui' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        'disembunyikan' => 'bg-red-50 text-red-800 border-red-200',
                        default => 'bg-amber-50 text-amber-800 border-amber-200',
                    };
                    $modLabel = match($review->moderation_status) {
                        'disetujui' => 'Disetujui',
                        'disembunyikan' => 'Disembunyikan',
                        default => 'Menunggu Persetujuan',
                    };
                @endphp
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-bold text-[#28211c]">{{ $review->user?->name ?? 'Pelanggan' }}</span>
                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $modBadge }}">{{ $modLabel }}</span>
                                @if($review->is_featured_home)
                                    <span class="rounded-full bg-[#52311a] px-2.5 py-0.5 text-[10px] font-bold text-white">Tampil di Home</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-[#796b62]">
                                Produk: <strong class="text-[#52311a]">{{ $review->product?->name ?? '-' }}</strong>
                                @if($review->order)
                                    &bull; Order <a href="{{ route('admin.orders.show', $review->order) }}" class="font-bold text-[#52311a] hover:underline">{{ $review->order->order_number }}</a>
                                @endif
                                &bull; {{ $review->created_at?->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-sm font-bold text-amber-500">{{ str_repeat('★', $review->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
                            <a href="{{ route('admin.reviews.show', $review) }}" class="mt-1 inline-block text-xs font-bold text-[#52311a] hover:underline">
                                Detail &amp; Balas &rarr;
                            </a>
                        </div>
                    </div>

                    <p class="mt-3 rounded-xl bg-[#fdfbf6] p-3.5 text-xs leading-relaxed text-[#28211c]">
                        "{{ $review->comment }}"
                    </p>

                    @if($review->hide_reason)
                        <p class="mt-2 text-xs font-semibold text-red-700">Alasan Disembunyikan: {{ $review->hide_reason }}</p>
                    @endif

                    @if($review->admin_reply)
                        <div class="mt-2 rounded-xl border border-[#e8dfd3] bg-[#f9eddd]/40 p-3 text-xs">
                            <p class="font-bold text-[#52311a]">Balasan Admin:</p>
                            <p class="mt-0.5 text-[#5e524a]">{{ $review->admin_reply }}</p>
                        </div>
                    @endif

                    {{-- Aksi Cepat Moderasi --}}
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-[#efe7dc] pt-3">
                        @if($review->moderation_status !== 'disetujui')
                            <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-emerald-800">
                                    Setujui Ulasan
                                </button>
                            </form>
                        @endif

                        @if($review->moderation_status === 'disetujui')
                            <form method="POST" action="{{ route('admin.reviews.toggle-home', $review) }}">
                                @csrf
                                <button type="submit"
                                    class="rounded-lg border px-3 py-1.5 text-[11px] font-bold {{ $review->is_featured_home ? 'border-[#52311a] bg-[#52311a] text-white' : 'border-[#d6c5b3] bg-white text-[#52311a] hover:bg-[#f9eddd]' }}">
                                    {{ $review->is_featured_home ? 'Lepas dari Home' : 'Tampilkan di Home' }}
                                </button>
                            </form>
                        @endif

                        @if($review->moderation_status !== 'disembunyikan')
                            <form method="POST" action="{{ route('admin.reviews.hide', $review) }}" class="flex items-center gap-1.5">
                                @csrf
                                <input type="text" name="hide_reason" required placeholder="Alasan sembunyikan..."
                                    class="kelana-input w-48 py-1 text-[11px]">
                                <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-[11px] font-bold text-red-700 hover:bg-red-100">
                                    Sembunyikan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-10 text-center text-sm text-[#796b62]">
                    Belum ada ulasan pada filter ini.
                </div>
            @endforelse
        </div>

        @if($reviews->hasPages())
            <div>{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection

