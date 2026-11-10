<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel lokasi pickup resmi.
     */
    public function up(): void
    {
        Schema::create('pickup_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->string('maps_url')->nullable();
            $table->string('operating_info')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel lokasi pickup.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_locations');
    }
};
