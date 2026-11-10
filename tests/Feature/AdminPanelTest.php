<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Holiday;
use App\Models\Order;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\Review;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Memastikan akses panel admin dilindungi dari pelanggan biasa dan admin dapat login menggunakan alias maupun email.
     */
    public function test_admin_guard_and_authentication_work_correctly(): void
    {
        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        // Uji login Admin menggunakan username alias 'admin.kelana'
        $this->post(route('admin.login.attempt'), [
            'email' => 'admin.kelana',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan Toko');

        $this->get(route('admin.orders.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /**
     * Memastikan alur pengelolaan pesanan admin: buat pesanan WhatsApp manual, tandai lunas, update status, unggah bukti kurir, dan cetak nota.
     */
    public function test_admin_order_management_manual_whatsapp_courier_and_receipt(): void
    {
        Storage::fake('public');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $product = Product::query()->with('variants')->firstOrFail();

        $this->actingAs($admin);

        // 1. Buat pesanan manual dari WhatsApp
        $createRes = $this->post(route('admin.orders.store'), [
            'customer_name' => 'Pak Hendra WA',
            'customer_phone' => '081377776666',
            'delivery_type' => 'delivery',
            'recipient_name' => 'Pak Hendra WA',
            'recipient_phone' => '081377776666',
            'delivery_address' => 'Perumahan Pondok Tjandra Indah No. 8',
            'manual_shipping_fee' => 12000,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time_slot' => '13.00-15.00 WIB',
            'payment_action' => 'belum_dibayar',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $product->variants->first()->id,
                    'quantity' => 2,
                    'custom_note' => 'Selamat Ulang Tahun',
                ],
            ],
        ]);

        $order = Order::query()->where('customer_name', 'Pak Hendra WA')->firstOrFail();
        $createRes->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame('whatsapp', $order->source);
        $this->assertSame('menunggu_pembayaran', $order->order_status);

        // 2. Tandai lunas manual oleh admin
        $this->post(route('admin.orders.mark-paid', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('lunas', $order->payment_status);
        $this->assertSame('diproses', $order->order_status);

        // 3. Tolak status khusus pickup pada pesanan delivery
        $this->post(route('admin.orders.update-status', $order), [
            'order_status' => 'siap_diambil',
        ])->assertRedirect()
            ->assertSessionHas('error');

        // 4. Unggah foto bukti pengiriman & perbarui info kurir sekaligus selesai
        $tinyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $proofFile = UploadedFile::fake()->createWithContent('bukti-antar.png', $tinyPng);

        $this->post(route('admin.orders.update-courier', $order), [
            'courier_name' => 'Mas Teguh Kurir',
            'courier_phone' => '085611112222',
            'courier_vehicle_plate' => 'W 4321 XY',
            'received_by' => 'Ibu Hendra',
            'delivery_proof' => $proofFile,
            'mark_delivered' => '1',
        ])->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('sampai_tujuan', $order->order_status);
        $this->assertSame('Ibu Hendra', $order->received_by);
        $this->assertNotNull($order->delivery_proof_path);

        // 5. Buka halaman cetak nota dan rekap produksi
        $this->get(route('admin.orders.receipt', $order))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->get(route('admin.orders.recap'))
            ->assertOk()
            ->assertSee('Rekap Produksi');
    }

    /**
     * Memastikan CRUD produk admin, moderasi ulasan, manajemen pelanggan, notifikasi, dan pengaturan toko berjalan mulus.
     */
    public function test_admin_products_reviews_customers_notifications_and_settings(): void
    {
        Storage::fake('public');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $category = Category::query()->firstOrFail();
        $this->actingAs($admin);

        // 1. Tambah produk baru beserta varian & gambar
        $tinyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $prodImg = UploadedFile::fake()->createWithContent('brownies-baru.png', $tinyPng);

        $this->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'product_code' => 'PRD-901',
            'name' => 'Brownies Kelana Red Velvet',
            'tagline' => 'Lezat dan lembut',
            'description' => 'Brownies red velvet spesial dengan cream cheese swirl.',
            'base_price' => 65000,
            'is_available' => '1',
            'is_active' => '1',
            'variants' => [
                ['name' => 'Box 20x10', 'price' => 65000, 'is_available' => '1'],
                ['name' => 'Box 20x20', 'price' => 115000, 'is_available' => '0'],
            ],
            'images' => [$prodImg],
        ])->assertRedirect();

        $createdProduct = Product::query()->where('product_code', 'PRD-901')->with(['variants', 'images'])->firstOrFail();
        $this->assertCount(2, $createdProduct->variants);
        $this->assertCount(1, $createdProduct->images);

        // Toggle stok habis cepat
        $this->post(route('admin.products.toggle-status', $createdProduct), ['field' => 'is_available'])
            ->assertRedirect();
        $this->assertFalse($createdProduct->fresh()->is_available);

        // 2. Moderasi ulasan: setujui, balas, dan toggle tampil di Home
        $review = Review::query()->firstOrFail();
        $this->post(route('admin.reviews.approve', $review))->assertRedirect();
        $this->post(route('admin.reviews.reply', $review), [
            'admin_reply' => 'Terima kasih banyak Kak atas kepercayaannya!',
        ])->assertRedirect();
        $this->post(route('admin.reviews.toggle-home', $review))->assertRedirect();

        $review->refresh();
        $this->assertSame('disetujui', $review->moderation_status);
        $this->assertNotNull($review->admin_reply);

        // 3. Nonaktifkan lalu aktifkan kembali akun pelanggan + catat log pemulihan WA
        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $this->post(route('admin.customers.toggle-active', $customer), [
            'deactivated_reason' => 'Verifikasi data sementara',
        ])->assertRedirect();
        $this->assertFalse($customer->fresh()->is_active);

        $this->post(route('admin.customers.toggle-active', $customer))->assertRedirect();
        $this->assertTrue($customer->fresh()->is_active);

        $this->post(route('admin.customers.recovery', $customer), [
            'note' => 'Membantu reset kata sandi pelanggan via WhatsApp.',
        ])->assertRedirect();

        // 4. Kirim notifikasi khusus dashboard pelanggan
        $this->post(route('admin.notifications.store'), [
            'target_type' => 'single',
            'user_id' => $customer->id,
            'category' => 'sistem',
            'channel' => 'dashboard',
            'title' => 'Info Promo Akhir Pekan',
            'message' => 'Halo {nama_pelanggan}, tersedia menu spesial di K3Mart!',
        ])->assertRedirect(route('admin.notifications.history'));

        // 5. Ubah pengaturan metode ongkir ke Jarak & tambah hari libur + pickup baru
        $this->put(route('admin.settings.shipping.update'), [
            'shipping_method' => 'distance',
            'flat_shipping_fee' => 15000,
            'distance_base_km' => 3.0,
            'distance_base_fee' => 10000,
            'distance_per_km_fee' => 3000,
            'distance_max_km' => 25.0,
        ])->assertRedirect();

        $this->assertSame('distance', StoreSetting::current()->shipping_method);

        // Tolak duplikasi tanggal libur yang sudah terdaftar dengan validasi rapi (bukan error 500)
        $this->post(route('admin.settings.holidays.store'), [
            'holiday_date' => '2026-12-25',
            'name' => 'Duplikat Natal',
        ])->assertSessionHasErrors('holiday_date');

        $holidayDate = now()->addDays(60)->toDateString();
        $this->post(route('admin.settings.holidays.store'), [
            'holiday_date' => $holidayDate,
            'name' => 'Libur Khusus Awal Tahun',
        ])->assertRedirect();
        $this->assertTrue(Holiday::query()->whereDate('holiday_date', $holidayDate)->exists());

        $this->post(route('admin.settings.pickups.store'), [
            'name' => 'Cabang Merr Surabaya',
            'address' => 'Jl. Dr. Ir. H. Soekarno No. 100, Surabaya',
            'operating_info' => '09.00-16.00 WIB',
        ])->assertRedirect();
        $this->assertTrue(PickupLocation::query()->where('name', 'Cabang Merr Surabaya')->exists());

        // Verifikasi seluruh halaman GET Admin Panel merender HTTP 200 tanpa error Blade
        $sampleOrder = Order::query()->firstOrFail();
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.orders.create'))->assertOk();
        $this->get(route('admin.orders.edit', $sampleOrder))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.products.edit', $createdProduct))->assertOk();
        $this->get(route('admin.reviews.index'))->assertOk();
        $this->get(route('admin.reviews.show', $review))->assertOk();
        $this->get(route('admin.customers.index'))->assertOk();
        $this->get(route('admin.customers.show', $customer))->assertOk();
        $this->get(route('admin.notifications.index'))->assertOk();
        $this->get(route('admin.notifications.templates'))->assertOk();
        $this->get(route('admin.notifications.create'))->assertOk();
        $this->get(route('admin.notifications.history'))->assertOk();
        $this->get(route('admin.settings.index'))->assertOk();
    }
}
