<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'product_id',
        'rating',
        'comment',
        'moderation_status',
        'hide_reason',
        'is_featured_home',
        'admin_reply',
        'admin_replied_at',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_featured_home' => 'boolean',
            'admin_replied_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke pesanan asal ulasan.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relasi ke pelanggan pemberi ulasan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke produk utama yang diulas.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
