<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Menampilkan katalog produk dengan dukungan filter kategori, K3Mart, dan pencarian.
     */
    public function index(Request $request): View
    {
        // 1. Ambil daftar kategori aktif untuk sidebar katalog
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $searchKeyword = trim((string) $request->query('q', ''));
        $selectedCategorySlug = trim((string) $request->query('kategori', ''));
        $isK3martFilter = $request->boolean('k3mart');

        // 2. Bangun query produk aktif sesuai filter pencarian dan kategori
        $productsQuery = Product::query()
            ->with(['category', 'variants', 'images'])
            ->where('is_active', true);

        if ($isK3martFilter) {
            $productsQuery->where('is_k3mart', true);
        }

        if ($selectedCategorySlug !== '') {
            $productsQuery->whereHas('category', function ($query) use ($selectedCategorySlug): void {
                $query->where('slug', $selectedCategorySlug);
            });
        }

        if ($searchKeyword !== '') {
            $productsQuery->where(function ($query) use ($searchKeyword): void {
                $query->where('name', 'like', '%'.$searchKeyword.'%')
                    ->orWhere('description', 'like', '%'.$searchKeyword.'%');
            });
        }

        // 3. Urutkan produk tersedia di atas produk habis lalu paginasi
        $products = $productsQuery
            ->orderByDesc('is_available')
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view('products.index', compact(
            'categories',
            'products',
            'searchKeyword',
            'selectedCategorySlug',
            'isK3martFilter'
        ));
    }

    /**
     * Menampilkan detail produk beserta varian harga, galeri foto, dan ulasan terverifikasi.
     */
    public function show(Product $product): View
    {
        // 1. Pastikan hanya produk aktif yang dapat diakses dari sisi publik
        if (! $product->is_active) {
            abort(404);
        }

        // 2. Muat relasi kategori, varian ukuran, galeri foto, dan ulasan yang disetujui
        $product->load(['category', 'variants', 'images']);

        $approvedReviews = $product->reviews()
            ->with('user')
            ->where('moderation_status', 'disetujui')
            ->latest()
            ->take(6)
            ->get();

        // 3. Ambil produk terkait dalam kategori yang sama
        $relatedProducts = Product::query()
            ->with(['variants', 'images'])
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(3)
            ->get();

        return view('products.show', compact('product', 'approvedReviews', 'relatedProducts'));
    }
}
