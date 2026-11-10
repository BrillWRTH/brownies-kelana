<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel zona ongkos kirim wilayah.
     */
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('area_description')->nullable();
            $table->unsignedBigInteger('fee');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel zona ongkos kirim.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
