<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\StoreSetting;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Menampilkan halaman detail pesanan pelanggan beserta timeline status dan ulasan.
     */
    public function show(string $orderNumber): View
    {
        // 1. Cari pesanan berdasarkan nomor pesanan milik pelanggan yang sedang login
        $normalizedNumber = strtoupper(trim($orderNumber));
        $order = Order::query()
            ->with(['items.product', 'pickupLocation', 'shippingZone', 'logs', 'latestPayment', 'review'])
            ->where('user_id', Auth::id())
            ->where(function ($query) use ($normalizedNumber): void {
                $query->where('order_number', $normalizedNumber)
                    ->orWhere('order_number', str_replace(' ', '-', $normalizedNumber));

                if (ctype_digit($normalizedNumber)) {
                    $query->orWhere('id', (int) $normalizedNumber);
                }
            })
            ->first();

        // 2. Jika pesanan tidak ditemukan atau bukan milik pengguna, tampilkan halaman khusus Pesanan Tidak Ditemukan
        if (! $order) {
            return view('orders.not-found', [
                'orderNumber' => $orderNumber,
                'user' => Auth::user(),
                'storeSetting' => StoreSetting::current(),
            ]);
        }

        // 3. Periksa batas waktu pembayaran otomatis jika masih menunggu pembayaran
        $order->expireIfPastDeadline();
        $order->refresh();
        $order->load(['items', 'pickupLocation', 'logs', 'latestPayment', 'review']);

        $storeSetting = StoreSetting::current();

        return view('orders.show', compact('order', 'storeSetting'));
    }

    /**
     * Membatalkan pesanan oleh pelanggan selama status belum diproses.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        // 1. Pastikan pesanan adalah milik pengguna dan masih memenuhi syarat pembatalan
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $order->canBeCancelledByCustomer()) {
            return back()->with('error', 'Pesanan yang sudah diproses tidak dapat dibatalkan melalui website. Silakan hubungi CS.');
        }

        // 2. Validasi alasan pembatalan dan kotak centang konfirmasi
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:255'],
            'custom_reason' => ['nullable', 'string', 'max:255'],
            'confirm_cancel' => ['accepted'],
        ], [
            'cancellation_reason.required' => 'Pilih alasan pembatalan pesanan.',
            'confirm_cancel.accepted' => 'Centang pernyataan konfirmasi pembatalan pesanan.',
        ]);

        $finalReason = $validated['cancellation_reason'] === 'Lainnya' && ! empty($validated['custom_reason'])
            ? trim($validated['custom_reason'])
            : $validated['cancellation_reason'];

        // 3. Perbarui status pesanan menjadi dibatalkan beserta catatan log & notifikasi admin
        $order->update([
            'order_status' => 'dibatalkan',
            'payment_status' => 'dibatalkan',
            'cancelled_at' => now(),
            'cancelled_by' => 'customer',
            'cancellation_reason' => $finalReason,
        ]);

        if ($latestPayment = $order->latestPayment) {
            $latestPayment->update(['status' => 'cancel']);
        }

        $order->logs()->create([
            'user_id' => Auth::id(),
            'actor_name' => Auth::user()->name,
            'event_type' => 'cancelled',
            'title' => 'Pesanan Dibatalkan oleh Pelanggan',
            'description' => 'Alasan: '.$finalReason,
        ]);

        UserNotification::create([
            'user_id' => null,
            'order_id' => $order->id,
            'audience' => 'admin',
            'category' => 'pesanan',
            'channel' => 'dashboard',
            'title' => 'Pesanan Dibatalkan oleh Customer',
            'message' => $order->order_number.' · '.$order->customer_name.' membatalkan pesanan. Alasan: "'.$finalReason.'".',
            'delivery_status' => 'terkirim',
            'action_url' => route('admin.orders.show', $order),
        ]);

        return redirect()->route('orders.show', $order->order_number)
            ->with('success', 'Pesanan '.$order->order_number.' berhasil dibatalkan.');
    }

    /**
     * Menyimpan ulasan bintang dan komentar pelanggan untuk pesanan yang sudah selesai.
     */
    public function storeReview(Request $request, Order $order): RedirectResponse
    {
        // 1. Validasi kepemilikan pesanan, status selesai, dan belum pernah diulas
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $order->isCompleted()) {
            return back()->with('error', 'Ulasan hanya dapat diberikan setelah pesanan selesai diterima atau diambil.');
        }

        if ($order->review()->exists()) {
            return back()->with('error', 'Kamu sudah memberikan ulasan untuk pesanan ini.');
        }

        // 2. Validasi input rating 1-5 bintang dan isi komentar ulasan
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'rating.required' => 'Pilih rating 1 hingga 5 bintang.',
            'comment.required' => 'Tuliskan ulasan pengalamanmu menikmati Brownies Kelana.',
            'comment.min' => 'Ulasan minimal 5 karakter.',
        ]);

        // 3. Simpan ulasan ke database dengan status menunggu persetujuan moderasi admin
        $firstItem = $order->items()->first();

        Review::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'product_id' => $firstItem?->product_id,
            'rating' => (int) $validated['rating'],
            'comment' => trim($validated['comment']),
            'moderation_status' => 'menunggu_persetujuan',
            'is_featured_home' => false,
        ]);

        $order->logs()->create([
            'user_id' => Auth::id(),
            'actor_name' => Auth::user()->name,
            'event_type' => 'reviewed',
            'title' => 'Ulasan Pelanggan Dikirim',
            'description' => 'Memberikan rating '.(int) $validated['rating'].'/5 bintang.',
        ]);

        UserNotification::create([
            'user_id' => null,
            'order_id' => $order->id,
            'audience' => 'admin',
            'category' => 'pesanan',
            'channel' => 'dashboard',
            'title' => 'Ulasan Baru dari Pelanggan',
            'message' => Auth::user()->name.' mengulas pesanan '.$order->order_number.' ('.(int) $validated['rating'].'/5 bintang). Menunggu persetujuan.',
            'delivery_status' => 'terkirim',
            'action_url' => route('admin.reviews.index'),
        ]);

        return redirect()->route('orders.show', $order->order_number)
            ->with('success', 'Terima kasih! Ulasanmu berhasil dikirim.');
    }
}
