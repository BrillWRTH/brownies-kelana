<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel template pesan notifikasi otomatis dan manual.
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('category')->default('pesanan'); // pesanan, sistem
            $table->string('trigger_key')->unique();
            $table->string('title');
            $table->text('message_template');
            $table->json('channels')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel template pesan notifikasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
