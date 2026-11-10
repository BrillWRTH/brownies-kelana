<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontAndCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Memastikan landing page dan seluruh halaman informasi publik tampil lengkap.
     */
    public function test_public_storefront_and_information_pages_render_successfully(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Brownies Kelana')
            ->assertSee('Brownies Original')
            ->assertSee('Sushi Crab Bake');

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Grand Royal Regency');

        $this->get(route('orders.guide'))
            ->assertOk();

        $this->get(route('shipping.info'))
            ->assertOk()
            ->assertSee('Bank bjb Kanwil V');

        $this->get(route('allergen.info'))
            ->assertOk();

        $this->get(route('privacy'))
            ->assertOk();

        $this->get(route('terms'))
            ->assertOk();
    }

    /**
     * Memastikan pencarian produk, filter kategori, filter K3Mart, dan halaman detail bekerja akurat.
     */
    public function test_catalog_filters_and_product_detail_page_work_correctly(): void
    {
        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Brownies Original')
            ->assertSee('Basque Burnt Cheesecake');

        $this->get(route('products.index', ['q' => 'Cheesecake']))
            ->assertOk()
            ->assertSee('Basque Burnt Cheesecake')
            ->assertDontSee('Dimsum Ayam Mentai');

        $this->get(route('products.index', ['k3mart' => 1]))
            ->assertOk()
            ->assertSee('Sushi Crab Bake')
            ->assertSee('Goguma Ppang');

        $availableProduct = Product::query()->where('slug', 'brownies-original')->firstOrFail();
        $this->get(route('products.show', $availableProduct))
            ->assertOk()
            ->assertSee('Brownies Original')
            ->assertSee('+ Keranjang')
            ->assertSee('Beli Sekarang');

        $soldOutProduct = Product::query()->where('slug', 'matcha-burnt-cheesecake')->firstOrFail();
        $this->get(route('products.show', $soldOutProduct))
            ->assertOk()
            ->assertSee('Stok Habis');
    }
}
