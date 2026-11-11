@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-xs text-[#6f5d50]">
            Menampilkan <span class="font-bold text-[#593822]">{{ $paginator->firstItem() }}</span>
            sampai <span class="font-bold text-[#593822]">{{ $paginator->lastItem() }}</span>
            dari <span class="font-bold text-[#593822]">{{ $paginator->total() }}</span> data
        </p>

        <div class="inline-flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-xl border border-[#e3d5c3] bg-[#f7ebd5]/60 text-xs font-bold text-[#b8a594]">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[#d9c8b4] bg-white text-xs font-bold text-[#593822] shadow-xs transition hover:bg-[#f7ebd5]">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-xs font-bold text-[#8d7d70]">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                class="inline-flex h-9 min-w-[36px] items-center justify-center rounded-xl bg-[#593822] px-3 text-xs font-bold text-white shadow-xs">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                                class="inline-flex h-9 min-w-[36px] items-center justify-center rounded-xl border border-[#d9c8b4] bg-white px-3 text-xs font-semibold text-[#593822] shadow-xs transition hover:bg-[#f7ebd5]">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[#d9c8b4] bg-white text-xs font-bold text-[#593822] shadow-xs transition hover:bg-[#f7ebd5]">&rsaquo;</a>
            @else
                <span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-xl border border-[#e3d5c3] bg-[#f7ebd5]/60 text-xs font-bold text-[#b8a594]">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif

