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
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Menampilkan halaman instruksi pembayaran Midtrans / popup Snap untuk pesanan.
     */
    public function show(Order $order): View|RedirectResponse
    {
        // 1. Pastikan pesanan ini adalah milik pelanggan yang sedang login
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // 2. Periksa apakah batas waktu 30 menit telah berakhir
        $order->expireIfPastDeadline();
        $order->load(['items', 'pickupLocation', 'latestPayment']);
        $payment = $order->latestPayment;

        // 3. Jika status bukan menunggu pembayaran, arahkan ke modal status hasil pembayaran
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

        $storeSetting = StoreSetting::current();
        $midtransClientKey = config('services.midtrans.client_key');
        $isProduction = (bool) config('services.midtrans.is_production');

        // 4. Tampilkan tampilan instruksi sesuai metode yang dipilih (Mandiri VA, BCA VA, QRIS, atau Snap)
        return view('payments.show', compact(
            'order',
            'payment',
            'storeSetting',
            'midtransClientKey',
            'isProduction'
        ));
    }

    /**
     * Memeriksa pembaruan status transaksi ke server Midtrans atau status batas waktu lokal.
     */
    public function checkStatus(Order $order): RedirectResponse
    {
        // 1. Validasi kepemilikan pesanan
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load('latestPayment');
        $payment = $order->latestPayment;

        // 2. Jika key Midtrans tersedia, sinkronkan status terbaru dari API Midtrans
        $serverKey = config('services.midtrans.server_key');
        if (! empty($serverKey) && $payment && $payment->midtrans_order_id) {
            $baseUrl = config('services.midtrans.is_production')
                ? 'https://api.midtrans.com/v2/'.$payment->midtrans_order_id.'/status'
                : 'https://api.sandbox.midtrans.com/v2/'.$payment->midtrans_order_id.'/status';

            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->get($baseUrl);

            if ($response->successful()) {
                $payload = $response->json();
                $this->applyMidtransStatus($order, $payment, $payload);
            }
        } else {
            // Cek apakah timer kedaluwarsa sudah terlewati
            $order->expireIfPastDeadline();
        }

        $order->refresh();

        $stateMap = [
            'lunas' => 'berhasil',
            'kedaluwarsa' => 'kedaluwarsa',
            'dibatalkan' => 'dibatalkan',
            'gagal' => 'gagal',
        ];

        $state = $stateMap[$order->payment_status] ?? 'pending';

        return redirect()->route('payments.result', ['order' => $order, 'state' => $state]);
    }

    /**
     * Simulasi pembayaran Midtrans pada mode lokal / demo ketika API key Sandbox belum diatur.
     */
    public function simulate(Request $request, Order $order): RedirectResponse
    {
        // 1. Validasi kepemilikan pesanan dan keamanan mode demo lokal
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if (! app()->environment('local', 'testing') && ! empty(config('services.midtrans.server_key'))) {
            abort(403, 'Simulasi pembayaran hanya tersedia pada mode lokal / demo.');
        }

        // 2. Validasi jenis skenario status pembayaran yang dipilih
        $validated = $request->validate([
            'outcome' => ['required', 'in:berhasil,pending,gagal,kedaluwarsa,dibatalkan,error'],
        ]);

        $order->load('latestPayment');
        $payment = $order->latestPayment;
        $outcome = $validated['outcome'];

        // 3. Terapkan pembaruan status sesuai hasil simulasi Midtrans
        if ($outcome === 'berhasil') {
            $this->markOrderPaid($order, $payment, 'MT-SIM-'.now()->format('His'), [
                'transaction_status' => 'settlement',
                'payment_type' => $payment?->payment_method ?? 'midtrans_snap',
                'simulated' => true,
            ]);
        } elseif ($outcome === 'gagal') {
            $order->update(['payment_status' => 'gagal']);
            if ($payment) {
                $payment->update(['status' => 'deny']);
            }
            $order->logs()->create([
                'user_id' => Auth::id(),
                'actor_name' => 'Midtrans',
                'event_type' => 'payment_failed',
                'title' => 'Pembayaran Ditolak / Gagal',
                'description' => 'Transaksi pembayaran belum berhasil diproses oleh penyedia pembayaran.',
            ]);
        } elseif ($outcome === 'kedaluwarsa') {
            $setting = StoreSetting::current();
            $order->update([
                'order_status' => $setting->auto_cancel_status === 'dibatalkan' ? 'dibatalkan' : 'kedaluwarsa',
                'payment_status' => 'kedaluwarsa',
                'cancelled_at' => now(),
                'cancelled_by' => 'system',
                'cancellation_reason' => 'Sesi pembayaran kedaluwarsa.',
            ]);
            if ($payment) {
                $payment->update(['status' => 'expire']);
            }
        } elseif ($outcome === 'dibatalkan') {
            $order->update([
                'order_status' => 'dibatalkan',
                'payment_status' => 'dibatalkan',
                'cancelled_at' => now(),
                'cancelled_by' => 'customer',
                'cancellation_reason' => 'Pembayaran dibatalkan oleh pelanggan.',
            ]);
            if ($payment) {
                $payment->update(['status' => 'cancel']);
            }
        } elseif ($outcome === 'error') {
            $order->logs()->create([
                'user_id' => Auth::id(),
                'actor_name' => 'Midtrans',
                'event_type' => 'payment_error',
                'title' => 'Gangguan Pemrosesan Pembayaran',
                'description' => 'Terjadi gangguan sementara pada gateway pembayaran.',
            ]);
        }

        return redirect()->route('payments.result', ['order' => $order, 'state' => $outcome]);
    }

    /**
     * Menampilkan tampilan status pembayaran (Berhasil, Pending, Kedaluwarsa, Dibatalkan, Error, Gagal).
     */
    public function result(Order $order, string $state): View
    {
        // 1. Validasi kepemilikan pesanan dan state status
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if (! in_array($state, ['pending', 'berhasil', 'gagal', 'kedaluwarsa', 'dibatalkan', 'error'], true)) {
            abort(404);
        }

        $order->load('latestPayment');
        $storeSetting = StoreSetting::current();

        return view('payments.result', compact('order', 'state', 'storeSetting'));
    }

    /**
     * Menerima notifikasi webhook server-to-server resmi dari Midtrans.
     */
    public function callback(Request $request): Response
    {
        // 1. Ambil payload callback dan verifikasi tanda tangan SHA-512 Midtrans
        $payload = $request->all();
        $serverKey = (string) config('services.midtrans.server_key');
        $midtransOrderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if ($serverKey !== '') {
            $expectedSignature = hash('sha512', $midtransOrderId.$statusCode.$grossAmount.$serverKey);
            if (! hash_equals($expectedSignature, $signatureKey)) {
                return response('Invalid signature', 403);
            }
        }

        // 2. Temukan transaksi pembayaran dan pesanan yang sesuai
        $payment = Payment::query()
            ->with('order')
            ->where('midtrans_order_id', $midtransOrderId)
            ->first();

        if (! $payment || ! $payment->order) {
            return response('Order not found', 404);
        }

        // 3. Perbarui status pembayaran dan status pesanan sesuai notifikasi Midtrans
        $this->applyMidtransStatus($payment->order, $payment, $payload);

        return response('OK', 200);
    }

    /**
     * Memetakan status transaksi dari respons API atau webhook Midtrans ke database lokal.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function applyMidtransStatus(Order $order, Payment $payment, array $payload): void
    {
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? 'accept');
        $transactionId = (string) ($payload['transaction_id'] ?? '');

        if ($transactionStatus === 'capture' && $fraudStatus === 'accept') {
            $this->markOrderPaid($order, $payment, $transactionId, $payload);
        } elseif ($transactionStatus === 'settlement') {
            $this->markOrderPaid($order, $payment, $transactionId, $payload);
        } elseif ($transactionStatus === 'expire') {
            $setting = StoreSetting::current();
            $order->update([
                'order_status' => $setting->auto_cancel_status === 'dibatalkan' ? 'dibatalkan' : 'kedaluwarsa',
                'payment_status' => 'kedaluwarsa',
                'cancelled_at' => now(),
                'cancelled_by' => 'system',
                'cancellation_reason' => 'Batas pembayaran Midtrans berakhir.',
            ]);
            $payment->update(['status' => 'expire', 'raw_response' => $payload]);
        } elseif (in_array($transactionStatus, ['cancel', 'deny'], true)) {
            $order->update([
                'payment_status' => $transactionStatus === 'cancel' ? 'dibatalkan' : 'gagal',
                'order_status' => $transactionStatus === 'cancel' ? 'dibatalkan' : $order->order_status,
                'cancelled_at' => $transactionStatus === 'cancel' ? now() : null,
            ]);
            $payment->update(['status' => $transactionStatus, 'raw_response' => $payload]);
        }
    }

    /**
     * Menandai pesanan lunas dan mulai diproses serta mengirim notifikasi pelanggan & admin.
     *
     * @param  array<string, mixed>  $rawPayload
     */
    protected function markOrderPaid(Order $order, ?Payment $payment, string $transactionId, array $rawPayload): void
    {
        if ($order->payment_status === 'lunas') {
            return;
        }

        $order->update([
            'payment_status' => 'lunas',
            'order_status' => $order->order_status === 'menunggu_pembayaran' ? 'diproses' : $order->order_status,
            'paid_at' => now(),
        ]);

        if ($payment) {
            $payment->update([
                'status' => 'settlement',
                'transaction_id' => $transactionId ?: $payment->transaction_id,
                'paid_at' => now(),
                'raw_response' => $rawPayload,
            ]);
        }

        $order->logs()->create([
            'actor_name' => 'Midtrans',
            'event_type' => 'payment_settled',
            'title' => 'Pembayaran Berhasil · Lunas',
            'description' => 'Pembayaran dikonfirmasi otomatis oleh Midtrans. Pesanan mulai disiapkan.',
        ]);

        if ($order->user_id) {
            UserNotification::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'audience' => 'customer',
                'category' => 'pesanan',
                'channel' => 'dashboard',
                'title' => 'Pembayaran Berhasil',
                'message' => 'Pembayaran pesanan '.$order->order_number.' sebesar Rp '.number_format($order->total_amount, 0, ',', '.').' telah dikonfirmasi lunas.',
                'delivery_status' => 'terkirim',
                'action_url' => route('orders.show', $order->order_number),
            ]);
        }

        UserNotification::create([
            'user_id' => null,
            'order_id' => $order->id,
            'audience' => 'admin',
            'category' => 'pesanan',
            'channel' => 'dashboard',
            'title' => 'Pembayaran Lunas (Midtrans)',
            'message' => $order->order_number.' · '.$order->customer_name.' telah melunasi Rp '.number_format($order->total_amount, 0, ',', '.').'. Siap diproses.',
            'delivery_status' => 'terkirim',
            'action_url' => route('admin.orders.show', $order),
        ]);
    }
}
