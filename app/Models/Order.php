<?php

namespace App\Models;

use App\Notifications\OrderLifecycleNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

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
     * Memeriksa batas waktu pembayaran secara atomik agar aman terhadap eksekusi paralel.
     */
    public function expireIfPastDeadline(): bool
    {
        if (
            $this->order_status !== 'menunggu_pembayaran'
            || $this->payment_status !== 'belum_dibayar'
            || ! $this->payment_deadline_at
            || ! now()->gt($this->payment_deadline_at)
        ) {
            return false;
        }

        $expiredOrder = DB::transaction(function (): ?self {
            $lockedOrder = static::query()->lockForUpdate()->find($this->id);

            if (
                ! $lockedOrder
                || $lockedOrder->order_status !== 'menunggu_pembayaran'
                || $lockedOrder->payment_status !== 'belum_dibayar'
                || ! $lockedOrder->payment_deadline_at
                || ! now()->gt($lockedOrder->payment_deadline_at)
            ) {
                return null;
            }

            $setting = StoreSetting::current();
            $expiredOrderStatus = $setting->auto_cancel_status === 'dibatalkan' ? 'dibatalkan' : 'kedaluwarsa';

            $lockedOrder->update([
                'order_status' => $expiredOrderStatus,
                'payment_status' => 'kedaluwarsa',
                'cancelled_at' => now(),
                'cancelled_by' => 'system',
                'cancellation_reason' => 'Batas waktu pembayaran telah berakhir.',
            ]);

            if ($latestPayment = $lockedOrder->latestPayment) {
                $latestPayment->update(['status' => 'expire']);
            }

            $lockedOrder->logs()->create([
                'actor_name' => 'Sistem',
                'event_type' => 'status_changed',
                'title' => 'Pesanan Kedaluwarsa Otomatis',
                'description' => 'Batas waktu pembayaran berakhir tanpa pembayaran terkonfirmasi.',
            ]);

            if ($lockedOrder->user_id) {
                UserNotification::create([
                    'user_id' => $lockedOrder->user_id,
                    'order_id' => $lockedOrder->id,
                    'audience' => 'customer',
                    'category' => 'pesanan',
                    'channel' => 'dashboard',
                    'title' => 'Pesanan Kedaluwarsa',
                    'message' => 'Pesanan '.$lockedOrder->order_number.' melewati batas pembayaran dan ditutup otomatis.',
                    'delivery_status' => 'terkirim',
                    'action_url' => route('orders.show', $lockedOrder->order_number),
                ]);
            }

            UserNotification::create([
                'user_id' => null,
                'order_id' => $lockedOrder->id,
                'audience' => 'admin',
                'category' => 'pesanan',
                'channel' => 'dashboard',
                'title' => 'Pesanan Kedaluwarsa Otomatis',
                'message' => $lockedOrder->order_number.' · '.$lockedOrder->customer_name.' melewati batas bayar dan dibatalkan otomatis oleh sistem.',
                'delivery_status' => 'terkirim',
                'action_url' => route('admin.orders.show', $lockedOrder),
            ]);

            return $lockedOrder;
        });

        if (! $expiredOrder) {
            return false;
        }

        $this->refresh();

        if (! empty($expiredOrder->customer_email)) {
            try {
                Notification::route('mail', $expiredOrder->customer_email)->notify(
                    new OrderLifecycleNotification(
                        $expiredOrder,
                        'Pesanan '.$expiredOrder->order_number.' Kedaluwarsa — Brownies Kelana',
                        'Batas waktu pembayaran untuk pesanan ini telah berakhir.',
                        'Pesanan Anda ditutup otomatis karena tidak ada pembayaran terkonfirmasi sebelum tenggat waktu. Anda dapat membuat pesanan baru kapan saja melalui katalog kami.',
                        'Lihat Detail Pesanan',
                        route('orders.show', $expiredOrder->order_number)
                    )
                );
            } catch (Throwable $exception) {
                Log::warning('Gagal mengirim email kedaluwarsa pesanan '.$expiredOrder->order_number.': '.$exception->getMessage());
            }
        }

        return true;
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
     * Menghasilkan nomor pesanan berurutan yang aman dari duplikasi dengan format BC-XXXX.
     */
    public static function generateOrderNumber(): string
    {
        $lastOrder = static::query()->lockForUpdate()->orderByDesc('id')->first();
        $nextNumber = 1040;

        if ($lastOrder && preg_match('/(\d+)$/', $lastOrder->order_number, $matches)) {
            $nextNumber = max(1040, ((int) $matches[1]) + 1);
        } elseif ($lastOrder) {
            $nextNumber = 1040 + $lastOrder->id;
        }

        while (static::query()->where('order_number', 'BC-'.$nextNumber)->exists()) {
            $nextNumber++;
        }

        return 'BC-'.$nextNumber;
    }
}
