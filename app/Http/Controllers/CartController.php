<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Menampilkan halaman penuh keranjang belanja beserta ringkasan dan catatan pesanan.
     */
    public function index(Request $request): View
    {
        // 1. Ambil atau buat keranjang sesuai pengguna login atau session tamu
        $cart = $this->resolveCurrentCart($request);
        $cart->load(['items.product.images', 'items.variant']);

        // 2. Ambil produk rekomendasi pelengkap katalog
        $recommendedProducts = Product::query()
            ->with(['variants', 'images'])
            ->where('is_active', true)
            ->where('is_available', true)
            ->where('is_k3mart', false)
            ->take(4)
            ->get();

        return view('cart.index', compact('cart', 'recommendedProducts'));
    }

    /**
     * Menambahkan produk dan varian yang dipilih ke keranjang belanja.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi data input produk, varian, kuantitas, dan catatan khusus
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'custom_note' => ['nullable', 'string', 'max:100'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        // 2. Pastikan produk aktif dan tersedia untuk dipesan
        $product = Product::query()->with('variants')->findOrFail($validated['product_id']);
        if (! $product->is_active || ! $product->is_available) {
            return back()->with('error', 'Maaf, produk ini sedang habis atau tidak tersedia.');
        }

        // 3. Validasi varian produk harus milik produk terkait dan berstatus tersedia
        $variant = null;
        if (! empty($validated['product_variant_id'])) {
            $variant = $product->variants->firstWhere('id', (int) $validated['product_variant_id']);
            if (! $variant || ! $variant->is_available) {
                return back()->with('error', 'Varian produk yang dipilih sedang habis atau tidak tersedia.');
            }
        } elseif ($product->variants->isNotEmpty()) {
            $variant = $product->variants->firstWhere('is_available', true);
            if (! $variant) {
                return back()->with('error', 'Semua varian untuk produk ini sedang habis.');
            }
        }
        $variantId = $variant?->id;

        // 4. Batasi panjang tulisan khusus sesuai pengaturan produk
        $customNote = trim((string) ($validated['custom_note'] ?? ''));
        if ($product->allows_custom_note && $customNote !== '') {
            $customNote = mb_substr($customNote, 0, $product->custom_note_max_chars ?: 30);
        } else {
            $customNote = null;
        }

        // 5. Ambil keranjang aktif dan tambahkan atau perbarui jumlah item
        $cart = $this->resolveCurrentCart($request);
        $quantity = (int) ($validated['quantity'] ?? 1);

        $existingItem = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variantId)
            ->where('custom_note', $customNote)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => min(50, $existingItem->quantity + $quantity),
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'custom_note' => $customNote,
            ]);
        }

        // 6. Jika pengguna klik Beli Sekarang, langsung arahkan ke checkout atau login
        if ($request->boolean('buy_now')) {
            if (! Auth::check()) {
                return redirect()->route('login')
                    ->with('error', 'Silakan masuk terlebih dahulu untuk melanjutkan ke halaman checkout.');
            }

            return redirect()->route('checkout.index');
        }

        return back()
            ->with('success', $product->name.' berhasil ditambahkan ke keranjang.')
            ->with('open_cart_drawer', true);
    }

    /**
     * Memperbarui jumlah item di dalam keranjang belanja.
     */
    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        // 1. Validasi kepemilikan item keranjang pengguna saat ini
        $cart = $this->resolveCurrentCart($request);
        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        // 2. Validasi perubahan kuantitas atau aksi tambah/kurang
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        // 3. Hapus item apabila kuantitas bernilai 0, atau simpan jumlah baru jika masih tersedia
        if ((int) $validated['quantity'] === 0) {
            $cartItem->delete();

            return back()->with('success', 'Item dihapus dari keranjang.');
        }

        $cartItem->loadMissing(['product', 'variant']);
        if (
            ! $cartItem->product
            || ! $cartItem->product->is_active
            || ! $cartItem->product->is_available
            || ($cartItem->variant && ! $cartItem->variant->is_available)
        ) {
            return back()->with('error', 'Produk atau varian pada item ini sedang habis/nonaktif. Silakan hapus item dari keranjang.');
        }

        $cartItem->update([
            'quantity' => (int) $validated['quantity'],
        ]);

        return back()->with('success', 'Jumlah item keranjang diperbarui.');
    }

    /**
     * Menyimpan catatan pesanan keseluruhan di halaman keranjang.
     */
    public function updateNote(Request $request): RedirectResponse
    {
        // 1. Validasi isi catatan pesanan pelanggan
        $validated = $request->validate([
            'order_note' => ['nullable', 'string', 'max:500'],
        ]);

        // 2. Simpan catatan ke data keranjang aktif
        $cart = $this->resolveCurrentCart($request);
        $cart->update([
            'order_note' => trim((string) ($validated['order_note'] ?? '')) ?: null,
        ]);

        return back()->with('success', 'Catatan pesanan disimpan.');
    }

    /**
     * Menghapus satu item dari keranjang belanja.
     */
    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        // 1. Pastikan item milik keranjang pengguna saat ini
        $cart = $this->resolveCurrentCart($request);
        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        // 2. Hapus item dari database dan kembali dengan notifikasi
        $cartItem->delete();

        return back()->with('success', 'Produk berhasil dihapus dari keranjang.');
    }

    /**
     * Mengambil atau membuat record keranjang untuk user login maupun session tamu.
     */
    protected function resolveCurrentCart(Request $request): Cart
    {
        if (Auth::check()) {
            return Cart::query()->firstOrCreate([
                'user_id' => Auth::id(),
            ]);
        }

        return Cart::query()->firstOrCreate([
            'session_id' => $request->session()->getId(),
        ]);
    }
}
