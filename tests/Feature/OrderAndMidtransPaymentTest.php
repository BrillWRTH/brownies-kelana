<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Product;
use App\Models\Review;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\OrderLifecycleNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderAndMidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Memastikan checkout 100% Midtrans Snap memanggil API resmi, rollback saat API gagal, dan sinkron status server-to-server.
     */
    public function test_full_midtrans_checkout_rejects_cod_calls_snap_api_and_rolls_back_on_api_failure(): void
    {
        Config::set('services.midtrans.server_key', 'SB-Mid-server-TESTKEY');
        Config::set('services.midtrans.client_key', 'SB-Mid-client-TESTKEY');
        Config::set('services.midtrans.is_production', false);

        Notification::fake();

        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $fudgy = Product::query()->where('slug', 'brownies-original')->with('variants')->firstOrFail();
        $storeSetting = StoreSetting::current();
        $openDate = $this->findFirstAvailableDate($storeSetting);

        $this->actingAs($customer)->post(route('cart.items.store'), [
            'product_id' => $fudgy->id,
            'product_variant_id' => $fudgy->variants->first()->id,
            'quantity' => 2,
        ]);

        // Pastikan halaman checkout tidak menyediakan opsi COD
        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertDontSee('Bayar di Tempat (COD)');

        // Tolak jika ada pengiriman payload payment_method selain kanal Midtrans resmi
        $this->post(route('checkout.store'), [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567890',
            'delivery_type' => 'delivery',
            'recipient_name' => $customer->name,
            'recipient_phone' => '081234567890',
            'delivery_address' => 'Jl. Raya Taman Sidoarjo No. 12',
            'scheduled_date' => $openDate,
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'payment_method' => 'cod',
        ])->assertSessionHasErrors('payment_method');

        $initialOrderCount = Order::query()->count();

        // Daftarkan respons berurutan Midtrans: gagal (500) dulu, lalu berhasil (201) + sinkron status (404 lalu 200 settlement)
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::sequence()
                ->push(['error_messages' => ['Gateway maintenance']], 500)
                ->push([
                    'token' => 'snap-token-real-123',
                    'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token-real-123',
                ], 201),
            'https://api.sandbox.midtrans.com/v2/*/status' => Http::sequence()
                ->push(['status_code' => '404'], 404)
                ->push([
                    'status_code' => '200',
                    'transaction_status' => 'settlement',
                    'fraud_status' => 'accept',
                    'transaction_id' => 'MT-REAL-SYNC-999',
                    'payment_type' => 'echannel',
                    'biller_code' => '70012',
                    'bill_key' => '9988776655',
                    'gross_amount' => '255000.00',
                ], 200),
        ]);

        // Jika API Midtrans Snap gagal (HTTP 500), transaksi DB harus rollback & keranjang tetap aman
        $this->post(route('checkout.store'), [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567890',
            'delivery_type' => 'delivery',
            'recipient_name' => $customer->name,
            'recipient_phone' => '081234567890',
            'delivery_address' => 'Jl. Raya Taman Sidoarjo No. 12',
            'delivery_latitude' => -7.351000,
            'delivery_longitude' => 112.715000,
            'scheduled_date' => $openDate,
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'payment_method' => 'mandiri_va',
        ])->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame($initialOrderCount, Order::query()->count());
        $this->assertCount(1, Cart::query()->where('user_id', $customer->id)->with('items')->firstOrFail()->items);

        // Jika API Midtrans Snap berhasil (HTTP 201), token Snap asli disimpan dan halaman pembayaran tidak memakai simulasi lokal
        $checkoutRes = $this->post(route('checkout.store'), [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567890',
            'delivery_type' => 'delivery',
            'recipient_name' => $customer->name,
            'recipient_phone' => '081234567890',
            'delivery_address' => 'Jl. Raya Taman Sidoarjo No. 12',
            'delivery_latitude' => -7.351000,
            'delivery_longitude' => 112.715000,
            'scheduled_date' => $openDate,
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'payment_method' => 'mandiri_va',
        ]);

        $order = Order::query()->latest('id')->firstOrFail();
        $checkoutRes->assertRedirect(route('payments.show', $order));

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/snap/v1/transactions')
                && ($request['enabled_payments'] ?? []) === ['echannel']
                && ($request['transaction_details']['gross_amount'] ?? 0) === 255000;
        });

        Notification::assertSentOnDemand(OrderLifecycleNotification::class);

        $this->assertSame('menunggu_pembayaran', $order->order_status);
        $this->assertSame('belum_dibayar', $order->payment_status);
        $this->assertSame('snap-token-real-123', $order->latestPayment->snap_token);
        $this->assertNull($order->latestPayment->va_number);

        // Halaman pembayaran menampilkan tombol Midtrans Snap asli tanpa simulasi lokal maupun canvas QR palsu
        $this->get(route('payments.show', $order))
            ->assertOk()
            ->assertSee('Bayar Sekarang via Midtrans Snap')
            ->assertDontSee('Mode Simulasi Midtrans Lokal')
            ->assertDontSee('kelana-qris-canvas');

        // Akses manual URL /status/berhasil saat order belum lunas di Midtrans TIDAK melunaskan order
        $this->get(route('payments.result', ['order' => $order, 'state' => 'berhasil']))
            ->assertRedirect(route('payments.result', ['order' => $order, 'state' => 'pending']));
        $this->assertSame('belum_dibayar', $order->fresh()->payment_status);

        // Cek Status Pembayaran menyinkronkan settlement dari server API Midtrans v2 dan melunaskan pesanan
        $this->post(route('payments.check-status', $order))
            ->assertRedirect(route('payments.result', ['order' => $order, 'state' => 'berhasil']));

        $order->refresh();
        $this->assertSame('lunas', $order->payment_status);
        $this->assertSame('diproses', $order->order_status);
        $this->assertSame('MT-REAL-SYNC-999', $order->latestPayment->transaction_id);
        $this->assertSame('9988776655', $order->latestPayment->bill_key);
        $this->assertNotNull($order->paid_at);

        $this->assertTrue(
            UserNotification::query()
                ->where('audience', 'admin')
                ->where('order_id', $order->id)
                ->where('title', 'Pembayaran Lunas (Midtrans)')
                ->exists()
        );
    }

    /**
     * Memastikan webhook resmi Midtrans memverifikasi signature SHA-512, nominal gross_amount, dan idempoten saat duplikat.
     */
    public function test_midtrans_webhook_verifies_sha512_amount_mismatch_and_is_idempotent(): void
    {
        Config::set('services.midtrans.server_key', 'SB-Mid-server-TESTKEY');
        Notification::fake();

        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $order = Order::query()
            ->where('user_id', $customer->id)
            ->where('payment_status', 'belum_dibayar')
            ->with('latestPayment')
            ->firstOrFail();

        $midtransOrderId = $order->latestPayment->midtrans_order_id;
        $statusCode = '200';
        $grossAmount = (string) $order->total_amount.'.00';

        // 1. Tolak signature palsu
        $this->postJson(route('midtrans.callback'), [
            'order_id' => $midtransOrderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => 'invalid-signature',
            'transaction_status' => 'settlement',
        ])->assertStatus(403);

        // 2. Tolak jika gross_amount dari webhook dimanipulasi walau signature valid
        $mismatchAmount = '1000.00';
        $mismatchSignature = hash('sha512', $midtransOrderId.$statusCode.$mismatchAmount.'SB-Mid-server-TESTKEY');

        $this->postJson(route('midtrans.callback'), [
            'order_id' => $midtransOrderId,
            'status_code' => $statusCode,
            'gross_amount' => $mismatchAmount,
            'signature_key' => $mismatchSignature,
            'transaction_status' => 'settlement',
        ])->assertStatus(422);

        $this->assertSame('belum_dibayar', $order->fresh()->payment_status);

        // 3. Terima signature SHA-512 yang valid dan nominal sesuai DB
        $validSignature = hash('sha512', $midtransOrderId.$statusCode.$grossAmount.'SB-Mid-server-TESTKEY');

        $this->postJson(route('midtrans.callback'), [
            'order_id' => $midtransOrderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'settlement',
            'transaction_id' => 'MT-HOOK-12345',
            'payment_type' => 'bank_transfer',
            'va_numbers' => [
                ['bank' => 'bca', 'va_number' => '880001234567'],
            ],
        ])->assertOk();

        $order->refresh();
        $this->assertSame('lunas', $order->payment_status);
        $this->assertSame('diproses', $order->order_status);
        $this->assertSame('MT-HOOK-12345', $order->latestPayment->transaction_id);
        $this->assertSame('880001234567', $order->latestPayment->va_number);

        $logCountAfterSettle = OrderLog::query()->where('order_id', $order->id)->count();

        // 4. Kirim ulang webhook duplikat + webhook expire terlambat => harus idempoten & tidak menurunkan status lunas
        $this->postJson(route('midtrans.callback'), [
            'order_id' => $midtransOrderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'settlement',
            'transaction_id' => 'MT-HOOK-12345',
        ])->assertOk();

        $expireSignature = hash('sha512', $midtransOrderId.'202'.$grossAmount.'SB-Mid-server-TESTKEY');
        $this->postJson(route('midtrans.callback'), [
            'order_id' => $midtransOrderId,
            'status_code' => '202',
            'gross_amount' => $grossAmount,
            'signature_key' => $expireSignature,
            'transaction_status' => 'expire',
        ])->assertOk();

        $order->refresh();
        $this->assertSame('lunas', $order->payment_status);
        $this->assertSame('diproses', $order->order_status);
        $this->assertSame($logCountAfterSettle, OrderLog::query()->where('order_id', $order->id)->count());
    }

    /**
     * Memastikan command kedaluwarsa otomatis, aturan batal sebelum diproses, serta ulasan unik pasca selesai bekerja.
     */
    public function test_order_expiry_command_cancellation_guard_and_completed_review_submission(): void
    {
        Notification::fake();

        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $this->actingAs($customer);

        // 1. Order yang melewati deadline 30 menit ditutup otomatis oleh command scheduler Artisan
        $unpaidOrder = Order::query()
            ->where('user_id', $customer->id)
            ->where('order_status', 'menunggu_pembayaran')
            ->firstOrFail();

        $unpaidOrder->update(['payment_deadline_at' => now()->subMinutes(5)]);

        $this->artisan('orders:expire-unpaid')
            ->assertSuccessful();

        $this->assertSame('kedaluwarsa', $unpaidOrder->fresh()->order_status);
        $this->assertSame('kedaluwarsa', $unpaidOrder->fresh()->payment_status);

        // 2. Pesanan lain yang masih menunggu bayar dan belum lewat deadline boleh dibatalkan pelanggan
        $cancellableOrder = Order::create([
            'order_number' => 'BC-8801',
            'user_id' => $customer->id,
            'source' => 'website',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'delivery_type' => 'pickup',
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'scheduled_date' => now()->addDays(2),
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'subtotal' => 75000,
            'shipping_fee' => 0,
            'total_amount' => 75000,
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'belum_dibayar',
            'payment_deadline_at' => now()->addMinutes(25),
        ]);

        $this->post(route('orders.cancel', $cancellableOrder), [
            'cancellation_reason' => 'Ingin ubah jadwal pengambilan',
            'confirm_cancel' => '1',
        ])->assertRedirect(route('orders.show', $cancellableOrder->order_number));

        $this->assertSame('dibatalkan', $cancellableOrder->fresh()->order_status);

        // 3. Pesanan yang sudah diproses TIDAK dapat dibatalkan sendiri oleh pelanggan
        $processingOrder = Order::query()
            ->where('user_id', $customer->id)
            ->whereIn('order_status', ['diproses', 'dalam_pengiriman', 'sampai_tujuan'])
            ->firstOrFail();

        $this->post(route('orders.cancel', $processingOrder), [
            'cancellation_reason' => 'Coba batalkan',
            'confirm_cancel' => '1',
        ])->assertRedirect()
            ->assertSessionHas('error');

        // 4. Ulasan hanya dapat dikirim 1x pada pesanan berstatus selesai dan langsung masuk antrean moderasi admin
        $completedOrder = Order::create([
            'order_number' => 'BC-8802',
            'user_id' => $customer->id,
            'source' => 'website',
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'delivery_type' => 'pickup',
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'scheduled_date' => now()->subDay(),
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'subtotal' => 75000,
            'shipping_fee' => 0,
            'total_amount' => 75000,
            'order_status' => 'sudah_diambil',
            'payment_status' => 'lunas',
            'paid_at' => now()->subDays(2),
            'delivered_at' => now()->subDay(),
        ]);

        $fudgy = Product::query()->firstOrFail();
        $completedOrder->items()->create([
            'product_id' => $fudgy->id,
            'product_name' => $fudgy->name,
            'variant_name' => 'Reguler',
            'unit_price' => 75000,
            'quantity' => 1,
            'subtotal' => 75000,
        ]);

        $this->post(route('orders.reviews.store', $completedOrder), [
            'rating' => 5,
            'comment' => 'Brownies fudgy sangat lembut dan cokelatnya terasa premium!',
        ])->assertRedirect(route('orders.show', $completedOrder->order_number));

        $review = Review::query()->where('order_id', $completedOrder->id)->firstOrFail();
        $this->assertSame(5, $review->rating);
        $this->assertSame('menunggu_persetujuan', $review->moderation_status);

        // Pengiriman ulasan kedua untuk pesanan yang sama harus ditolak
        $this->post(route('orders.reviews.store', $completedOrder), [
            'rating' => 4,
            'comment' => 'Coba ulas lagi',
        ])->assertRedirect()
            ->assertSessionHas('error');

        // 5. Pastikan halaman dashboard, notifikasi, profil, dan keranjang pelanggan tampil bersih (HTTP 200)
        $this->get(route('dashboard'))->assertOk()->assertSee($customer->name);
        $this->get(route('notifications.index'))->assertOk();
        $this->get(route('profile.show'))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
        $this->get(route('cart.index'))->assertOk();
        $this->get(route('orders.show', 'BC-TIDAK-ADA'))->assertOk();
    }

    /**
     * Mencari tanggal pertama yang valid dan terbuka untuk pre-order H-1.
     */
    protected function findFirstAvailableDate(StoreSetting $storeSetting): string
    {
        for ($offset = 1; $offset <= 30; $offset++) {
            $candidate = Carbon::today()->addDays($offset)->toDateString();
            $check = $storeSetting->checkScheduleDate($candidate);
            if ($check['available']) {
                return $candidate;
            }
        }

        return Carbon::today()->addDay()->toDateString();
    }
}
