<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel keranjang belanja pelanggan.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->text('order_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel keranjang belanja.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
