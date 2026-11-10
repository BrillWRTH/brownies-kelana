<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar seluruh produk katalog beserta pencarian dan filter status.
     */
    public function index(Request $request): View
    {
        // 1. Ambil filter pencarian, kategori, dan ketersediaan dari query
        $searchKeyword = trim((string) $request->query('q', ''));
        $categoryId = trim((string) $request->query('category_id', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $productsQuery = Product::query()
            ->with(['category', 'variants', 'images'])
            ->latest('id');

        if ($searchKeyword !== '') {
            $productsQuery->where(function ($query) use ($searchKeyword): void {
                $query->where('name', 'like', '%'.$searchKeyword.'%')
                    ->orWhere('product_code', 'like', '%'.$searchKeyword.'%');
            });
        }

        if ($categoryId !== '') {
            $productsQuery->where('category_id', (int) $categoryId);
        }

        if ($statusFilter === 'tersedia') {
            $productsQuery->where('is_active', true)->where('is_available', true);
        } elseif ($statusFilter === 'habis') {
            $productsQuery->where('is_available', false);
        } elseif ($statusFilter === 'nonaktif') {
            $productsQuery->where('is_active', false);
        } elseif ($statusFilter === 'k3mart') {
            $productsQuery->where('is_k3mart', true);
        }

        $products = $productsQuery->paginate(12)->withQueryString();
        $categories = Category::query()->orderBy('sort_order')->get();

        return view('admin.products.index', compact(
            'products',
            'categories',
            'searchKeyword',
            'categoryId',
            'statusFilter'
        ));
    }

    /**
     * Menampilkan formulir penambahan produk baru beserta varian dan galeri foto.
     */
    public function create(): View
    {
        // 1. Siapkan kategori dan kode produk berikutnya secara berurutan
        $categories = Category::query()->orderBy('sort_order')->get();
        $nextId = ((int) Product::query()->max('id')) + 1;
        $suggestedCode = 'PRD-'.str_pad((string) $nextId, 3, '0', STR_PAD_LEFT);

        return view('admin.products.create', compact('categories', 'suggestedCode'));
    }

    /**
     * Menyimpan produk baru beserta varian harga dan foto galeri yang diunggah.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi data dasar produk, varian ukuran/rasa, dan file gambar galeri
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'product_code' => ['nullable', 'string', 'max:30', 'unique:products,product_code'],
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:3000'],
            'allergen_info' => ['nullable', 'string', 'max:500'],
            'base_price' => ['required', 'integer', 'min:1000'],
            'custom_note_max_chars' => ['nullable', 'integer', 'min:5', 'max:120'],
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.price' => ['required_with:variants', 'integer', 'min:1000'],
            'variants.*.is_available' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
        ], [
            'category_id.required' => 'Pilih kategori produk.',
            'name.required' => 'Nama produk wajib diisi.',
            'description.required' => 'Deskripsi produk wajib diisi.',
            'base_price.required' => 'Harga dasar wajib diisi.',
            'images.*.image' => 'Semua berkas foto harus berupa gambar.',
            'images.*.max' => 'Ukuran setiap foto maksimal 2MB.',
        ]);

        // 2. Bangun slug unik serta tentukan harga dasar dari varian termurah jika ada
        $slug = $this->generateUniqueSlug($validated['name']);
        $nextId = ((int) Product::query()->max('id')) + 1;
        $productCode = trim((string) ($validated['product_code'] ?? '')) ?: 'PRD-'.str_pad((string) $nextId, 3, '0', STR_PAD_LEFT);

        $variantsInput = collect($validated['variants'] ?? [])
            ->filter(fn (array $item): bool => trim((string) ($item['name'] ?? '')) !== '');

        $minPrice = $variantsInput->isNotEmpty()
            ? (int) $variantsInput->min('price')
            : (int) $validated['base_price'];

        // 3. Simpan produk, varian, dan unggahan foto di dalam transaksi database
        $createdProduct = DB::transaction(function () use ($request, $validated, $slug, $productCode, $minPrice, $variantsInput): Product {
            $product = Product::create([
                'category_id' => (int) $validated['category_id'],
                'product_code' => $productCode,
                'name' => trim($validated['name']),
                'slug' => $slug,
                'tagline' => trim((string) ($validated['tagline'] ?? '')) ?: null,
                'description' => trim($validated['description']),
                'allergen_info' => trim((string) ($validated['allergen_info'] ?? '')) ?: null,
                'base_price' => $minPrice,
                'is_available' => $request->boolean('is_available', true),
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured'),
                'is_k3mart' => $request->boolean('is_k3mart'),
                'is_weekend_only' => $request->boolean('is_weekend_only'),
                'allows_custom_note' => $request->boolean('allows_custom_note'),
                'custom_note_max_chars' => (int) ($validated['custom_note_max_chars'] ?? 30),
                'thumbnail' => 'images/kelana/prod-brownies-panggang.png',
            ]);

            if ($variantsInput->isNotEmpty()) {
                $sort = 1;
                foreach ($variantsInput as $variantRow) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => trim($variantRow['name']),
                        'price' => (int) $variantRow['price'],
                        'is_available' => isset($variantRow['is_available']) ? (bool) $variantRow['is_available'] : true,
                        'sort_order' => $sort++,
                    ]);
                }
            } else {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => 'Reguler',
                    'price' => $minPrice,
                    'is_available' => $product->is_available,
                    'sort_order' => 1,
                ]);
            }

            if ($request->hasFile('images')) {
                $firstThumbnail = null;
                foreach ($request->file('images') as $index => $imageFile) {
                    $storedPath = $imageFile->store('products', 'public');
                    $publicPath = 'storage/'.$storedPath;
                    if ($index === 0) {
                        $firstThumbnail = $publicPath;
                    }

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $publicPath,
                        'is_primary' => $index === 0,
                        'sort_order' => $index + 1,
                    ]);
                }

                if ($firstThumbnail) {
                    $product->update(['thumbnail' => $firstThumbnail]);
                }
            }

            return $product;
        });

        return redirect()->route('admin.products.edit', $createdProduct)
            ->with('success', 'Produk '.$createdProduct->name.' berhasil ditambahkan.');
    }

    /**
     * Mengarahkan rute detail admin langsung ke formulir edit produk yang lengkap.
     */
    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('admin.products.edit', $product);
    }

    /**
     * Menampilkan formulir ubah informasi produk, varian, status tampil, dan galeri foto.
     */
    public function edit(Product $product): View
    {
        $product->load(['category', 'variants', 'images']);
        $categories = Category::query()->orderBy('sort_order')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Memperbarui data produk, menyinkronkan varian harga, dan menambah foto galeri baru.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        // 1. Validasi data pembaruan produk, daftar varian, dan tambahan foto baru
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:3000'],
            'allergen_info' => ['nullable', 'string', 'max:500'],
            'base_price' => ['required', 'integer', 'min:1000'],
            'custom_note_max_chars' => ['nullable', 'integer', 'min:5', 'max:120'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.price' => ['required_with:variants', 'integer', 'min:1000'],
            'variants.*.is_available' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
        ]);

        // 2. Perbarui slug apabila nama berubah dan hitung harga terendah dari varian
        $slug = $product->name !== trim($validated['name'])
            ? $this->generateUniqueSlug($validated['name'], $product->id)
            : $product->slug;

        $variantsInput = collect($validated['variants'] ?? [])
            ->filter(fn (array $item): bool => trim((string) ($item['name'] ?? '')) !== '');

        $minPrice = $variantsInput->isNotEmpty()
            ? (int) $variantsInput->min('price')
            : (int) $validated['base_price'];

        // 3. Simpan perubahan ke tabel produk, varian, dan galeri gambar
        DB::transaction(function () use ($request, $product, $validated, $slug, $minPrice, $variantsInput): void {
            $product->update([
                'category_id' => (int) $validated['category_id'],
                'name' => trim($validated['name']),
                'slug' => $slug,
                'tagline' => trim((string) ($validated['tagline'] ?? '')) ?: null,
                'description' => trim($validated['description']),
                'allergen_info' => trim((string) ($validated['allergen_info'] ?? '')) ?: null,
                'base_price' => $minPrice,
                'is_available' => $request->boolean('is_available'),
                'is_active' => $request->boolean('is_active'),
                'is_featured' => $request->boolean('is_featured'),
                'is_k3mart' => $request->boolean('is_k3mart'),
                'is_weekend_only' => $request->boolean('is_weekend_only'),
                'allows_custom_note' => $request->boolean('allows_custom_note'),
                'custom_note_max_chars' => (int) ($validated['custom_note_max_chars'] ?? 30),
            ]);

            // Sinkronkan daftar varian produk
            $keptIds = [];
            $sort = 1;
            foreach ($variantsInput as $variantRow) {
                $variantId = isset($variantRow['id']) ? (int) $variantRow['id'] : null;
                $payload = [
                    'product_id' => $product->id,
                    'name' => trim($variantRow['name']),
                    'price' => (int) $variantRow['price'],
                    'is_available' => isset($variantRow['is_available']) ? (bool) $variantRow['is_available'] : true,
                    'sort_order' => $sort++,
                ];

                if ($variantId && $existing = ProductVariant::query()->where('product_id', $product->id)->find($variantId)) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;
                } else {
                    $createdVariant = ProductVariant::create($payload);
                    $keptIds[] = $createdVariant->id;
                }
            }

            if ($variantsInput->isNotEmpty()) {
                $product->variants()->whereNotIn('id', $keptIds)->delete();
            }

            // Tambahkan foto baru ke galeri produk jika diunggah
            if ($request->hasFile('images')) {
                $hasExistingImages = $product->images()->exists();
                $nextSort = ((int) $product->images()->max('sort_order')) + 1;

                foreach ($request->file('images') as $index => $imageFile) {
                    $storedPath = $imageFile->store('products', 'public');
                    $publicPath = 'storage/'.$storedPath;
                    $isPrimary = ! $hasExistingImages && $index === 0;

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $publicPath,
                        'is_primary' => $isPrimary,
                        'sort_order' => $nextSort++,
                    ]);

                    if ($isPrimary || str_contains((string) $product->thumbnail, 'prod-brownies-panggang.png')) {
                        $product->update(['thumbnail' => $publicPath]);
                    }
                }
            }
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Perubahan produk '.$product->name.' berhasil disimpan.');
    }

    /**
     * Menghapus produk dari katalog tanpa merusak riwayat item pesanan lama.
     */
    public function destroy(Product $product): RedirectResponse
    {
        // 1. Bersihkan file foto unggahan lokal produk pada storage publik
        $product->load('images');
        foreach ($product->images as $image) {
            if (str_starts_with($image->image_path, 'storage/')) {
                Storage::disk('public')->delete(substr($image->image_path, 8));
            }
        }

        // 2. Hapus produk dari database (order_items lama tetap menyimpan snapshot nama & harga)
        $productName = $product->name;
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk '.$productName.' berhasil dihapus dari katalog.');
    }

    /**
     * Mengubah cepat status ketersediaan stok (Tersedia/Habis) atau status tampil di katalog.
     */
    public function toggleStatus(Request $request, Product $product): RedirectResponse
    {
        // 1. Tentukan kolom status yang hendak dibalik (stok tersedia atau visibilitas aktif)
        $field = $request->input('field', 'is_available');
        if ($field === 'is_active') {
            $product->update(['is_active' => ! $product->is_active]);
            $label = $product->is_active ? 'diaktifkan di katalog' : 'disembunyikan dari katalog';
        } else {
            $product->update(['is_available' => ! $product->is_available]);
            $label = $product->is_available ? 'ditandai Tersedia' : 'ditandai Habis';
        }

        return back()->with('success', $product->name.' berhasil '.$label.'.');
    }

    /**
     * Menghapus satu foto dari galeri produk dan memperbarui thumbnail jika perlu.
     */
    public function deleteImage(ProductImage $image): RedirectResponse
    {
        // 1. Hapus berkas gambar dari disk storage apabila merupakan hasil unggahan admin
        $product = $image->product;
        if (str_starts_with($image->image_path, 'storage/')) {
            Storage::disk('public')->delete(substr($image->image_path, 8));
        }

        $wasPrimary = $image->is_primary;
        $image->delete();

        // 2. Jika foto utama dihapus, pilih foto pertama yang tersisa sebagai thumbnail baru
        if ($product && $wasPrimary) {
            $nextImage = $product->images()->first();
            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
                $product->update(['thumbnail' => $nextImage->image_path]);
            } else {
                $product->update(['thumbnail' => null]);
            }
        }

        return back()->with('success', 'Foto produk berhasil dihapus.');
    }

    /**
     * Menghasilkan slug URL produk yang dijamin unik.
     */
    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'produk';
        $slug = $baseSlug;
        $counter = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }
}
