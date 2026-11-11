@php
    $helpStore = $storeSetting ?? ($sharedStoreSetting ?? \App\Models\StoreSetting::current());
@endphp

<div class="mt-8 flex flex-col items-start justify-between gap-4 rounded-2xl border border-[#d6c2a3] bg-[#e3d3b6] p-5 sm:flex-row sm:items-center">
    <div class="flex items-start gap-3.5">
        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#8f6743]/40 bg-[#f7ebd5] text-[#593822]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 18v-6a9 9 0 0118 0v6M3 18a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3v5zm18 0a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3v5z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-bold text-[#2f2118]">Butuh Bantuan?</h3>
            <p class="mt-0.5 text-xs text-[#593822]">
                WhatsApp CS: <strong>{{ $helpStore->whatsapp_number }}</strong> &middot; Untuk komunikasi dan bantuan pesanan.
            </p>
            <p class="mt-0.5 text-[11px] text-[#6f5d50]">
                {{ $helpStore->operatingHoursSummary() }}
            </p>
        </div>
    </div>
    <a href="{{ $helpStore->whatsappUrl('Halo CS Brownies Kelana, saya butuh bantuan terkait pesanan.') }}" target="_blank" rel="noopener"
        class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-[#c8b29b] bg-white px-5 py-2.5 text-xs font-bold text-[#2f2118] shadow-xs transition hover:bg-[#fcf3e3]">
        Hubungi CS
    </a>
</div>
