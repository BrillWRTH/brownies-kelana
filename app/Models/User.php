<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'phone',
        'secondary_phone',
        'saved_address',
        'saved_address_detail',
        'saved_latitude',
        'saved_longitude',
        'role',
        'is_active',
        'deactivated_reason',
        'deactivated_at',
        'email_verified_at',
        'password',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'is_active' => 'boolean',
            'saved_latitude' => 'float',
            'saved_longitude' => 'float',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke keranjang aktif milik pelanggan.
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Relasi ke seluruh pesanan milik pelanggan.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    /**
     * Relasi ke seluruh ulasan yang dibuat pelanggan.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    /**
     * Relasi ke notifikasi dashboard milik pelanggan.
     */
    public function customerNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)
            ->where('audience', 'customer')
            ->latest();
    }

    /**
     * Memeriksa apakah pengguna memiliki role admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Menormalkan format nomor telepon untuk pencocokan WhatsApp dan akun.
     */
    public static function normalizePhoneNumber(?string $phone): string
    {
        if (! $phone) {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }
}
