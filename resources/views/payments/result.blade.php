@extends('layouts.app')

@section('title', 'Status Pembayaran '.$order->order_number.' — Brownies Kelana')

@section('content')
    @php
        $stateMeta = [
            'berhasil' => [
                'badgeBg' => 'bg-emerald-100 text-emerald-700',
                'icon' => '&#10003;',
                'title' => 'Pembayaran Berhasil',
                'desc' => 'Terima kasih telah memesan di Brownies Kelana! Pembayaranmu sebesar Rp '.number_format($order->total_amount, 0, ',', '.').' telah terkonfirmasi otomatis dan pesanan mulai diproses.',
                'primaryText' => 'Lihat Detail Pesanan',
                'primaryUrl' => route('orders.show', $order->order_number),
                'secondaryText' => 'Kembali ke Beranda',
                'secondaryUrl' => route('home'),
            ],
            'pending' => [
                'badgeBg' => 'bg-amber-100 text-amber-700',
                'icon' => '&#9201;',
                'title' => 'Menunggu Pembayaran',
                'desc' => 'Pesanan '.$order->order_number.' menunggu penyelesaian pembayaran sebelum batas waktu '.$order->payment_deadline_at?->locale('id')->isoFormat('HH:mm').' WIB.',
                'primaryText' => 'Bayar Sekarang',
                'primaryUrl' => route('payments.show', $order),
                'secondaryText' => 'Lihat Detail Pesanan',
                'secondaryUrl' => route('orders.show', $order->order_number),
            ],
            'gagal' => [
                'badgeBg' => 'bg-red-100 text-red-700',
                'icon' => '&#10005;',
                'title' => 'Pembayaran Gagal',
                'desc' => 'Maaf, pembayaran kamu belum berhasil diproses. Silakan coba kembali atau gunakan kanal Midtrans lain.',
                'primaryText' => 'Coba Bayar Lagi',
                'primaryUrl' => route('payments.show', $order),
                'secondaryText' => 'Lihat Detail Pesanan',
                'secondaryUrl' => route('orders.show', $order->order_number),
            ],
            'kedaluwarsa' => [
                'badgeBg' => 'bg-slate-200 text-slate-700',
                'icon' => '&#8987;',
                'title' => 'Pembayaran Kedaluwarsa',
                'desc' => 'Batas waktu pembayaran untuk pesanan '.$order->order_number.' telah berakhir. Sesi tidak dapat digunakan kembali.',
                'primaryText' => 'Lihat Detail Pesanan',
                'primaryUrl' => route('orders.show', $order->order_number),
                'secondaryText' => 'Pesan Ulang di Katalog',
                'secondaryUrl' => route('products.index'),
            ],
            'dibatalkan' => [
                'badgeBg' => 'bg-red-100 text-red-700',
                'icon' => '&oslash;',
                'title' => 'Pembayaran Dibatalkan',
                'desc' => 'Pembayaran telah dibatalkan. Pesanan '.$order->order_number.' tidak aktif dan tidak diproses oleh dapur.',
                'primaryText' => 'Lihat Detail Pesanan',
                'primaryUrl' => route('orders.show', $order->order_number),
                'secondaryText' => 'Kembali ke Beranda',
                'secondaryUrl' => route('home'),
            ],
            'error' => [
                'badgeBg' => 'bg-amber-100 text-amber-800',
                'icon' => '!',
                'title' => 'Terjadi Gangguan Pembayaran',
                'desc' => 'Terjadi gangguan pemrosesan sementara di gateway pembayaran. Transaksi belum diproses, silakan coba lagi.',
                'primaryText' => 'Coba Lagi',
                'primaryUrl' => route('payments.show', $order),
                'secondaryText' => 'Lihat Detail Pesanan',
                'secondaryUrl' => route('orders.show', $order->order_number),
            ],
        ][$state];
    @endphp

    <section class="mx-auto flex max-w-lg flex-col items-center px-4 py-14 text-center">
        <div class="w-full rounded-3xl border border-[#8f6743] bg-white p-8 shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full text-2xl font-bold {{ $stateMeta['badgeBg'] }}">
                {!! $stateMeta['icon'] !!}
            </div>

            <p class="mt-4 text-xs font-bold tracking-wider text-[#8f6743] uppercase">
                {{ $order->order_number }} &middot; Rp {{ number_format($order->total_amount, 0, ',', '.') }}
            </p>
            <h1 class="mt-1 font-serif text-2xl font-bold text-[#593822]">{{ $stateMeta['title'] }}</h1>
            <p class="mt-3 text-xs leading-relaxed text-[#5e4d3e]">{{ $stateMeta['desc'] }}</p>

            <div class="mt-7 space-y-2.5">
                <a href="{{ $stateMeta['primaryUrl'] }}" class="btn-kelana-primary w-full rounded-lg text-xs">
                    {{ $stateMeta['primaryText'] }}
                </a>
                <a href="{{ $stateMeta['secondaryUrl'] }}" class="btn-kelana-outline w-full rounded-lg text-xs">
                    {{ $stateMeta['secondaryText'] }}
                </a>
            </div>
        </div>
    </section>
@endsection

