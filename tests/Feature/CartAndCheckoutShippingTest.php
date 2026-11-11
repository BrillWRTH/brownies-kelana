<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Holiday;
use App\Models\Order;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\StoreSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CartAndCheckoutShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Memastikan pelanggan dapat menambah, mengubah jumlah, menulis catatan, dan menghapus item keranjang, serta produk habis ditolak.
     */
    public function test_cart_crud_operations_and_sold_out_rejection(): void
    {
        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $fudgy = Product::query()->where('slug', 'brownies-original')->with('variants')->firstOrFail();
        $soldOut = Product::query()->where('slug', 'matcha-burnt-cheesecake')->with('variants')->firstOrFail();

        $this->actingAs($customer);

        // Produk stok habis ditolak saat masuk keranjang
        $this->post(route('cart.items.store'), [
            'product_id' => $soldOut->id,
            'quantity' => 1,
        ])->assertRedirect()
            ->assertSessionHas('error');

        // Produk tersedia berhasil masuk keranjang dengan custom note
        $this->post(route('cart.items.store'), [
            'product_id' => $fudgy->id,
            'product_variant_id' => $fudgy->variants->first()->id,
            'quantity' => 2,
            'custom_note' => 'Happy Birthday!',
        ])->assertRedirect()
            ->assertSessionHas('open_cart_drawer', true);

        $cart = Cart::query()->where('user_id', $customer->id)->with('items')->firstOrFail();
        $item = $cart->items->firstOrFail();
        $this->assertSame(2, $item->quantity);
        $this->assertSame('Happy Birthday!', $item->custom_note);

        // Perbarui kuantitas item
        $this->patch(route('cart.items.update', $item), ['quantity' => 4])
            ->assertRedirect();
        $this->assertSame(4, $item->fresh()->quantity);

        // Simpan catatan keseluruhan keranjang
        $this->patch(route('cart.note.update'), ['order_note' => 'Kirim sore ya kak'])
            ->assertRedirect();
        $this->assertSame('Kirim sore ya kak', $cart->fresh()->order_note);

        // Hapus item dari keranjang
        $this->delete(route('cart.items.destroy', $item))
            ->assertRedirect();
        $this->assertCount(0, $cart->fresh()->items);
    }

    /**
     * Memastikan aturan H-1 menolak tanggal hari ini, hari tutup, hari libur, dan kuota penuh.
     */
    public function test_checkout_validates_h1_closed_days_holidays_and_daily_quota(): void
    {
        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $fudgy = Product::query()->where('slug', 'brownies-original')->with('variants')->firstOrFail();
        $pickup = PickupLocation::query()->where('is_active', true)->firstOrFail();
        $storeSetting = StoreSetting::current();

        $this->actingAs($customer)->post(route('cart.items.store'), [
            'product_id' => $fudgy->id,
            'product_variant_id' => $fudgy->variants->first()->id,
            'quantity' => 1,
        ]);

        $basePayload = [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567890',
            'delivery_type' => 'pickup',
            'pickup_location_id' => $pickup->id,
            'recipient_name' => $customer->name,
            'recipient_phone' => '081234567890',
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'payment_method' => 'midtrans_snap',
        ];

        // 1. Tolak hari ini (melanggar aturan H-1)
        $this->post(route('checkout.store'), array_merge($basePayload, [
            'scheduled_date' => Carbon::today()->toDateString(),
        ]))->assertSessionHasErrors('scheduled_date');

        // 2. Cari hari Rabu berikutnya yang disetel tutup di operasional toko
        $nextWednesday = Carbon::today()->addDays(2);
        while ($nextWednesday->dayOfWeek !== Carbon::WEDNESDAY) {
            $nextWednesday->addDay();
        }
        $this->post(route('checkout.store'), array_merge($basePayload, [
            'scheduled_date' => $nextWednesday->toDateString(),
        ]))->assertSessionHasErrors('scheduled_date');

        // 3. Tolak tanggal hari libur terdaftar
        $holidayDate = Holiday::query()->where('is_active', true)->firstOrFail()->holiday_date->toDateString();
        $this->post(route('checkout.store'), array_merge($basePayload, [
            'scheduled_date' => $holidayDate,
        ]))->assertSessionHasErrors('scheduled_date');

        // 4. Cari tanggal terbuka valid lalu penuhkan kuota menjadi 1 untuk menguji pembatasan kuota penuh
        $openDate = $this->findFirstAvailableDate($storeSetting);
        $storeSetting->update(['daily_quota' => 1]);
        Order::create([
            'order_number' => 'BC-9001',
            'user_id' => $customer->id,
            'source' => 'website',
            'customer_name' => 'Test',
            'customer_phone' => '0812',
            'delivery_type' => 'pickup',
            'pickup_location_id' => $pickup->id,
            'recipient_name' => 'Test',
            'recipient_phone' => '0812',
            'scheduled_date' => $openDate,
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'subtotal' => 50000,
            'shipping_fee' => 0,
            'total_amount' => 50000,
            'order_status' => 'diproses',
            'payment_status' => 'lunas',
        ]);

        $this->post(route('checkout.store'), array_merge($basePayload, [
            'scheduled_date' => $openDate,
        ]))->assertSessionHasErrors('scheduled_date');
    }

    /**
     * Memastikan perhitungan ongkir mode Pickup, Flat, Zona, dan Jarak (termasuk batas max km) bekerja akurat.
     */
    public function test_shipping_modes_flat_zone_distance_and_pickup_calculate_correctly(): void
    {
        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $storeSetting = StoreSetting::current();
        $zone = ShippingZone::query()->where('is_active', true)->orderByDesc('fee')->firstOrFail();

        $this->actingAs($customer);

        // Mode Pickup selalu Rp 0
        $this->postJson(route('checkout.check-shipping'), [
            'delivery_type' => 'pickup',
        ])->assertOk()
            ->assertJsonPath('shipping.fee', 0)
            ->assertJsonPath('shipping.method', 'pickup');

        // Mode Flat
        $storeSetting->update(['shipping_method' => 'flat', 'flat_shipping_fee' => 15000]);
        $this->postJson(route('checkout.check-shipping'), [
            'delivery_type' => 'delivery',
        ])->assertOk()
            ->assertJsonPath('shipping.fee', 15000)
            ->assertJsonPath('shipping.method', 'flat');

        // Mode Zona
        $storeSetting->update(['shipping_method' => 'zone']);
        $this->postJson(route('checkout.check-shipping'), [
            'delivery_type' => 'delivery',
            'shipping_zone_id' => $zone->id,
        ])->assertOk()
            ->assertJsonPath('shipping.fee', $zone->fee)
            ->assertJsonPath('shipping.method', 'zone');

        // Mode Jarak: base 3 km = Rp 10.000, +Rp 2.500/km maks 25 km
        // Jarak 5.2 km => kelebihan ceil(5.2 - 3) = 3 km => 10.000 + (3 * 2.500) = 17.500
        $storeSetting->update([
            'shipping_method' => 'distance',
            'distance_base_km' => 3.0,
            'distance_base_fee' => 10000,
            'distance_per_km_fee' => 2500,
            'distance_max_km' => 25.0,
        ]);

        $this->postJson(route('checkout.check-shipping'), [
            'delivery_type' => 'delivery',
            'distance_km' => 5.2,
        ])->assertOk()
            ->assertJsonPath('shipping.fee', 17500)
            ->assertJsonPath('shipping.within_range', true);

        // Jarak melebihi 25 km => ditolak / within_range false
        $this->postJson(route('checkout.check-shipping'), [
            'delivery_type' => 'delivery',
            'distance_km' => 30.5,
        ])->assertOk()
            ->assertJsonPath('shipping.within_range', false);
    }

    /**
     * Memastikan server mengabaikan manipulasi jarak frontend, menolak varian habis, dan menolak zona/pickup nonaktif.
     */
    public function test_checkout_ignores_tampered_distance_and_rejects_unavailable_variants_or_inactive_shipping(): void
    {
        Config::set('services.midtrans.server_key', 'SB-Mid-server-TESTKEY');
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-security-test',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token-security-test',
            ], 201),
        ]);

        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $product = Product::query()->where('slug', 'brownies-original')->with('variants')->firstOrFail();
        $variant = $product->variants->first();
        $storeSetting = StoreSetting::current();
        $openDate = $this->findFirstAvailableDate($storeSetting);

        $this->actingAs($customer);

        // 1. Varian yang tidak tersedia ditolak masuk keranjang
        $variant->update(['is_available' => false]);
        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect()
            ->assertSessionHas('error');

        $variant->update(['is_available' => true]);
        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect();

        // 2. Mode ongkir jarak: kirim koordinat jauh (Malang ~80 km) dengan manipulasi delivery_distance_km = 0.5 => wajib ditolak!
        $storeSetting->update([
            'shipping_method' => 'distance',
            'distance_base_km' => 3.0,
            'distance_base_fee' => 10000,
            'distance_per_km_fee' => 2500,
            'distance_max_km' => 25.0,
        ]);

        $this->post(route('checkout.store'), [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567890',
            'delivery_type' => 'delivery',
            'recipient_name' => $customer->name,
            'recipient_phone' => '081234567890',
            'delivery_address' => 'Alun-alun Malang',
            'delivery_latitude' => -7.9826,
            'delivery_longitude' => 112.6308,
            'delivery_distance_km' => 0.5,
            'scheduled_date' => $openDate,
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'payment_method' => 'midtrans_snap',
        ])->assertSessionHasErrors('delivery_address');

        // 3. Pickup nonaktif ditolak
        $pickup = PickupLocation::query()->where('is_active', true)->firstOrFail();
        $pickup->update(['is_active' => false]);

        $this->post(route('checkout.store'), [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081234567890',
            'delivery_type' => 'pickup',
            'pickup_location_id' => $pickup->id,
            'recipient_name' => $customer->name,
            'recipient_phone' => '081234567890',
            'scheduled_date' => $openDate,
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'payment_method' => 'midtrans_snap',
        ])->assertSessionHasErrors('pickup_location_id');
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
