<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\StoreSetting;
use App\Models\UserNotification;
use App\Notifications\OrderLifecycleNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

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
        // 1. Pastikan pesanan adalah milik pengguna dan periksa aturan pembatalan
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->expireIfPastDeadline();
        $order->refresh();

        if (! $order->canBeCancelledByCustomer()) {
            return back()->with('error', 'Pesanan yang sudah diproses atau ditutup tidak dapat dibatalkan melalui website. Silakan hubungi CS.');
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

        // 3. Perbarui status pesanan menjadi dibatalkan secara atomik beserta log & notifikasi admin
        $cancelled = DB::transaction(function () use ($order, $finalReason): bool {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            if (! $lockedOrder || ! $lockedOrder->canBeCancelledByCustomer()) {
                return false;
            }

            $lockedOrder->update([
                'order_status' => 'dibatalkan',
                'payment_status' => 'dibatalkan',
                'cancelled_at' => now(),
                'cancelled_by' => 'customer',
                'cancellation_reason' => $finalReason,
            ]);

            if ($latestPayment = $lockedOrder->latestPayment) {
                $latestPayment->update(['status' => 'cancel']);
            }

            $lockedOrder->logs()->create([
                'user_id' => Auth::id(),
                'actor_name' => Auth::user()->name,
                'event_type' => 'cancelled',
                'title' => 'Pesanan Dibatalkan oleh Pelanggan',
                'description' => 'Alasan: '.$finalReason,
            ]);

            UserNotification::create([
                'user_id' => null,
                'order_id' => $lockedOrder->id,
                'audience' => 'admin',
                'category' => 'pesanan',
                'channel' => 'dashboard',
                'title' => 'Pesanan Dibatalkan oleh Customer',
                'message' => $lockedOrder->order_number.' · '.$lockedOrder->customer_name.' membatalkan pesanan. Alasan: "'.$finalReason.'".',
                'delivery_status' => 'terkirim',
                'action_url' => route('admin.orders.show', $lockedOrder),
            ]);

            return true;
        });

        if (! $cancelled) {
            return back()->with('error', 'Pesanan ini tidak lagi memenuhi syarat pembatalan.');
        }

        $order->refresh();

        // 4. Kirim email konfirmasi pembatalan pesanan
        if (! empty($order->customer_email)) {
            try {
                Notification::route('mail', $order->customer_email)->notify(
                    new OrderLifecycleNotification(
                        $order,
                        'Pesanan '.$order->order_number.' Dibatalkan — Brownies Kelana',
                        'Konfirmasi pembatalan pesanan Anda telah dicatat.',
                        'Alasan pembatalan: '.$finalReason,
                        'Lihat Detail Pesanan',
                        route('orders.show', $order->order_number)
                    )
                );
            } catch (Throwable $exception) {
                Log::warning('Gagal mengirim email pembatalan '.$order->order_number.': '.$exception->getMessage());
            }
        }

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

        // 3. Simpan ulasan secara atomik agar tidak terjadi duplikasi ulasan per pesanan
        $created = DB::transaction(function () use ($order, $validated): bool {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            if (! $lockedOrder || ! $lockedOrder->isCompleted() || $lockedOrder->review()->exists()) {
                return false;
            }

            $firstItem = $lockedOrder->items()->first();

            Review::create([
                'order_id' => $lockedOrder->id,
                'user_id' => Auth::id(),
                'product_id' => $firstItem?->product_id,
                'rating' => (int) $validated['rating'],
                'comment' => trim($validated['comment']),
                'moderation_status' => 'menunggu_persetujuan',
                'is_featured_home' => false,
            ]);

            $lockedOrder->logs()->create([
                'user_id' => Auth::id(),
                'actor_name' => Auth::user()->name,
                'event_type' => 'reviewed',
                'title' => 'Ulasan Pelanggan Dikirim',
                'description' => 'Memberikan rating '.(int) $validated['rating'].'/5 bintang.',
            ]);

            UserNotification::create([
                'user_id' => null,
                'order_id' => $lockedOrder->id,
                'audience' => 'admin',
                'category' => 'pesanan',
                'channel' => 'dashboard',
                'title' => 'Ulasan Baru dari Pelanggan',
                'message' => Auth::user()->name.' mengulas pesanan '.$lockedOrder->order_number.' ('.(int) $validated['rating'].'/5 bintang). Menunggu persetujuan.',
                'delivery_status' => 'terkirim',
                'action_url' => route('admin.reviews.index'),
            ]);

            return true;
        });

        if (! $created) {
            return back()->with('error', 'Kamu sudah memberikan ulasan untuk pesanan ini.');
        }

        return redirect()->route('orders.show', $order->order_number)
            ->with('success', 'Terima kasih! Ulasanmu berhasil dikirim.');
    }
}
