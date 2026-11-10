<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Holiday;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\Review;
use App\Models\StoreSetting;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Menampilkan halaman utama (Landing Page) Brownies Kelana.
     */
    public function index(): View
    {
        // 1. Ambil kategori utama yang aktif untuk etalase Produk Kami
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(3)
            ->get();

        // 2. Ambil produk unggulan yang tersedia di katalog utama
        $featuredProducts = Product::query()
            ->with(['category', 'images', 'variants'])
            ->where('is_active', true)
            ->where('is_k3mart', false)
            ->where('is_featured', true)
            ->take(6)
            ->get();

        // 3. Ambil produk khusus K3Mart Corner (Sisi lain dari Kelana)
        $k3martProducts = Product::query()
            ->with(['category', 'images', 'variants'])
            ->where('is_active', true)
            ->where('is_k3mart', true)
            ->take(3)
            ->get();

        // 4. Ambil ulasan pelanggan yang disetujui dan dipilih tampil di Home
        $homeReviews = Review::query()
            ->with(['user', 'product'])
            ->where('moderation_status', 'disetujui')
            ->where('is_featured_home', true)
            ->latest()
            ->take(6)
            ->get();

        return view('pages.home', compact('categories', 'featuredProducts', 'k3martProducts', 'homeReviews'));
    }

    /**
     * Menampilkan halaman Tentang Kami beserta jam operasional dan lokasi toko.
     */
    public function about(): View
    {
        // 1. Ambil pengaturan toko dan daftar lokasi pickup aktif
        $storeSetting = StoreSetting::current();
        $pickupLocations = PickupLocation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pages.about', compact('storeSetting', 'pickupLocations'));
    }

    /**
     * Menampilkan halaman panduan Cara Pemesanan langkah demi langkah.
     */
    public function guide(): View
    {
        $storeSetting = StoreSetting::current();

        return view('pages.guide', compact('storeSetting'));
    }

    /**
     * Menampilkan halaman informasi Pengiriman dan Pengambilan Mandiri.
     */
    public function shippingInfo(): View
    {
        $storeSetting = StoreSetting::current();
        $pickupLocations = PickupLocation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $holidays = Holiday::query()
            ->where('is_active', true)
            ->whereDate('holiday_date', '>=', now()->toDateString())
            ->orderBy('holiday_date')
            ->get();

        return view('pages.shipping-info', compact('storeSetting', 'pickupLocations', 'holidays'));
    }

    /**
     * Menampilkan halaman Informasi Alergen seluruh kategori produk.
     */
    public function allergenInfo(): View
    {
        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.allergen', compact('products'));
    }

    /**
     * Menampilkan halaman Kebijakan Privasi.
     */
    public function privacy(): View
    {
        $storeSetting = StoreSetting::current();

        return view('pages.privacy', compact('storeSetting'));
    }

    /**
     * Menampilkan halaman Syarat dan Ketentuan.
     */
    public function terms(): View
    {
        $storeSetting = StoreSetting::current();

        return view('pages.terms', compact('storeSetting'));
    }
}
