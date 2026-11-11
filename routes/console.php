<?php

use App\Models\Order;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire-unpaid', function (): void {
    // 1. Cari seluruh pesanan belum dibayar yang sudah melewati batas waktu
    $overdueOrders = Order::query()
        ->where('order_status', 'menunggu_pembayaran')
        ->where('payment_status', 'belum_dibayar')
        ->whereNotNull('payment_deadline_at')
        ->where('payment_deadline_at', '<', now())
        ->get();

    // 2. Tutup tiap pesanan yang melewati deadline secara atomik
    $closedCount = 0;
    foreach ($overdueOrders as $overdueOrder) {
        if ($overdueOrder->expireIfPastDeadline()) {
            $closedCount++;
        }
    }

    $this->info('Pesanan kedaluwarsa ditutup otomatis: '.$closedCount);
})->purpose('Menutup otomatis pesanan yang melewati batas waktu pembayaran');

Schedule::command('orders:expire-unpaid')
    ->everyMinute()
    ->withoutOverlapping();
