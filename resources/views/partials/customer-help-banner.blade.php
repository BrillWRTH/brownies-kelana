@php
    $helpStore = $storeSetting ?? ($sharedStoreSetting ?? \App\Models\StoreSetting::current());
@endphp

<div class="mt-8 flex flex-col items-start justify-between gap-4 rounded-2xl border border-[#e3d5c3] bg-[#f9eddd] p-5 sm:flex-row sm:items-center">
    <div>
        <h3 class="text-sm font-bold text-[#52311a]">Butuh Bantuan?</h3>
        <p class="mt-1 text-xs text-[#5e524a]">
            WhatsApp CS: <strong class="text-[#28211c]">{{ $helpStore->whatsapp_number }}</strong> &middot; Untuk komunikasi dan bantuan pesanan.
        </p>
        <p class="mt-0.5 text-[11px] text-[#796b62]">
            {{ $helpStore->operatingHoursSummary() }}
        </p>
    </div>
    <a href="{{ $helpStore->whatsappUrl('Halo CS Brownies Kelana, saya butuh bantuan terkait pesanan.') }}" target="_blank" rel="noopener"
        class="btn-kelana-primary shrink-0 text-xs">
        Hubungi CS &rarr;
    </a>
</div>

