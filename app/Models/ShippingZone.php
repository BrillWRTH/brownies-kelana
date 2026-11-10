<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'area_description',
        'fee',
        'is_active',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
