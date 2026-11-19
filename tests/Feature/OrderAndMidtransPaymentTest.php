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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
     * Memastikan checkout hanya menggunakan QRIS Brownies Kelana, menampilkan QRIS + tombol WhatsApp penjual, serta lampirkan bukti bayar.
     */
    public function test_qris_checkout_upload_proof_and_admin_manual_verification_flow(): void
    {
        Storage::fake('public');
        Notification::fake();

        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $fudgy = Product::query()->where('slug', 'brownies-original')->with('variants')->firstOrFail();
        $storeSetting = StoreSetting::current();
        $openDate = $this->findFirstAvailableDate($storeSetting);

        $this->actingAs($customer)->post(route('cart.items.store'), [
            'product_id' => $fudgy->id,
            'product_variant_id' => $fudgy->variants->first()->id,
            'quantity' => 2,
        ]);

        // 1. Pastikan halaman checkout menampilkan QRIS Brownies Kelana dan menolak kanal lain (COD / Midtrans VA)
        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('QRIS Brownies Kelana')
            ->assertSee('ID1025429266147')
            ->assertDontSee('Midtrans Snap')
            ->assertDontSee('Bayar di Tempat (COD)');

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
        ])->assertSessionHasErrors('payment_method');

        // 2. Checkout dengan QRIS berhasil membuat pesanan tanpa memanggil Midtrans API
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
            'payment_method' => 'qris',
        ]);

        $order = Order::query()->latest('id')->firstOrFail();
        $checkoutRes->assertRedirect(route('payments.show', $order));

        Notification::assertSentOnDemand(OrderLifecycleNotification::class);

        $this->assertSame('menunggu_pembayaran', $order->order_status);
        $this->assertSame('belum_dibayar', $order->payment_status);
        $this->assertSame('qris_manual', $order->latestPayment->gateway);
        $this->assertSame('qris', $order->latestPayment->payment_method);
        $this->assertSame('QRIS Brownies Kelana', $order->latestPayment->channel_label);

        // 3. Halaman pembayaran menampilkan QRIS resmi, tombol Hubungi Penjual (WA), dan form Lampirkan Bukti Bayar
        $this->get(route('payments.show', $order))
            ->assertOk()
            ->assertSee('qris-brownies-kelana.jpg')
            ->assertSee('ID1025429266147')
            ->assertSee('Hubungi Penjual (WhatsApp)')
            ->assertSee('Lampirkan Bukti Pembayaran')
            ->assertDontSee('snap.js');

        // Akses manual URL /status/berhasil saat order belum diverifikasi admin TIDAK melunaskan order
        $this->get(route('payments.result', ['order' => $order, 'state' => 'berhasil']))
            ->assertRedirect(route('payments.result', ['order' => $order, 'state' => 'pending']));
        $this->assertSame('belum_dibayar', $order->fresh()->payment_status);

        // 4. Pelanggan melampirkan foto bukti bayar QRIS
        $sampleImageBytes = file_get_contents(public_path('images/kelana/qris-brownies-kelana.jpg'));
        $fakeProof = UploadedFile::fake()->createWithContent('bukti-qris.jpg', $sampleImageBytes);
        $this->post(route('payments.upload-proof', $order), [
            'payment_proof' => $fakeProof,
            'sender_name' => 'BCA a.n. Sinta',
            'customer_payment_note' => 'Sudah bayar via m-BCA',
        ])->assertRedirect(route('payments.show', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('menunggu_verifikasi', $order->payment_status);
        $this->assertSame('waiting_verification', $order->latestPayment->status);
        $this->assertNotNull($order->latestPayment->proof_path);
        $this->assertSame('BCA a.n. Sinta', $order->latestPayment->sender_name);

        // Pesanan yang sudah melampirkan bukti TIDAK boleh kedaluwarsa otomatis
        $order->update(['payment_deadline_at' => now()->subMinutes(10)]);
        $this->assertFalse($order->expireIfPastDeadline());
        $this->assertSame('menunggu_verifikasi', $order->fresh()->payment_status);

        $this->assertTrue(
            UserNotification::query()
                ->where('audience', 'admin')
                ->where('order_id', $order->id)
                ->where('title', 'Bukti Bayar QRIS Perlu Verifikasi')
                ->exists()
        );

        // 5. Admin menolak bukti bayar yang kurang jelas -> status kembali belum_dibayar agar pelanggan bisa unggah ulang
        $this->actingAs($admin)->post(route('admin.orders.reject-payment', $order), [
            'rejection_reason' => 'Foto tangkapan layar terpotong, nominal belum terbaca.',
        ])->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('belum_dibayar', $order->payment_status);
        $this->assertSame('rejected', $order->latestPayment->status);
        $this->assertSame('Foto tangkapan layar terpotong, nominal belum terbaca.', $order->latestPayment->rejection_reason);

        // 6. Pelanggan melihat alasan penolakan lalu mengunggah ulang bukti bayar yang jelas
        $this->actingAs($customer)->get(route('payments.show', $order))
            ->assertOk()
            ->assertSee('Foto tangkapan layar terpotong, nominal belum terbaca.');

        $secondProof = UploadedFile::fake()->createWithContent('bukti-qris-jelas.jpg', $sampleImageBytes);
        $this->actingAs($customer)->post(route('payments.upload-proof', $order), [
            'payment_proof' => $secondProof,
            'sender_name' => 'BCA a.n. Sinta',
        ])->assertRedirect(route('payments.show', $order));

        $this->assertSame('menunggu_verifikasi', $order->fresh()->payment_status);

        // 7. Admin memeriksa detail pesanan & melakukan verifikasi pembayaran berhasil secara manual
        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Verifikasi Pembayaran Manual (QRIS)')
            ->assertSee('Verifikasi Pembayaran Berhasil');

        $this->actingAs($admin)->post(route('admin.orders.mark-paid', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('lunas', $order->payment_status);
        $this->assertSame('diproses', $order->order_status);
        $this->assertSame('verified', $order->latestPayment->status);
        $this->assertSame($admin->id, $order->latestPayment->verified_by);
        $this->assertNotNull($order->paid_at);

        // 8. Pelanggan diarahkan ke halaman hasil pembayaran terverifikasi
        $this->actingAs($customer)->get(route('payments.show', $order))
            ->assertRedirect(route('payments.result', ['order' => $order, 'state' => 'berhasil']));
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

        // 6. Pastikan cek status pembayaran dapat diakses via GET maupun POST tanpa error 405 Method Not Allowed
        $this->get(route('payments.check-status', $completedOrder))->assertRedirect();
        $this->post(route('payments.check-status', $completedOrder))->assertRedirect();
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
