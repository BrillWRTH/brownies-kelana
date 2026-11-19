<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'gateway',
        'payment_method',
        'channel_label',
        'midtrans_order_id',
        'snap_token',
        'snap_redirect_url',
        'transaction_id',
        'va_number',
        'va_bank',
        'bill_key',
        'biller_code',
        'qris_url',
        'qris_string',
        'proof_path',
        'proof_uploaded_at',
        'sender_name',
        'customer_payment_note',
        'amount',
        'status',
        'expires_at',
        'paid_at',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'raw_response',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'proof_uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    /**
     * Relasi ke pesanan induk.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relasi ke admin yang memverifikasi atau menolak bukti pembayaran.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
