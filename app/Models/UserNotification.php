<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'audience',
        'category',
        'channel',
        'title',
        'message',
        'delivery_status',
        'action_url',
        'read_at',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke akun penerima notifikasi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke pesanan terkait notifikasi.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
