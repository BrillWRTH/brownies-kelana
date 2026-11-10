<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
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
     * Memastikan checkout hanya menerima kanal Midtrans resmi, menolak COD, dan dapat menyelesaikan simulasi lunas.
     */
    public function test_full_midtrans_checkout_rejects_cod_and_settles_on_simulation(): void
    {
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

        // Checkout resmi via Midtrans Mandiri VA berhasil membuat pesanan dan mengosongkan keranjang
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

        $this->assertSame('menunggu_pembayaran', $order->order_status);
        $this->assertSame('belum_dibayar', $order->payment_status);
        $this->assertSame('mandiri', $order->latestPayment->va_bank);

        // Halaman instruksi pembayaran menampilkan kode Mandiri Bill Payment & hitung mundur
        $this->get(route('payments.show', $order))
            ->assertOk()
            ->assertSee('70012')
            ->assertSee($order->latestPayment->bill_key);

        // Simulasi Midtrans berhasil melunaskan pesanan & memajukan status ke diproses
        $this->post(route('payments.simulate', $order), ['outcome' => 'berhasil'])
            ->assertRedirect(route('payments.result', ['order' => $order, 'state' => 'berhasil']));

        $order->refresh();
        $this->assertSame('lunas', $order->payment_status);
        $this->assertSame('diproses', $order->order_status);
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
     * Memastikan webhook resmi Midtrans memverifikasi signature SHA-512 dan memproses pelunasan.
     */
    public function test_midtrans_webhook_callback_verifies_sha512_signature_and_settles_payment(): void
    {
        Config::set('services.midtrans.server_key', 'SB-Mid-server-TESTKEY');

        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $order = Order::query()
            ->where('user_id', $customer->id)
            ->where('payment_status', 'belum_dibayar')
            ->with('latestPayment')
            ->firstOrFail();

        $midtransOrderId = $order->latestPayment->midtrans_order_id;
        $statusCode = '200';
        $grossAmount = (string) $order->total_amount.'.00';

        // Tolak signature palsu
        $this->postJson(route('midtrans.callback'), [
            'order_id' => $midtransOrderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => 'invalid-signature',
            'transaction_status' => 'settlement',
        ])->assertStatus(403);

        // Terima signature SHA-512 yang valid
        $validSignature = hash('sha512', $midtransOrderId.$statusCode.$grossAmount.'SB-Mid-server-TESTKEY');

        $this->postJson(route('midtrans.callback'), [
            'order_id' => $midtransOrderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'settlement',
            'transaction_id' => 'MT-HOOK-12345',
        ])->assertOk();

        $order->refresh();
        $this->assertSame('lunas', $order->payment_status);
        $this->assertSame('diproses', $order->order_status);
        $this->assertSame('MT-HOOK-12345', $order->latestPayment->transaction_id);
    }

    /**
     * Memastikan pembayaran lewat deadline kedaluwarsa otomatis, aturan batal sebelum diproses, serta ulasan pasca selesai.
     */
    public function test_order_expiry_cancellation_guard_and_completed_review_submission(): void
    {
        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $this->actingAs($customer);

        // 1. Order yang melewati deadline 30 menit otomatis menjadi kedaluwarsa saat dibuka
        $unpaidOrder = Order::query()
            ->where('user_id', $customer->id)
            ->where('order_status', 'menunggu_pembayaran')
            ->firstOrFail();

        $unpaidOrder->update(['payment_deadline_at' => now()->subMinutes(5)]);

        $this->get(route('orders.show', $unpaidOrder->order_number))
            ->assertOk();

        $this->assertSame('kedaluwarsa', $unpaidOrder->fresh()->order_status);

        // 2. Pesanan lain yang masih menunggu bayar dan belum lewat deadline boleh dibatalkan pelanggan
        $cancellableOrder = Order::create([
            'order_number' => 'BC-8801',
            'user_id' => $customer->id,
            'source' => 'website',
            'customer_name' => $customer->name,
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

        // 4. Ulasan hanya dapat dikirim pada pesanan berstatus selesai dan langsung masuk antrean moderasi admin
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
