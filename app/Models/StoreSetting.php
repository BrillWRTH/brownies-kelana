<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_name',
        'tagline',
        'about_story',
        'whatsapp_number',
        'email',
        'instagram_url',
        'e_catalog_url',
        'store_address',
        'store_latitude',
        'store_longitude',
        'google_maps_url',
        'preorder_min_days',
        'daily_quota',
        'shipping_method',
        'flat_shipping_fee',
        'distance_base_km',
        'distance_base_fee',
        'distance_per_km_fee',
        'distance_max_km',
        'payment_timeout_minutes',
        'auto_cancel_status',
        'operating_hours',
        'delivery_slots',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'store_latitude' => 'float',
            'store_longitude' => 'float',
            'preorder_min_days' => 'integer',
            'daily_quota' => 'integer',
            'flat_shipping_fee' => 'integer',
            'distance_base_km' => 'float',
            'distance_base_fee' => 'integer',
            'distance_per_km_fee' => 'integer',
            'distance_max_km' => 'float',
            'payment_timeout_minutes' => 'integer',
            'operating_hours' => 'array',
            'delivery_slots' => 'array',
        ];
    }

    /**
     * Mengambil data pengaturan toko aktif atau nilai bawaan.
     */
    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'store_name' => 'Brownies Kelana',
            'tagline' => 'DIBUAT UNTUK DINIKMATI',
            'whatsapp_number' => '+62 812-3185-8544',
            'email' => 'brownieskelana@gmail.com',
            'store_address' => 'Grand Royal Regency, Cluster Flamboyan E3-16, Jl Gadung, Wage, Taman, Sidoarjo.',
            'store_latitude' => -7.3728210,
            'store_longitude' => 112.7196810,
            'preorder_min_days' => 1,
            'daily_quota' => 50,
            'shipping_method' => 'flat',
            'flat_shipping_fee' => 15000,
            'distance_base_km' => 3.0,
            'distance_base_fee' => 10000,
            'distance_per_km_fee' => 2500,
            'distance_max_km' => 25.0,
            'payment_timeout_minutes' => 30,
            'auto_cancel_status' => 'kedaluwarsa',
            'delivery_slots' => ['09.00-11.00 WIB', '13.00-15.00 WIB'],
        ]);
    }

    /**
     * Membuat tautan WhatsApp resmi toko dalam format wa.me.
     */
    public function whatsappUrl(?string $message = null): string
    {
        $digits = preg_replace('/[^0-9]/', '', $this->whatsapp_number) ?? '6281231858544';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        $url = 'https://wa.me/'.$digits;
        if ($message) {
            $url .= '?text='.urlencode($message);
        }

        return $url;
    }

    /**
     * Menghitung jarak tempuh perkiraan jalan raya (km) menggunakan Haversine dikali koreksi jalan 1.25.
     */
    public function calculateDistanceKm(float $targetLatitude, float $targetLongitude): float
    {
        $earthRadiusKm = 6371.0;
        $latFrom = deg2rad((float) $this->store_latitude);
        $lonFrom = deg2rad((float) $this->store_longitude);
        $latTo = deg2rad($targetLatitude);
        $lonTo = deg2rad($targetLongitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        $straightKm = $angle * $earthRadiusKm;

        return round(max(0.5, $straightKm * 1.25), 2);
    }

    /**
     * Menghitung ongkos kirim sesuai metode aktif di pengaturan toko.
     *
     * @return array{fee: int, method: string, label: string, within_range: bool, distance_km: ?float}
     */
    public function calculateShippingFee(
        string $deliveryType,
        ?int $zoneId = null,
        ?float $distanceKm = null
    ): array {
        if ($deliveryType === 'pickup') {
            return [
                'fee' => 0,
                'method' => 'pickup',
                'label' => 'Ambil sendiri (pick-up)',
                'within_range' => true,
                'distance_km' => null,
            ];
        }

        if ($this->shipping_method === 'zone') {
            $zone = ShippingZone::query()->where('is_active', true)->find($zoneId);
            if (! $zone) {
                $zone = ShippingZone::query()->where('is_active', true)->orderBy('fee')->first();
            }

            return [
                'fee' => $zone ? (int) $zone->fee : (int) $this->flat_shipping_fee,
                'method' => 'zone',
                'label' => $zone ? 'Zona · '.$zone->name : 'Zona',
                'within_range' => true,
                'distance_km' => null,
            ];
        }

        if ($this->shipping_method === 'distance') {
            $jarak = $distanceKm ?? $this->distance_base_km;
            if ($jarak > $this->distance_max_km) {
                return [
                    'fee' => 0,
                    'method' => 'distance',
                    'label' => 'Di luar jangkauan maksimal (maks '.(int) $this->distance_max_km.' km)',
                    'within_range' => false,
                    'distance_km' => $jarak,
                ];
            }

            $extraKm = max(0, (int) ceil($jarak - $this->distance_base_km));
            $fee = (int) $this->distance_base_fee + ($extraKm * (int) $this->distance_per_km_fee);

            return [
                'fee' => $fee,
                'method' => 'distance',
                'label' => 'Jarak · '.number_format($jarak, 1, ',', '.').' km',
                'within_range' => true,
                'distance_km' => $jarak,
            ];
        }

        return [
            'fee' => (int) $this->flat_shipping_fee,
            'method' => 'flat',
            'label' => 'Biaya kirim · Flat',
            'within_range' => true,
            'distance_km' => null,
        ];
    }

    /**
     * Memeriksa ketersediaan tanggal pengiriman/pickup terhadap aturan H-1, jam buka, hari libur, dan kuota.
     *
     * @return array{available: bool, status: string, used_quota: int, remaining_quota: int, reason: string}
     */
    public function checkScheduleDate(string $dateString): array
    {
        $date = Carbon::parse($dateString)->startOfDay();
        $minDate = now()->startOfDay()->addDays((int) $this->preorder_min_days);

        if ($date->lt($minDate)) {
            return [
                'available' => false,
                'status' => 'past',
                'used_quota' => 0,
                'remaining_quota' => 0,
                'reason' => 'Minimal pemesanan adalah H-'.(int) $this->preorder_min_days.' sebelum jadwal.',
            ];
        }

        $dayKey = strtolower($date->locale('en')->isoFormat('dddd'));
        $hours = $this->operating_hours ?? [];
        $dayConfig = $hours[$dayKey] ?? ['is_open' => true];
        if (! ($dayConfig['is_open'] ?? false)) {
            return [
                'available' => false,
                'status' => 'closed',
                'used_quota' => 0,
                'remaining_quota' => 0,
                'reason' => 'Toko tutup pada hari tersebut.',
            ];
        }

        $holiday = Holiday::query()
            ->where('is_active', true)
            ->whereDate('holiday_date', $date->toDateString())
            ->first();

        if ($holiday) {
            return [
                'available' => false,
                'status' => 'holiday',
                'used_quota' => 0,
                'remaining_quota' => 0,
                'reason' => 'Toko libur: '.$holiday->name,
            ];
        }

        $usedQuota = Order::query()
            ->whereDate('scheduled_date', $date->toDateString())
            ->whereNotIn('order_status', ['dibatalkan', 'kedaluwarsa'])
            ->count();

        $remainingQuota = max(0, (int) $this->daily_quota - $usedQuota);

        if ($remainingQuota <= 0) {
            return [
                'available' => false,
                'status' => 'full',
                'used_quota' => $usedQuota,
                'remaining_quota' => 0,
                'reason' => 'Kuota pesanan pada tanggal ini sudah penuh ('.(int) $this->daily_quota.'/'.(int) $this->daily_quota.').',
            ];
        }

        return [
            'available' => true,
            'status' => 'available',
            'used_quota' => $usedQuota,
            'remaining_quota' => $remainingQuota,
            'reason' => 'Tersedia (sisa kuota '.$remainingQuota.' pesanan).',
        ];
    }

    /**
     * Mengembalikan ringkasan teks jam operasional toko secara dinamis.
     */
    public function operatingHoursSummary(): string
    {
        $hours = $this->operating_hours ?? [];
        if (empty($hours)) {
            return 'Senin–Selasa 09.00–16.00 WIB · Jumat–Sabtu 08.00–16.00 WIB · Rabu, Kamis & Minggu tutup';
        }

        $openParts = [];
        $closedDays = [];
        foreach ($hours as $day) {
            $label = $day['label'] ?? '';
            if ($day['is_open'] ?? false) {
                $openParts[] = $label.' '.str_replace(':', '.', ($day['open'] ?? '09:00')).'–'.str_replace(':', '.', ($day['close'] ?? '16:00')).' WIB';
            } else {
                $closedDays[] = $label;
            }
        }

        $summary = implode(' · ', $openParts);
        if (! empty($closedDays)) {
            $summary .= ' · '.implode(', ', $closedDays).' tutup';
        }

        return $summary;
    }
}
