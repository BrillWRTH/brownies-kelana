@props(['items' => []])

<nav aria-label="Breadcrumb" class="mb-4 flex flex-wrap items-center gap-1.5 text-xs text-[#796b62]">
    <a href="{{ route('home') }}" class="font-medium transition hover:text-[#593822] hover:underline">Beranda</a>
    @foreach($items as $label => $url)
        <span class="text-[#b9a897] select-none">&rsaquo;</span>
        @if($url && ! $loop->last)
            <a href="{{ $url }}" class="font-medium transition hover:text-[#593822] hover:underline">{{ $label }}</a>
        @else
            <span class="font-bold text-[#593822] truncate max-w-[200px] sm:max-w-xs">{{ $label }}</span>
        @endif
    @endforeach
</nav>
