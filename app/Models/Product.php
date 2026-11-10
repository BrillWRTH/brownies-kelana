<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'product_code',
        'name',
        'slug',
        'tagline',
        'description',
        'allergen_info',
        'base_price',
        'is_available',
        'is_active',
        'is_featured',
        'is_k3mart',
        'is_weekend_only',
        'allows_custom_note',
        'custom_note_max_chars',
        'thumbnail',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_k3mart' => 'boolean',
            'is_weekend_only' => 'boolean',
            'allows_custom_note' => 'boolean',
            'custom_note_max_chars' => 'integer',
        ];
    }

    /**
     * Relasi ke kategori induk produk.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke daftar varian ukuran dan harga produk.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Relasi ke seluruh galeri foto produk.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Relasi ke ulasan pelanggan untuk produk ini.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Mengambil URL gambar utama produk dengan fallback yang aman.
     */
    public function primaryImageUrl(): string
    {
        if ($this->thumbnail) {
            return str_starts_with($this->thumbnail, 'http') || str_starts_with($this->thumbnail, '/')
                ? $this->thumbnail
                : asset($this->thumbnail);
        }

        $firstImage = $this->images->first();
        if ($firstImage) {
            return str_starts_with($firstImage->image_path, 'http') || str_starts_with($firstImage->image_path, '/')
                ? $firstImage->image_path
                : asset($firstImage->image_path);
        }

        return asset('images/kelana/prod-brownies-panggang.png');
    }
}
