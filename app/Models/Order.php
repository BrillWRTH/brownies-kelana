<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'source',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_secondary_phone',
        'delivery_type',
        'pickup_location_id',
        'recipient_name',
        'recipient_phone',
        'recipient_secondary_phone',
        'delivery_address',
        'delivery_address_detail',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_distance_km',
        'shipping_zone_id',
        'shipping_method_snapshot',
        'maps_url',
        'scheduled_date',
        'scheduled_time_slot',
        'subtotal',
        'shipping_fee',
        'total_amount',
        'customer_note',
        'order_status',
        'payment_status',
        'courier_name',
        'courier_phone',
        'courier_vehicle_plate',
        'delivery_proof_path',
        'received_by',
        'delivered_at',
        'payment_deadline_at',
        'paid_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
            'delivery_distance_km' => 'float',
            'scheduled_date' => 'date',
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'total_amount' => 'integer',
            'delivered_at' => 'datetime',
            'payment_deadline_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke akun pelanggan pemilik pesanan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke titik pickup apabila metode pengambilan dipilih.
     */
    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(PickupLocation::class);
    }

    /**
     * Relasi ke zona pengiriman apabila ongkir zona digunakan.
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    /**
     * Relasi ke rincian item produk dalam pesanan.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relasi ke riwayat perubahan dan aktivitas pesanan.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class)->latest('id');
    }

    /**
     * Relasi ke data pembayaran Midtrans pesanan.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /**
     * Relasi ke transaksi pembayaran terbaru pesanan.
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Relasi ke ulasan pesanan dari pelanggan.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Memeriksa apakah pesanan masih boleh dibatalkan oleh pelanggan (sebelum diproses).
     */
    public function canBeCancelledByCustomer(): bool
    {
        return $this->order_status === 'menunggu_pembayaran';
    }

    /**
     * Memeriksa apakah pesanan sudah selesai dan dapat diulas.
     */
    public function isCompleted(): bool
    {
        return in_array($this->order_status, ['sampai_tujuan', 'sudah_diambil'], true);
    }

    /**
     * Memeriksa apakah batas waktu pembayaran telah terlewati tanpa pelunasan.
     */
    public function expireIfPastDeadline(): bool
    {
        if (
            $this->order_status === 'menunggu_pembayaran'
            && $this->payment_status === 'belum_dibayar'
            && $this->payment_deadline_at
            && now()->gt($this->payment_deadline_at)
        ) {
            $setting = StoreSetting::current();
            $expiredOrderStatus = $setting->auto_cancel_status === 'dibatalkan' ? 'dibatalkan' : 'kedaluwarsa';

            $this->update([
                'order_status' => $expiredOrderStatus,
                'payment_status' => 'kedaluwarsa',
                'cancelled_at' => now(),
                'cancelled_by' => 'system',
                'cancellation_reason' => 'Batas waktu pembayaran telah berakhir.',
            ]);

            if ($latestPayment = $this->latestPayment) {
                $latestPayment->update(['status' => 'expire']);
            }

            $this->logs()->create([
                'actor_name' => 'Sistem',
                'event_type' => 'status_changed',
                'title' => 'Pesanan Kedaluwarsa Otomatis',
                'description' => 'Batas waktu pembayaran berakhir tanpa pembayaran terkonfirmasi.',
            ]);

            if ($this->user_id) {
                UserNotification::create([
                    'user_id' => $this->user_id,
                    'order_id' => $this->id,
                    'audience' => 'customer',
                    'category' => 'pesanan',
                    'channel' => 'dashboard',
                    'title' => 'Pesanan Kedaluwarsa',
                    'message' => 'Pesanan '.$this->order_number.' melewati batas pembayaran 30 menit dan ditutup otomatis.',
                    'delivery_status' => 'terkirim',
                    'action_url' => route('orders.show', $this->order_number),
                ]);
            }

            UserNotification::create([
                'user_id' => null,
                'order_id' => $this->id,
                'audience' => 'admin',
                'category' => 'pesanan',
                'channel' => 'dashboard',
                'title' => 'Pesanan Kedaluwarsa Otomatis',
                'message' => $this->order_number.' · '.$this->customer_name.' melewati batas bayar dan dibatalkan otomatis oleh sistem.',
                'delivery_status' => 'terkirim',
                'action_url' => route('admin.orders.show', $this),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Mengembalikan label Bahasa Indonesia untuk status pesanan.
     */
    public function orderStatusLabel(): string
    {
        return match ($this->order_status) {
            'menunggu_pembayaran' => 'Menunggu Pembayaran',
            'diproses' => 'Diproses',
            'dikemas' => 'Dikemas',
            'dalam_pengiriman' => 'Dalam Pengiriman',
            'siap_diambil' => 'Siap Diambil',
            'sampai_tujuan' => 'Sampai Tujuan',
            'sudah_diambil' => 'Sudah Diambil',
            'dibatalkan' => 'Dibatalkan',
            'kedaluwarsa' => 'Kedaluwarsa',
            default => ucfirst(str_replace('_', ' ', $this->order_status)),
        };
    }

    /**
     * Mengembalikan label Bahasa Indonesia untuk status pembayaran.
     */
    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'belum_dibayar' => 'Belum Dibayar',
            'lunas' => 'Lunas',
            'gagal' => 'Gagal',
            'kedaluwarsa' => 'Kedaluwarsa',
            'dibatalkan' => 'Dibatalkan',
            'refund' => 'Refund',
            default => ucfirst(str_replace('_', ' ', $this->payment_status)),
        };
    }

    /**
     * Menghasilkan nomor pesanan berurutan dengan format BC-XXXX.
     */
    public static function generateOrderNumber(): string
    {
        $lastOrder = static::query()->orderByDesc('id')->first();
        $nextNumber = 1040;

        if ($lastOrder && preg_match('/(\d+)$/', $lastOrder->order_number, $matches)) {
            $nextNumber = max(1040, ((int) $matches[1]) + 1);
        } elseif ($lastOrder) {
            $nextNumber = 1040 + $lastOrder->id;
        }

        return 'BC-'.$nextNumber;
    }
}
