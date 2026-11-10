<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel pengaturan toko, operasional, kuota, ongkir, dan timeout.
     */
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('store_name')->default('Brownies Kelana');
            $table->string('tagline')->default('DIBUAT UNTUK DINIKMATI');
            $table->text('about_story')->nullable();
            $table->string('whatsapp_number')->default('+62 812-3185-8544');
            $table->string('email')->default('brownieskelana@gmail.com');
            $table->string('instagram_url')->nullable();
            $table->string('e_catalog_url')->nullable();
            $table->text('store_address');
            $table->decimal('store_latitude', 10, 7)->default(-7.3728210);
            $table->decimal('store_longitude', 10, 7)->default(112.7196810);
            $table->string('google_maps_url')->nullable();
            $table->unsignedTinyInteger('preorder_min_days')->default(1);
            $table->unsignedInteger('daily_quota')->default(50);
            $table->string('shipping_method')->default('flat'); // flat, zone, distance
            $table->unsignedBigInteger('flat_shipping_fee')->default(15000);
            $table->decimal('distance_base_km', 6, 2)->default(3.00);
            $table->unsignedBigInteger('distance_base_fee')->default(10000);
            $table->unsignedBigInteger('distance_per_km_fee')->default(2500);
            $table->decimal('distance_max_km', 6, 2)->default(25.00);
            $table->unsignedSmallInteger('payment_timeout_minutes')->default(30);
            $table->string('auto_cancel_status')->default('kedaluwarsa');
            $table->json('operating_hours')->nullable();
            $table->json('delivery_slots')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel pengaturan toko.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
