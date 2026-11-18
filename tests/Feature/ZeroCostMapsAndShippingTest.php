<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\GosendCalibration;
use App\Models\Order;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\OpenRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZeroCostMapsAndShippingTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $admin;
    protected StoreSetting $storeSetting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->customer = User::query()->where('role', 'customer')->first() ?? User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->admin = User::query()->where('role', 'admin')->first() ?? User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $hours = [];
        foreach ($days as $d) {
            $hours[$d] = ['is_open' => true, 'open' => '08:00', 'close' => '17:00', 'label' => ucfirst($d)];
        }

        $this->storeSetting = StoreSetting::current();
        $this->storeSetting->update([
            'shipping_method' => 'distance',
            'distance_base_km' => 3.0,
            'distance_base_fee' => 10000,
            'distance_per_km_fee' => 2500,
            'safety_margin_percentage' => 10,
            'safety_margin_flat' => 1000,
            'shipping_rounding' => 500,
            'distance_max_km' => 25.0,
            'shipping_service_name' => 'Kurir Kelana / GoSend',
            'operating_hours' => $hours,
        ]);
    }

    /**
     * Endpoint geocoding server-side mengembalikan daftar tempat tanpa mengekspos API key.
     */
    public function test_checkout_geocode_returns_place_results(): void
    {
        $response = $this->actingAs($this->customer)->postJson(route('checkout.geocode'), [
            'query' => 'Pulosari Surabaya',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'results']);
    }

    /**
     * Endpoint reverse geocoding mengubah koordinat menjadi rincian alamat.
     */
    public function test_checkout_reverse_geocode_returns_address_details(): void
    {
        $response = $this->actingAs($this->customer)->postJson(route('checkout.reverse-geocode'), [
            'latitude' => -7.288625,
            'longitude' => 112.738580,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['display_name']]);
    }

    /**
     * Cek ongkir kalkulasi dinamis menghitung jarak rute, safety margin, dan pembulatan.
     */
    public function test_check_shipping_dynamic_distance_margin_and_rounding(): void
    {
        // Jarak 6 km dari dapur:
        // Base 3 km = 10.000. Sisa 3 km * 2.500 = 7.500. Subtotal = 17.500.
        // Margin: 10% dari 17.500 = 1.750 + flat 1.000 = 2.750.
        // Raw fee = 17.500 + 2.750 = 20.250.
        // Rounding 500 => ceil(20.250 / 500) * 500 = 20.500.
        $response = $this->actingAs($this->customer)->postJson(route('checkout.check-shipping'), [
            'delivery_type' => 'delivery',
            'distance_km' => 6.0,
        ]);

        $response->assertOk()
            ->assertJsonPath('shipping.within_range', true)
            ->assertJsonPath('shipping.fee', 20500)
            ->assertJsonPath('shipping.breakdown.base_fee', 10000)
            ->assertJsonPath('shipping.breakdown.additional_fee', 7500)
            ->assertJsonPath('shipping.breakdown.margin_fee', 2750);
    }

    /**
     * Mode Pickup gratis ongkir (Rp 0) dengan data titik lokasi pengambilan.
     */
    public function test_check_shipping_pickup_mode_is_free(): void
    {
        $pickup = PickupLocation::query()->where('is_active', true)->first();

        $response = $this->actingAs($this->customer)->postJson(route('checkout.check-shipping'), [
            'delivery_type' => 'pickup',
            'pickup_location_id' => $pickup->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('shipping.fee', 0)
            ->assertJsonPath('shipping.method', 'pickup')
            ->assertJsonPath('shipping.within_range', true)
            ->assertJsonPath('shipping.pickup_location.name', $pickup->name);
    }

    /**
     * Pelanggan dapat menyimpan alamat baru ke buku alamat tersimpan.
     */
    public function test_customer_can_save_address_to_user_addresses(): void
    {
        $response = $this->actingAs($this->customer)->postJson(route('checkout.save-address'), [
            'label' => 'Kantor Baru',
            'recipient_name' => 'Budi Santoso',
            'recipient_phone' => '081234567890',
            'full_address' => 'Jl. Pemuda No. 45, Embong Kaliasin, Genteng, Surabaya',
            'street' => 'Jl. Pemuda',
            'district' => 'Genteng',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'postal_code' => '60271',
            'latitude' => -7.265500,
            'longitude' => 112.748200,
            'location_notes' => 'Lantai 3 Gedung Graha',
            'is_default' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->customer->id,
            'label' => 'Kantor Baru',
            'recipient_name' => 'Budi Santoso',
            'city' => 'Surabaya',
            'is_default' => true,
        ]);
    }

    /**
     * Admin dapat memperbarui parameter tarif estimator, safety margin, dan pembulatan.
     */
    public function test_admin_can_update_shipping_estimator_settings(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.shipping.update'), [
            'shipping_method' => 'distance',
            'flat_shipping_fee' => 15000,
            'distance_base_km' => 4.0,
            'distance_base_fee' => 12000,
            'distance_per_km_fee' => 3000,
            'distance_max_km' => 30.0,
            'safety_margin_percentage' => 15,
            'safety_margin_flat' => 2000,
            'shipping_rounding' => 1000,
            'shipping_service_name' => 'GoSend Instant',
        ]);

        $response->assertRedirect();

        $updated = StoreSetting::current();
        $this->assertSame('distance', $updated->shipping_method);
        $this->assertEquals(4.0, $updated->distance_base_km);
        $this->assertSame(12000, $updated->distance_base_fee);
        $this->assertSame(3000, $updated->distance_per_km_fee);
        $this->assertSame(15, $updated->safety_margin_percentage);
        $this->assertSame(2000, $updated->safety_margin_flat);
        $this->assertSame(1000, $updated->shipping_rounding);
        $this->assertSame('GoSend Instant', $updated->shipping_service_name);
    }

    /**
     * Admin dapat mencatat data kalibrasi riil GoSend dan menghitung selisih harga.
     */
    public function test_admin_can_log_gosend_calibration(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.gosend-calibration.store'), [
            'date' => now()->toDateString(),
            'service_type' => 'Instant',
            'origin_address' => 'Grand Royal Regency, Taman, Sidoarjo',
            'origin_latitude' => -7.372821,
            'origin_longitude' => 112.719681,
            'destination_address' => 'Jl. Basuki Rahmat No. 12 Surabaya',
            'destination_latitude' => -7.265500,
            'destination_longitude' => 112.748200,
            'actual_gosend_price' => 24000,
            'notes' => 'Uji rute tengah hari',
        ]);

        $response->assertRedirect();

        $calibration = GosendCalibration::latest()->first();
        $this->assertNotNull($calibration);
        $this->assertSame(24000, $calibration->actual_gosend_price);
        $this->assertNotNull($calibration->estimated_internal_price);
        $this->assertNotNull($calibration->price_difference);
    }

    /**
     * Pesanan menyimpan snapshot quotation rincian ongkir saat order dibuat.
     */
    public function test_order_stores_shipping_quotation_snapshot(): void
    {
        // Siapkan keranjang dengan 1 produk
        $product = Product::query()->where('is_active', true)->where('is_available', true)->first();
        $cart = Cart::query()->create(['user_id' => $this->customer->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        config(['services.midtrans.server_key' => 'SB-Mid-server-test']);

        // Mock HTTP request ke Midtrans Snap agar mengembalikan token sukses
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'mock-snap-token-12345',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-12345',
            ], 201),
        ]);

        $tomorrow = now()->addDay()->toDateString();
        $response = $this->actingAs($this->customer)->post(route('checkout.store'), [
            'customer_name' => 'Sinta Maharani',
            'customer_email' => 'sinta@example.com',
            'customer_phone' => '081234567890',
            'delivery_type' => 'delivery',
            'recipient_name' => 'Sinta Maharani',
            'recipient_phone' => '081234567890',
            'delivery_address' => 'Jl. Raya Darmo No. 50, Surabaya',
            'delivery_latitude' => -7.288625,
            'delivery_longitude' => 112.738580,
            'scheduled_date' => $tomorrow,
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'payment_method' => 'midtrans_snap',
        ]);

        file_put_contents('test_debug.txt', json_encode([
            'session_errors' => session('errors') ? session('errors')->all() : null,
            'session_error' => session('error'),
            'location' => $response->headers->get('Location'),
        ]));

        $response->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->shipping_quotation_snapshot);
        $this->assertArrayHasKey('fee', $order->shipping_quotation_snapshot);
        $this->assertArrayHasKey('distance_km', $order->shipping_quotation_snapshot);
        $this->assertArrayHasKey('breakdown', $order->shipping_quotation_snapshot);
        $this->assertArrayHasKey('route_source', $order->shipping_quotation_snapshot);
    }
}
