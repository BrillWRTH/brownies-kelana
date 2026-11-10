<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel pembayaran pesanan via Midtrans.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->default('midtrans');
            $table->string('payment_method')->default('midtrans_snap');
            $table->string('channel_label')->nullable();
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('snap_token')->nullable();
            $table->string('snap_redirect_url')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('va_number')->nullable();
            $table->string('va_bank')->nullable();
            $table->string('bill_key')->nullable();
            $table->string('biller_code')->nullable();
            $table->text('qris_url')->nullable();
            $table->text('qris_string')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel pembayaran pesanan.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
