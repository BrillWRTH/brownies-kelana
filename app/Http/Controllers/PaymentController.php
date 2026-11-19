<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\StoreSetting;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Menampilkan halaman pembayaran QRIS Brownies Kelana, form unggah bukti bayar, dan tombol hubungi penjual.
     */
    public function show(Order $order): View|RedirectResponse
    {
        // 1. Pastikan pesanan ini adalah milik pelanggan yang sedang login
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // 2. Periksa batas waktu pembayaran (tidak mengkedaluwarsakan order yang sudah unggah bukti)
        $order->expireIfPastDeadline();
        $order->refresh();
        $order->load(['items', 'pickupLocation', 'latestPayment']);
        $payment = $order->latestPayment;

        // 3. Arahkan ke halaman hasil bila status pembayaran sudah final
        if ($order->payment_status === 'lunas') {
            return redirect()->route('payments.result', ['order' => $order, 'state' => 'berhasil']);
        }

        if ($order->payment_status === 'kedaluwarsa') {
            return redirect()->route('payments.result', ['order' => $order, 'state' => 'kedaluwarsa']);
        }

        if ($order->payment_status === 'dibatalkan') {
            return redirect()->route('payments.result', ['order' => $order, 'state' => 'dibatalkan']);
        }

        if ($order->payment_status === 'gagal') {
            return redirect()->route('payments.result', ['order' => $order, 'state' => 'gagal']);
        }

        // 4. Pastikan selalu ada catatan pembayaran QRIS aktif
        if (! $payment) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => 'qris_manual',
                'payment_method' => 'qris',
                'channel_label' => 'QRIS Brownies Kelana',
                'qris_url' => 'images/kelana/qris-brownies-kelana.jpg',
                'amount' => $order->total_amount,
                'status' => 'pending',
                'expires_at' => $order->payment_deadline_at,
            ]);
        }

        $storeSetting = StoreSetting::current();
        $qrisImagePath = 'images/kelana/qris-brownies-kelana.jpg';

        $waMessage = "Halo Admin Brownies Kelana, saya ingin mengonfirmasi pembayaran QRIS untuk:\n"
            ."• No. Pesanan: {$order->order_number}\n"
            ."• Nama: {$order->customer_name}\n"
            .'• Total Tagihan: Rp '.number_format($order->total_amount, 0, ',', '.')."\n"
            ."Berikut saya lampirkan bukti pembayarannya. Terima kasih!";
        $sellerWhatsappUrl = $storeSetting->whatsappUrl($waMessage);

        return view('payments.show', compact(
            'order',
            'payment',
            'storeSetting',
            'qrisImagePath',
            'sellerWhatsappUrl'
        ));
    }

    /**
     * Menyimpan bukti pembayaran QRIS yang dilampirkan pelanggan dan mengirim notifikasi ke admin untuk verifikasi manual.
     */
    public function uploadProof(Request $request, Order $order): RedirectResponse
    {
        // 1. Validasi kepemilikan pesanan
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->expireIfPastDeadline();
        $order->refresh();

        if ($order->payment_status === 'lunas') {
            return redirect()->route('payments.result', ['order' => $order, 'state' => 'berhasil']);
        }

        if (in_array($order->order_status, ['dibatalkan', 'kedaluwarsa'], true)) {
            return redirect()->route('orders.show', $order->order_number)
                ->with('error', 'Pesanan ini sudah ditutup ('.$order->orderStatusLabel().') dan tidak dapat menerima bukti pembayaran.');
        }

        // 2. Validasi file gambar bukti pembayaran dan keterangan opsional
        $validated = $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sender_name' => ['nullable', 'string', 'max:150'],
            'customer_payment_note' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_proof.required' => 'Lampirkan foto atau tangkapan layar bukti pembayaran QRIS terlebih dahulu.',
            'payment_proof.image' => 'File bukti bayar harus berupa gambar (JPG, PNG, atau WEBP).',
            'payment_proof.mimes' => 'Format gambar bukti bayar yang didukung: JPG, JPEG, PNG, atau WEBP.',
            'payment_proof.max' => 'Ukuran file bukti bayar maksimal 5MB.',
        ]);

        // 3. Simpan bukti pembayaran di storage publik dan perbarui status menjadi menunggu verifikasi admin
        DB::transaction(function () use ($request, $order, $validated): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $payment = $lockedOrder->latestPayment;

            if (! $payment) {
                $payment = Payment::create([
                    'order_id' => $lockedOrder->id,
                    'gateway' => 'qris_manual',
                    'payment_method' => 'qris',
                    'channel_label' => 'QRIS Brownies Kelana',
                    'qris_url' => 'images/kelana/qris-brownies-kelana.jpg',
                    'amount' => $lockedOrder->total_amount,
                    'status' => 'pending',
                    'expires_at' => $lockedOrder->payment_deadline_at,
                ]);
            }

            // Hapus file bukti lama jika pelanggan mengunggah ulang
            if ($payment->proof_path && str_starts_with($payment->proof_path, 'storage/')) {
                Storage::disk('public')->delete(substr($payment->proof_path, 8));
            }

            $storedPath = $request->file('payment_proof')->store('payment-proofs', 'public');
            $proofPath = 'storage/'.$storedPath;

            $senderName = trim((string) ($validated['sender_name'] ?? '')) ?: null;
            $paymentNote = trim((string) ($validated['customer_payment_note'] ?? '')) ?: null;

            $payment->update([
                'gateway' => 'qris_manual',
                'payment_method' => 'qris',
                'channel_label' => 'QRIS Brownies Kelana',
                'qris_url' => 'images/kelana/qris-brownies-kelana.jpg',
                'proof_path' => $proofPath,
                'proof_uploaded_at' => now(),
                'sender_name' => $senderName,
                'customer_payment_note' => $paymentNote,
                'status' => 'waiting_verification',
                'rejection_reason' => null,
            ]);

            $lockedOrder->update([
                'payment_status' => 'menunggu_verifikasi',
            ]);

            $descParts = ['Pelanggan melampirkan bukti pembayaran QRIS untuk diverifikasi admin.'];
            if ($senderName) {
                $descParts[] = 'Pengirim: '.$senderName.'.';
            }
            if ($paymentNote) {
                $descParts[] = 'Catatan: '.$paymentNote;
            }

            $lockedOrder->logs()->create([
                'user_id' => Auth::id(),
                'actor_name' => Auth::user()->name,
                'event_type' => 'payment_proof_uploaded',
                'title' => 'Bukti Pembayaran QRIS Diunggah',
                'description' => implode(' ', $descParts),
            ]);

            UserNotification::create([
                'user_id' => null,
                'order_id' => $lockedOrder->id,
                'audience' => 'admin',
                'category' => 'pesanan',
                'channel' => 'dashboard',
                'title' => 'Bukti Bayar QRIS Perlu Verifikasi',
                'message' => $lockedOrder->order_number.' · '.$lockedOrder->customer_name.' mengunggah bukti bayar QRIS Rp '.number_format($lockedOrder->total_amount, 0, ',', '.').'. Silakan cek & verifikasi manual.',
                'delivery_status' => 'terkirim',
                'action_url' => route('admin.orders.show', $lockedOrder),
            ]);
        });

        return redirect()->route('payments.show', $order)
            ->with('success', 'Bukti pembayaran QRIS berhasil dilampirkan! Admin Brownies Kelana akan segera memverifikasi pembayaranmu secara manual.');
    }

    /**
     * Memeriksa pembaruan status verifikasi pembayaran di database.
     */
    public function checkStatus(Order $order): RedirectResponse
    {
        // 1. Validasi kepemilikan pesanan
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // 2. Periksa tenggat dan muat ulang status terbaru yang diverifikasi admin
        $order->expireIfPastDeadline();
        $order->refresh();

        $state = $this->resolveResultState($order->payment_status);

        if ($order->payment_status === 'menunggu_verifikasi') {
            return redirect()->route('payments.show', $order)
                ->with('success', 'Bukti pembayaranmu sudah diterima dan sedang menunggu verifikasi manual oleh Admin Brownies Kelana.');
        }

        if ($order->payment_status === 'belum_dibayar') {
            return redirect()->route('payments.show', $order)
                ->with('error', 'Pembayaran belum terverifikasi. Silakan scan QRIS dan lampirkan bukti bayar atau hubungi penjual via WhatsApp.');
        }

        return redirect()->route('payments.result', ['order' => $order, 'state' => $state]);
    }

    /**
     * Menampilkan tampilan status pembayaran tervalidasi dari database.
     */
    public function result(Order $order, string $state): View|RedirectResponse
    {
        // 1. Validasi kepemilikan pesanan dan parameter state
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if (! in_array($state, ['pending', 'berhasil', 'gagal', 'kedaluwarsa', 'dibatalkan', 'error'], true)) {
            abort(404);
        }

        // 2. Pastikan tampilan status selalu mencerminkan kebenaran status pada database
        $order->expireIfPastDeadline();
        $order->refresh();

        $resolvedState = in_array($order->payment_status, ['belum_dibayar', 'menunggu_verifikasi'], true) && $state === 'error'
            ? 'error'
            : $this->resolveResultState($order->payment_status);

        if ($resolvedState !== $state) {
            return redirect()->route('payments.result', ['order' => $order, 'state' => $resolvedState]);
        }

        $order->load('latestPayment');
        $storeSetting = StoreSetting::current();

        return view('payments.result', compact('order', 'state', 'storeSetting'));
    }

    /**
     * Endpoint legacy webhook (tidak lagi digunakan karena verifikasi pembayaran QRIS dilakukan manual oleh admin).
     */
    public function callback(Request $request): Response
    {
        return response('Manual QRIS verification mode active', 200);
    }

    /**
     * Memetakan status pembayaran pesanan pada database menjadi nama state URL hasil pembayaran.
     */
    protected function resolveResultState(string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'lunas' => 'berhasil',
            'kedaluwarsa' => 'kedaluwarsa',
            'dibatalkan' => 'dibatalkan',
            'gagal' => 'gagal',
            default => 'pending',
        };
    }
}
