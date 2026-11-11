<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\StoreSetting;
use App\Models\UserNotification;
use App\Notifications\OrderLifecycleNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

class PaymentController extends Controller
{
    /**
     * Menampilkan halaman pembayaran Midtrans Snap resmi untuk pesanan yang menunggu pembayaran.
     */
    public function show(Order $order): View|RedirectResponse
    {
        // 1. Pastikan pesanan ini adalah milik pelanggan yang sedang login
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // 2. Periksa batas waktu pembayaran dan muat relasi transaksi
        $order->expireIfPastDeadline();
        $order->refresh();
        $order->load(['items', 'pickupLocation', 'latestPayment']);
        $payment = $order->latestPayment;

        // 3. Sinkronkan jika status belum lunas atau arahkan ke halaman hasil bila sudah final
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

        // 4. Jika snap_token belum ada (misalnya pesanan bawaan/retry), minta Snap Token resmi ke Midtrans
        $snapError = null;
        if ($payment && empty($payment->snap_token) && $order->order_status === 'menunggu_pembayaran') {
            $snapResult = $this->requestMidtransSnapToken($order, $payment);
            if (! $snapResult['ok']) {
                $snapError = $snapResult['message'];
            } else {
                $payment->refresh();
            }
        }

        $storeSetting = StoreSetting::current();
        $midtransClientKey = (string) config('services.midtrans.client_key');
        $isProduction = (bool) config('services.midtrans.is_production');

        return view('payments.show', compact(
            'order',
            'payment',
            'storeSetting',
            'midtransClientKey',
            'isProduction',
            'snapError'
        ));
    }

    /**
     * Memeriksa pembaruan status transaksi secara langsung ke server API Midtrans.
     */
    public function checkStatus(Order $order): RedirectResponse
    {
        // 1. Validasi kepemilikan pesanan
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // 2. Lakukan sinkronisasi server-to-server ke endpoint Status Midtrans
        $syncInfo = $this->syncStatusFromMidtrans($order);
        $order->expireIfPastDeadline();
        $order->refresh();

        $state = $this->resolveResultState($order->payment_status);

        if ($state === 'pending' && $syncInfo === 'not_started') {
            return redirect()->route('payments.show', $order)
                ->with('error', 'Transaksi belum dipilih di halaman pembayaran Midtrans. Klik tombol Bayar via Midtrans Snap untuk memilih metode.');
        }

        return redirect()->route('payments.result', ['order' => $order, 'state' => $state]);
    }

    /**
     * Menampilkan tampilan status pembayaran tervalidasi dari database (bukan sekadar parameter URL browser).
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

        // 2. Sinkronkan status asli ke Midtrans API jika status di DB masih menunggu pembayaran
        $order->expireIfPastDeadline();
        $order->refresh();

        if ($order->payment_status === 'belum_dibayar' && $state !== 'error') {
            $this->syncStatusFromMidtrans($order);
            $order->refresh();
        }

        // 3. Pastikan tampilan status selalu mencerminkan kebenaran status pada database
        $resolvedState = $order->payment_status === 'belum_dibayar' && $state === 'error'
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
     * Menerima notifikasi webhook server-to-server resmi dari Midtrans secara idempoten dan terverifikasi.
     */
    public function callback(Request $request): Response
    {
        // 1. Pastikan Server Key tersedia dan seluruh parameter wajib webhook lengkap
        $serverKey = trim((string) config('services.midtrans.server_key'));
        if ($serverKey === '') {
            Log::error('Webhook Midtrans diterima tetapi MIDTRANS_SERVER_KEY belum dikonfigurasi.');

            return response('Server key not configured', 500);
        }

        $payload = $request->all();
        $midtransOrderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if ($midtransOrderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            return response('Invalid notification payload', 400);
        }

        // 2. Verifikasi signature SHA-512 resmi Midtrans (order_id + status_code + gross_amount + ServerKey)
        $expectedSignature = hash('sha512', $midtransOrderId.$statusCode.$grossAmount.$serverKey);
        if (! hash_equals($expectedSignature, $signatureKey)) {
            Log::warning('Signature webhook Midtrans tidak valid.', [
                'midtrans_order_id' => $midtransOrderId,
                'status_code' => $statusCode,
            ]);

            return response('Invalid signature', 403);
        }

        // 3. Kunci baris payment & order di dalam transaksi DB untuk validasi nominal dan idempotency
        $emailPayload = null;
        $statusHttp = DB::transaction(function () use ($midtransOrderId, $grossAmount, $payload, &$emailPayload): int {
            $payment = Payment::query()
                ->where('midtrans_order_id', $midtransOrderId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return 404;
            }

            $order = Order::query()
                ->lockForUpdate()
                ->find($payment->order_id);

            if (! $order) {
                return 404;
            }

            // Validasi nominal gross_amount terhadap tagihan resmi pesanan pada database
            $reportedAmount = (int) round((float) $grossAmount);
            if ($reportedAmount !== (int) $payment->amount || (int) $payment->amount !== (int) $order->total_amount) {
                Log::warning('Nominal webhook Midtrans tidak cocok dengan total pesanan.', [
                    'order_number' => $order->order_number,
                    'midtrans_order_id' => $midtransOrderId,
                    'reported_amount' => $reportedAmount,
                    'expected_amount' => (int) $order->total_amount,
                ]);

                return 422;
            }

            $emailPayload = $this->applyMidtransStatus($order, $payment, $payload);

            return 200;
        });

        if ($statusHttp === 404) {
            return response('Order not found', 404);
        }

        if ($statusHttp === 422) {
            return response('Amount mismatch', 422);
        }

        if ($emailPayload) {
            $this->dispatchLifecycleEmail($emailPayload);
        }

        return response('OK', 200);
    }

    /**
     * Meminta Snap Token baru ke Midtrans Snap API untuk pesanan menunggu pembayaran.
     *
     * @return array{ok: bool, message: ?string}
     */
    protected function requestMidtransSnapToken(Order $order, Payment $payment): array
    {
        $serverKey = trim((string) config('services.midtrans.server_key'));
        if ($serverKey === '') {
            return ['ok' => false, 'message' => 'Kunci server Midtrans belum dikonfigurasi pada sistem.'];
        }

        $storeSetting = StoreSetting::current();
        $remainingMinutes = $order->payment_deadline_at
            ? max(1, (int) ceil(now()->diffInSeconds($order->payment_deadline_at, false) / 60))
            : (int) $storeSetting->payment_timeout_minutes;

        $midtransOrderId = $payment->midtrans_order_id;
        if (! $midtransOrderId || ! str_contains($midtransOrderId, '-')) {
            $midtransOrderId = $order->order_number.'-'.now()->format('His');
        }

        $order->loadMissing('items');
        $itemDetails = [];
        foreach ($order->items as $orderItem) {
            $itemDetails[] = [
                'id' => $orderItem->product_variant_id
                    ? 'PRD-'.$orderItem->product_id.'-V'.$orderItem->product_variant_id
                    : 'PRD-'.$orderItem->product_id,
                'price' => (int) $orderItem->unit_price,
                'quantity' => (int) $orderItem->quantity,
                'name' => mb_substr($orderItem->product_name.($orderItem->variant_name ? ' - '.$orderItem->variant_name : ''), 0, 50),
            ];
        }
        if ((int) $order->shipping_fee > 0) {
            $itemDetails[] = [
                'id' => 'SHIPPING',
                'price' => (int) $order->shipping_fee,
                'quantity' => 1,
                'name' => 'Ongkos Kirim',
            ];
        }

        $enabledPaymentsMap = [
            'qris' => ['other_qris', 'gopay'],
            'credit_card' => ['credit_card'],
            'mandiri_va' => ['echannel'],
            'bca_va' => ['bca_va'],
            'gopay' => ['gopay'],
        ];

        $snapBaseUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $snapPayload = [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => (int) $order->total_amount,
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->customer_email ?: Auth::user()?->email,
                'phone' => $order->customer_phone,
            ],
            'credit_card' => [
                'secure' => (bool) config('services.midtrans.is_3ds', true),
            ],
            'expiry' => [
                'unit' => 'minutes',
                'duration' => $remainingMinutes,
            ],
            'callbacks' => [
                'finish' => route('payments.result', ['order' => $order, 'state' => 'pending']),
            ],
        ];

        if (! empty($itemDetails)) {
            $snapPayload['item_details'] = $itemDetails;
        }

        if (isset($enabledPaymentsMap[$payment->payment_method])) {
            $snapPayload['enabled_payments'] = $enabledPaymentsMap[$payment->payment_method];
        }

        try {
            $response = Http::timeout(15)
                ->withBasicAuth($serverKey, '')
                ->acceptJson()
                ->post($snapBaseUrl, $snapPayload);

            $json = $response->json();

            // Jika order_id sudah pernah dipakai sebelumnya di Midtrans, buat suffix baru yang unik dan coba ulang
            if ($response->status() === 400 && is_array($json) && str_contains(json_encode($json) ?: '', 'order_id')) {
                $midtransOrderId = $order->order_number.'-'.now()->format('His').'-'.random_int(10, 99);
                $snapPayload['transaction_details']['order_id'] = $midtransOrderId;
                $response = Http::timeout(15)
                    ->withBasicAuth($serverKey, '')
                    ->acceptJson()
                    ->post($snapBaseUrl, $snapPayload);
                $json = $response->json();
            }

            if ($response->successful() && ! empty($json['token'])) {
                $payment->update([
                    'midtrans_order_id' => $midtransOrderId,
                    'snap_token' => $json['token'],
                    'snap_redirect_url' => $json['redirect_url'] ?? null,
                    'raw_response' => $json,
                ]);

                return ['ok' => true, 'message' => null];
            }

            Log::warning('Gagal memuat Snap Token Midtrans di halaman pembayaran.', [
                'order_number' => $order->order_number,
                'http_status' => $response->status(),
                'error_messages' => $json['error_messages'] ?? null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Koneksi ke Midtrans Snap bermasalah: '.$exception->getMessage());
        }

        return ['ok' => false, 'message' => 'Tidak dapat memuat sesi pembayaran Midtrans saat ini. Klik Cek Status atau Muat Ulang halaman.'];
    }

    /**
     * Mengambil status transaksi terkini secara server-to-server dari API Midtrans v2.
     */
    protected function syncStatusFromMidtrans(Order $order): string
    {
        $order->loadMissing('latestPayment');
        $payment = $order->latestPayment;
        $serverKey = trim((string) config('services.midtrans.server_key'));

        if ($serverKey === '' || ! $payment || ! $payment->midtrans_order_id) {
            return 'skipped';
        }

        $statusUrl = config('services.midtrans.is_production')
            ? 'https://api.midtrans.com/v2/'.$payment->midtrans_order_id.'/status'
            : 'https://api.sandbox.midtrans.com/v2/'.$payment->midtrans_order_id.'/status';

        try {
            $response = Http::timeout(12)
                ->withBasicAuth($serverKey, '')
                ->acceptJson()
                ->get($statusUrl);

            if ($response->status() === 404) {
                return 'not_started';
            }

            if ($response->successful()) {
                $payload = $response->json();
                $emailPayload = DB::transaction(function () use ($order, $payment, $payload): ?array {
                    $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
                    $lockedPayment = Payment::query()->lockForUpdate()->find($payment->id);

                    if (! $lockedOrder || ! $lockedPayment) {
                        return null;
                    }

                    // Verifikasi nominal jika tersedia di respons status
                    if (isset($payload['gross_amount'])) {
                        $reportedAmount = (int) round((float) $payload['gross_amount']);
                        if ($reportedAmount !== (int) $lockedOrder->total_amount) {
                            return null;
                        }
                    }

                    return $this->applyMidtransStatus($lockedOrder, $lockedPayment, $payload);
                });

                if ($emailPayload) {
                    $this->dispatchLifecycleEmail($emailPayload);
                }

                return 'synced';
            }
        } catch (Throwable $exception) {
            Log::warning('Gagal sinkronisasi status Midtrans untuk '.$order->order_number.': '.$exception->getMessage());
        }

        return 'error';
    }

    /**
     * Memetakan status transaksi dari respons API atau webhook Midtrans secara idempoten ke database lokal.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected function applyMidtransStatus(Order $order, Payment $payment, array $payload): ?array
    {
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? 'accept');
        $transactionId = (string) ($payload['transaction_id'] ?? '');
        $channelMeta = $this->extractMidtransChannelMetadata($payload);

        // Lindungi pesanan yang sudah lunas agar notifikasi lambat atau duplikat tidak mengubah status
        if ($order->payment_status === 'lunas') {
            if (empty($payment->transaction_id) && $transactionId !== '') {
                $payment->update(array_merge([
                    'transaction_id' => $transactionId,
                    'raw_response' => $payload,
                ], $channelMeta));
            }

            return null;
        }

        if (
            ($transactionStatus === 'capture' && $fraudStatus === 'accept')
            || $transactionStatus === 'settlement'
        ) {
            return $this->markOrderPaid($order, $payment, $transactionId, $payload, $channelMeta);
        }

        if ($transactionStatus === 'capture' && $fraudStatus === 'challenge') {
            $payment->update(array_merge([
                'status' => 'challenge',
                'transaction_id' => $transactionId ?: $payment->transaction_id,
                'raw_response' => $payload,
            ], $channelMeta));

            $order->logs()->create([
                'actor_name' => 'Midtrans',
                'event_type' => 'payment_challenge',
                'title' => 'Pembayaran Memerlukan Verifikasi (FDS Challenge)',
                'description' => 'Transaksi kartu sedang ditinjau sistem keamanan Midtrans.',
            ]);

            return null;
        }

        if ($transactionStatus === 'pending') {
            $payment->update(array_merge([
                'status' => 'pending',
                'transaction_id' => $transactionId ?: $payment->transaction_id,
                'raw_response' => $payload,
            ], $channelMeta));

            return null;
        }

        if ($transactionStatus === 'expire' && $order->payment_status === 'belum_dibayar') {
            $setting = StoreSetting::current();
            $expiredOrderStatus = $setting->auto_cancel_status === 'dibatalkan' ? 'dibatalkan' : 'kedaluwarsa';

            $order->update([
                'order_status' => $expiredOrderStatus,
                'payment_status' => 'kedaluwarsa',
                'cancelled_at' => now(),
                'cancelled_by' => 'system',
                'cancellation_reason' => 'Batas pembayaran Midtrans berakhir.',
            ]);

            $payment->update(array_merge([
                'status' => 'expire',
                'transaction_id' => $transactionId ?: $payment->transaction_id,
                'raw_response' => $payload,
            ], $channelMeta));

            $order->logs()->create([
                'actor_name' => 'Midtrans',
                'event_type' => 'status_changed',
                'title' => 'Pembayaran Kedaluwarsa',
                'description' => 'Midtrans melaporkan masa berlaku transaksi telah habis.',
            ]);

            if ($order->user_id) {
                UserNotification::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'audience' => 'customer',
                    'category' => 'pesanan',
                    'channel' => 'dashboard',
                    'title' => 'Pembayaran Kedaluwarsa',
                    'message' => 'Pembayaran Midtrans untuk pesanan '.$order->order_number.' telah kedaluwarsa.',
                    'delivery_status' => 'terkirim',
                    'action_url' => route('orders.show', $order->order_number),
                ]);
            }

            return [
                'order' => $order,
                'subject' => 'Pembayaran '.$order->order_number.' Kedaluwarsa — Brownies Kelana',
                'headline' => 'Sesi pembayaran Midtrans untuk pesanan Anda telah kedaluwarsa.',
                'body' => 'Tidak ada transaksi lunas yang tercatat hingga batas waktu pembayaran berakhir.',
                'actionLabel' => 'Lihat Detail Pesanan',
                'actionUrl' => route('orders.show', $order->order_number),
            ];
        }

        if (in_array($transactionStatus, ['cancel', 'deny'], true) && $order->payment_status === 'belum_dibayar') {
            $isCancelled = $transactionStatus === 'cancel';
            $order->update([
                'payment_status' => $isCancelled ? 'dibatalkan' : 'gagal',
                'order_status' => $isCancelled ? 'dibatalkan' : $order->order_status,
                'cancelled_at' => $isCancelled ? now() : $order->cancelled_at,
                'cancelled_by' => $isCancelled ? 'system' : $order->cancelled_by,
                'cancellation_reason' => $isCancelled ? 'Transaksi pembayaran dibatalkan di Midtrans.' : $order->cancellation_reason,
            ]);

            $payment->update(array_merge([
                'status' => $transactionStatus,
                'transaction_id' => $transactionId ?: $payment->transaction_id,
                'raw_response' => $payload,
            ], $channelMeta));

            $order->logs()->create([
                'actor_name' => 'Midtrans',
                'event_type' => $isCancelled ? 'cancelled' : 'payment_failed',
                'title' => $isCancelled ? 'Pembayaran Dibatalkan (Midtrans)' : 'Pembayaran Ditolak / Gagal (Midtrans)',
                'description' => 'Status Midtrans: '.$transactionStatus.'.',
            ]);

            return [
                'order' => $order,
                'subject' => 'Pembayaran '.$order->order_number.' '.($isCancelled ? 'Dibatalkan' : 'Gagal').' — Brownies Kelana',
                'headline' => $isCancelled ? 'Transaksi pembayaran pesanan Anda dibatalkan.' : 'Pembayaran Midtrans untuk pesanan Anda gagal diproses.',
                'body' => $isCancelled
                    ? 'Pesanan ditutup sesuai status pembatalan transaksi dari Midtrans.'
                    : 'Silakan ulangi proses pembayaran menggunakan metode Midtrans lain sebelum batas waktu berakhir.',
                'actionLabel' => $isCancelled ? 'Lihat Detail Pesanan' : 'Coba Bayar Lagi',
                'actionUrl' => $isCancelled ? route('orders.show', $order->order_number) : route('payments.show', $order),
            ];
        }

        return null;
    }

    /**
     * Menandai pesanan lunas secara atomik dan menyiapkan notifikasi pelanggan serta admin.
     *
     * @param  array<string, mixed>  $rawPayload
     * @param  array<string, mixed>  $channelMeta
     * @return array<string, mixed>|null
     */
    protected function markOrderPaid(
        Order $order,
        ?Payment $payment,
        string $transactionId,
        array $rawPayload,
        array $channelMeta = []
    ): ?array {
        if ($order->payment_status === 'lunas') {
            return null;
        }

        $order->update([
            'payment_status' => 'lunas',
            'order_status' => in_array($order->order_status, ['menunggu_pembayaran', 'kedaluwarsa', 'dibatalkan'], true)
                ? 'diproses'
                : $order->order_status,
            'paid_at' => now(),
        ]);

        if ($payment) {
            $payment->update(array_merge([
                'status' => 'settlement',
                'transaction_id' => $transactionId ?: $payment->transaction_id,
                'paid_at' => now(),
                'raw_response' => $rawPayload,
            ], $channelMeta));
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

        return [
            'order' => $order,
            'subject' => 'Pembayaran '.$order->order_number.' Lunas — Brownies Kelana',
            'headline' => 'Pembayaran Anda melalui Midtrans telah kami terima!',
            'body' => 'Pesanan Anda kini mulai kami proses sesuai jadwal yang dipilih.',
            'actionLabel' => 'Pantau Status Pesanan',
            'actionUrl' => route('orders.show', $order->order_number),
        ];
    }

    /**
     * Mengekstrak rincian kanal resmi (bank VA, bill key, atau URL QR) dari respons asli Midtrans.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function extractMidtransChannelMetadata(array $payload): array
    {
        $meta = [];

        if (! empty($payload['va_numbers'][0]) && is_array($payload['va_numbers'][0])) {
            $meta['va_bank'] = $payload['va_numbers'][0]['bank'] ?? null;
            $meta['va_number'] = $payload['va_numbers'][0]['va_number'] ?? null;
        } elseif (! empty($payload['permata_va_number'])) {
            $meta['va_bank'] = 'permata';
            $meta['va_number'] = (string) $payload['permata_va_number'];
        }

        if (! empty($payload['bill_key'])) {
            $meta['va_bank'] = 'mandiri';
            $meta['bill_key'] = (string) $payload['bill_key'];
        }
        if (! empty($payload['biller_code'])) {
            $meta['biller_code'] = (string) $payload['biller_code'];
        }

        if (! empty($payload['payment_type'])) {
            $typeLabelMap = [
                'bank_transfer' => 'Midtrans · Transfer Bank VA',
                'echannel' => 'Midtrans · Mandiri Bill Payment',
                'gopay' => 'Midtrans · GoPay',
                'qris' => 'Midtrans · QRIS',
                'shopeepay' => 'Midtrans · ShopeePay',
                'credit_card' => 'Midtrans · Kartu Kredit/Debit',
            ];
            $paymentType = (string) $payload['payment_type'];
            if (isset($typeLabelMap[$paymentType])) {
                $meta['channel_label'] = $typeLabelMap[$paymentType];
            }
        }

        if (! empty($payload['actions']) && is_array($payload['actions'])) {
            foreach ($payload['actions'] as $action) {
                if (($action['name'] ?? '') === 'generate-qr-code' && ! empty($action['url'])) {
                    $meta['qris_url'] = (string) $action['url'];
                    break;
                }
            }
        }

        return $meta;
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

    /**
     * Mengirim notifikasi email pembaruan pembayaran secara aman tanpa menggagalkan respons webhook.
     *
     * @param  array<string, mixed>  $emailPayload
     */
    protected function dispatchLifecycleEmail(array $emailPayload): void
    {
        $order = $emailPayload['order'];
        if (empty($order->customer_email)) {
            return;
        }

        try {
            Notification::route('mail', $order->customer_email)->notify(
                new OrderLifecycleNotification(
                    $order,
                    $emailPayload['subject'],
                    $emailPayload['headline'],
                    $emailPayload['body'],
                    $emailPayload['actionLabel'],
                    $emailPayload['actionUrl']
                )
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal mengirim email status pembayaran '.$order->order_number.': '.$exception->getMessage());
        }
    }
}
