<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel notifikasi dashboard untuk pelanggan dan inbox admin.
     */
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('audience')->default('customer')->index(); // customer, admin
            $table->string('category')->default('pesanan'); // pesanan, sistem
            $table->string('channel')->default('dashboard'); // dashboard, whatsapp_manual
            $table->string('title');
            $table->text('message');
            $table->string('delivery_status')->default('terkirim'); // terkirim, dicatat_admin, belum_dikirim
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel notifikasi dashboard.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
