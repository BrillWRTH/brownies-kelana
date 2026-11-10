<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'order_note',
    ];

    /**
     * Relasi ke pengguna pemilik keranjang.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke item-item produk di keranjang.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('id');
    }

    /**
     * Menghitung total kuantitas seluruh item di keranjang.
     */
    public function totalQuantity(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /**
     * Menghitung subtotal harga seluruh item di keranjang.
     */
    public function subtotalAmount(): int
    {
        return (int) $this->items->sum(fn (CartItem $item): int => $item->lineSubtotal());
    }
}
