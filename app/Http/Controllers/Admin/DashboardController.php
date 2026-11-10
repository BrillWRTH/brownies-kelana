<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Menampilkan ringkasan operasional, pesanan terbaru, jadwal produksi, dan inbox admin.
     */
    public function index(Request $request): View
    {
        // 1. Periksa pesanan yang melewati batas waktu pembayaran agar metrik selalu akurat
        $expiredCandidates = Order::query()
            ->where('order_status', 'menunggu_pembayaran')
            ->get();

        foreach ($expiredCandidates as $candidate) {
            $candidate->expireIfPastDeadline();
        }

        $storeSetting = StoreSetting::current();
        $searchKeyword = trim((string) $request->query('q', ''));

        // 2. Hitung metrik operasional utama UMKM Brownies Kelana
        $totalOrders = Order::query()->count();
        $pendingPaymentCount = Order::query()
            ->where('order_status', 'menunggu_pembayaran')
            ->count();
        $needsActionCount = Order::query()
            ->whereIn('order_status', ['diproses', 'dikemas', 'dalam_pengiriman', 'siap_diambil'])
            ->count();
        $processingCount = Order::query()
            ->whereIn('order_status', ['diproses', 'dikemas'])
            ->count();
        $inTransitCount = Order::query()
            ->whereIn('order_status', ['dalam_pengiriman', 'siap_diambil'])
            ->count();
        $paidOrdersCount = Order::query()
            ->where('payment_status', 'lunas')
            ->count();
        $paidTotalRevenue = (int) Order::query()
            ->where('payment_status', 'lunas')
            ->sum('total_amount');
        $completedCount = Order::query()
            ->whereIn('order_status', ['sampai_tujuan', 'sudah_diambil'])
            ->count();

        // 3. Muat daftar 5 pesanan terbaru (dapat disaring melalui kolom pencarian)
        $latestOrdersQuery = Order::query()
            ->with(['items', 'latestPayment', 'pickupLocation'])
            ->latest();

        if ($searchKeyword !== '') {
            $latestOrdersQuery->where(function ($query) use ($searchKeyword): void {
                $query->where('order_number', 'like', '%'.$searchKeyword.'%')
                    ->orWhere('customer_name', 'like', '%'.$searchKeyword.'%')
                    ->orWhereHas('items', function ($itemQuery) use ($searchKeyword): void {
                        $itemQuery->where('product_name', 'like', '%'.$searchKeyword.'%');
                    });
            });
        }

        $latestOrders = $latestOrdersQuery->take(5)->get();

        // 4. Muat pesanan yang masih menunggu pembayaran Midtrans dan notifikasi inbox admin
        $pendingOrders = Order::query()
            ->with('latestPayment')
            ->where('order_status', 'menunggu_pembayaran')
            ->latest()
            ->take(3)
            ->get();

        $unreadNotifications = UserNotification::query()
            ->where('audience', 'admin')
            ->whereNull('read_at')
            ->latest()
            ->take(4)
            ->get();

        $unreadNotificationsCount = UserNotification::query()
            ->where('audience', 'admin')
            ->whereNull('read_at')
            ->count();

        // 5. Susun ringkasan jadwal produksi untuk hari ini dan besok (order aktif vs total pcs)
        $todayDate = Carbon::today();
        $tomorrowDate = Carbon::tomorrow();
        $scheduleSummary = [];

        foreach ([$todayDate, $tomorrowDate] as $targetDate) {
            $dayOrders = Order::query()
                ->with('items')
                ->whereDate('scheduled_date', $targetDate->toDateString())
                ->whereNotIn('order_status', ['dibatalkan', 'kedaluwarsa'])
                ->get();

            $deliveryPcs = 0;
            $pickupPcs = 0;
            foreach ($dayOrders as $dayOrder) {
                $orderQty = (int) $dayOrder->items->sum('quantity');
                if ($dayOrder->delivery_type === 'pickup') {
                    $pickupPcs += $orderQty;
                } else {
                    $deliveryPcs += $orderQty;
                }
            }

            $scheduleSummary[] = [
                'label' => $targetDate->isToday() ? 'Hari ini · '.$targetDate->locale('id')->isoFormat('D MMM') : 'Besok · '.$targetDate->locale('id')->isoFormat('D MMM'),
                'date' => $targetDate->toDateString(),
                'orders_count' => $dayOrders->count(),
                'total_pcs' => $deliveryPcs + $pickupPcs,
                'delivery_pcs' => $deliveryPcs,
                'pickup_pcs' => $pickupPcs,
            ];
        }

        return view('admin.dashboard', compact(
            'storeSetting',
            'searchKeyword',
            'totalOrders',
            'pendingPaymentCount',
            'needsActionCount',
            'processingCount',
            'inTransitCount',
            'paidOrdersCount',
            'paidTotalRevenue',
            'completedCount',
            'latestOrders',
            'pendingOrders',
            'unreadNotifications',
            'unreadNotificationsCount',
            'scheduleSummary'
        ));
    }

    /**
     * Mengekspor daftar pesanan UMKM Brownies Kelana menjadi file CSV yang siap dibuka Excel.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        // 1. Terapkan filter opsional apabila admin mengekspor dari tampilan terfilter
        $ordersQuery = Order::query()
            ->with(['items', 'pickupLocation', 'latestPayment'])
            ->latest();

        if ($search = trim((string) $request->query('q', ''))) {
            $ordersQuery->where(function ($query) use ($search): void {
                $query->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%');
            });
        }

        if ($status = trim((string) $request->query('status', ''))) {
            $ordersQuery->where('order_status', $status);
        }

        if ($source = trim((string) $request->query('source', ''))) {
            $ordersQuery->where('source', $source);
        }

        $orders = $ordersQuery->get();
        $filename = 'pesanan-brownies-kelana-'.now()->format('Y-m-d').'.csv';

        // 2. Bangun stream output CSV dengan UTF-8 BOM agar karakter Rupiah terbaca rapi di Excel
        return response()->streamDownload(function () use ($orders): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'No. Pesanan',
                'Waktu Pesan',
                'Sumber',
                'Pelanggan',
                'Telepon',
                'Metode Penerimaan',
                'Lokasi / Alamat',
                'Jadwal Kirim/Pickup',
                'Slot Jam',
                'Rincian Item',
                'Subtotal (Rp)',
                'Ongkir (Rp)',
                'Total Bayar (Rp)',
                'Status Pembayaran',
                'Kanal Pembayaran',
                'Status Pesanan',
            ]);

            foreach ($orders as $order) {
                $itemsText = $order->items->map(function ($item): string {
                    $variant = $item->variant_name ? ' ('.$item->variant_name.')' : '';

                    return $item->quantity.'x '.$item->product_name.$variant;
                })->implode('; ');

                $location = $order->delivery_type === 'pickup'
                    ? ($order->pickupLocation?->name ?? 'Pickup')
                    : ($order->delivery_address ?? '-');

                fputcsv($handle, [
                    $order->order_number,
                    $order->created_at?->format('Y-m-d H:i'),
                    $order->source === 'whatsapp' ? 'WhatsApp Manual' : 'Website',
                    $order->customer_name,
                    $order->customer_phone,
                    $order->delivery_type === 'pickup' ? 'Pickup' : 'Pengiriman',
                    $location,
                    $order->scheduled_date?->format('Y-m-d'),
                    $order->scheduled_time_slot,
                    $itemsText,
                    $order->subtotal,
                    $order->shipping_fee,
                    $order->total_amount,
                    $order->paymentStatusLabel(),
                    $order->latestPayment?->channel_label ?? '-',
                    $order->orderStatusLabel(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
