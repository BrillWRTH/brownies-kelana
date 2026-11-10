<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'custom_note',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * Relasi ke keranjang induk.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relasi ke data produk.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi ke varian produk yang dipilih.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Mengambil harga satuan berdasarkan varian atau harga dasar produk.
     */
    public function unitPrice(): int
    {
        if ($this->variant) {
            return (int) $this->variant->price;
        }

        return (int) ($this->product?->base_price ?? 0);
    }

    /**
     * Menghitung subtotal harga untuk baris item ini.
     */
    public function lineSubtotal(): int
    {
        return $this->unitPrice() * (int) $this->quantity;
    }
}
