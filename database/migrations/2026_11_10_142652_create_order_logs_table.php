<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel riwayat log dan audit perubahan pesanan.
     */
    public function up(): void
    {
        Schema::create('order_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name');
            $table->string('event_type')->default('status_changed');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel log pesanan.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_logs');
    }
};
