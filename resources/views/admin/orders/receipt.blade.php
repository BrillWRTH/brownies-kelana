@extends('layouts.admin')

@section('title', 'Nota Pesanan '.$order->order_number.' — Brownies Kelana')
@section('kicker', 'NOTA PESANAN & SLIP DAPUR')

@section('content')
    <div class="mx-auto max-w-2xl space-y-4">
        {{-- Tombol Aksi Cetak (disembunyikan saat mencetak) --}}
        <div class="no-print flex items-center justify-between rounded-xl border border-[#e8dfd3] bg-white px-4 py-3 shadow-xs">
            <a href="{{ route('admin.orders.show', $order) }}" class="text-xs font-bold text-[#52311a] hover:underline">
                &larr; Kembali ke Detail Pesanan
            </a>
            <button type="button" onclick="window.print()" class="btn-kelana-primary px-4 py-2 text-xs">
                Cetak Nota / Simpan PDF
            </button>
        </div>

        {{-- Lembar Nota Pesanan --}}
        <div class="rounded-2xl border border-[#d6c5b3] bg-white p-6 shadow-xs sm:p-8">
            <div class="flex items-start justify-between border-b border-dashed border-[#c7b49e] pb-5">
                <div>
                    <h1 class="font-serif text-2xl font-bold text-[#52311a]">{{ $storeSetting->store_name }}</h1>
                    <p class="mt-1 max-w-sm text-[11px] text-[#5e524a]">{{ $storeSetting->store_address }}</p>
                    <p class="mt-0.5 text-[11px] text-[#796b62]">WA: {{ $storeSetting->whatsapp_number }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold tracking-widest text-[#936b49] uppercase">NOTA PESANAN</p>
                    <p class="mt-1 text-lg font-extrabold text-[#28211c]">{{ $order->order_number }}</p>
                    <p class="mt-0.5 text-[11px] text-[#796b62]">{{ $order->created_at?->format('d/m/Y H:i') }} WIB</p>
                </div>
            </div>

            <div class="grid gap-4 border-b border-dashed border-[#c7b49e] py-4 text-xs sm:grid-cols-2">
                <div>
                    <p class="text-[10px] font-bold text-[#936b49] uppercase">Pemesan &amp; Penerima</p>
                    <p class="mt-1 font-bold text-[#28211c]">{{ $order->customer_name }} ({{ $order->customer_phone }})</p>
                    <p class="text-[#5e524a]">Penerima: {{ $order->recipient_name }} &middot; {{ $order->recipient_phone }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-[#936b49] uppercase">Jadwal &amp; Metode</p>
                    <p class="mt-1 font-bold text-[#52311a]">
                        {{ $order->scheduled_date?->locale('id')->isoFormat('dddd, D MMM YYYY') }} ({{ $order->scheduled_time_slot }})
                    </p>
                    <p class="text-[#5e524a]">
                        @if($order->delivery_type === 'pickup')
                            Pickup: {{ $order->pickupLocation?->name }}
                        @else
                            Delivery: {{ $order->delivery_address }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="py-4">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-[#efe7dc] text-[10px] font-bold text-[#936b49] uppercase">
                            <th class="pb-2 text-left">Produk</th>
                            <th class="pb-2 text-center">Qty</th>
                            <th class="pb-2 text-right">Harga</th>
                            <th class="pb-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f5eee4]">
                        @foreach($order->items as $item)
                            <tr>
                                <td class="py-2.5 pr-2">
                                    <p class="font-bold text-[#28211c]">{{ $item->product_name }}</p>
                                    @if($item->variant_name)
                                        <p class="text-[11px] text-[#796b62]">Varian: {{ $item->variant_name }}</p>
                                    @endif
                                    @if($item->custom_note)
                                        <p class="text-[11px] font-semibold text-[#52311a]">Tulisan Kue: "{{ $item->custom_note }}"</p>
                                    @endif
                                </td>
                                <td class="py-2.5 text-center font-semibold">{{ $item->quantity }}</td>
                                <td class="py-2.5 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="py-2.5 text-right font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($order->customer_note)
                <div class="mb-4 rounded-lg border border-[#efe7dc] bg-[#fdfbf6] p-3 text-xs">
                    <strong>Catatan Pelanggan:</strong> {{ $order->customer_note }}
                </div>
            @endif

            <div class="space-y-1.5 border-t border-dashed border-[#c7b49e] pt-4 text-xs">
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Ongkos Kirim</span>
                    <span>Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between pt-1 text-base font-extrabold text-[#52311a]">
                    <span>Total Tagihan</span>
                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between pt-2 text-[11px] text-[#796b62]">
                    <span>Status Pembayaran: <strong class="text-[#28211c]">{{ $order->paymentStatusLabel() }}</strong></span>
                    <span>Kanal: {{ $order->latestPayment?->channel_label ?? '-' }}</span>
                </div>
            </div>

            <p class="mt-6 border-t border-[#efe7dc] pt-4 text-center text-[11px] italic text-[#8d7d70]">
                Terima kasih telah memesan Brownies Kelana — Dibuat Untuk Dinikmati.
            </p>
        </div>
    </div>
@endsection

