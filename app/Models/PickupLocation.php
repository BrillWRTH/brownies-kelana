<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupLocation extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'maps_url',
        'operating_info',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Relasi ke pesanan yang mengambil di lokasi pickup ini.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
