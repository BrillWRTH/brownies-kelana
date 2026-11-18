<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GosendCalibration extends Model
{
    use HasFactory;

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'service_type',
        'origin_address',
        'destination_address',
        'origin_latitude',
        'origin_longitude',
        'destination_latitude',
        'destination_longitude',
        'distance_km',
        'actual_gosend_price',
        'estimated_internal_price',
        'price_difference',
        'notes',
        'created_by',
    ];

    /**
     * Casting tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'origin_latitude' => 'float',
            'origin_longitude' => 'float',
            'destination_latitude' => 'float',
            'destination_longitude' => 'float',
            'distance_km' => 'float',
            'actual_gosend_price' => 'integer',
            'estimated_internal_price' => 'integer',
            'price_difference' => 'integer',
        ];
    }

    /**
     * Relasi ke admin pembuat data kalibrasi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

