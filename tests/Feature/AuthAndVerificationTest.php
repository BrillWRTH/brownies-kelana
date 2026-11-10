<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Memastikan pendaftaran pelanggan baru menghasilkan kode CUS-XXXX, notifikasi verifikasi, dan rute verifikasi bertanda tangan.
     */
    public function test_customer_can_register_and_verify_email(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Rani Kusuma',
            'email' => 'rani@example.com',
            'phone' => '081299998888',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'agree_terms' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'rani@example.com')->firstOrFail();
        $this->assertStringStartsWith('CUS-', (string) $user->customer_code);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);

        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($signedUrl)
            ->assertRedirect(route('verification.verified'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /**
     * Memastikan item keranjang tamu digabung ke akun pelanggan saat login dan akun nonaktif diblokir.
     */
    public function test_login_merges_guest_cart_and_inactive_accounts_are_blocked(): void
    {
        $product = Product::query()->where('slug', 'brownies-original')->with('variants')->firstOrFail();

        // Tambahkan item ke keranjang sebagai tamu terlebih dahulu
        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 2,
        ])->assertRedirect();

        $guestCart = Cart::query()->whereNull('user_id')->latest('id')->firstOrFail();
        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();

        $this->withCookie(config('session.cookie'), $guestCart->session_id)
            ->post(route('login.attempt'), [
                'email' => $customer->email,
                'password' => 'password',
            ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($customer);
        $cart = Cart::query()->where('user_id', $customer->id)->with('items')->firstOrFail();
        $this->assertTrue($cart->items->contains('product_id', $product->id));

        // Nonaktifkan akun dan pastikan akses login serta rute terproteksi ditolak
        $customer->update([
            'is_active' => false,
            'deactivated_reason' => 'Pelanggaran kebijakan.',
            'deactivated_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();

        $this->post(route('login.attempt'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect()
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    /**
     * Memastikan alur reset kata sandi memperbarui password pengguna secara valid.
     */
    public function test_customer_can_reset_password_with_broker_token(): void
    {
        $customer = User::query()->where('email', 'sinta@example.com')->firstOrFail();
        $token = Password::createToken($customer);

        $this->get(route('password.reset', ['token' => $token, 'email' => $customer->email]))
            ->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'passwordbaru99',
            'password_confirmation' => 'passwordbaru99',
        ])->assertRedirect(route('password.success'));

        $this->post(route('login.attempt'), [
            'email' => $customer->email,
            'password' => 'passwordbaru99',
        ])->assertRedirect(route('dashboard'));
    }
}
