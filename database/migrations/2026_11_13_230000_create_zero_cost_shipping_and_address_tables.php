<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk tabel alamat terstruktur, kalibrasi GoSend, dan snapshot ongkir.
     */
    public function up(): void
    {
        // 1. Tabel Alamat Tersimpan Pelanggan (Struktur Alamat Lengkap & Titik GPS)
        if (! Schema::hasTable('user_addresses')) {
            Schema::create('user_addresses', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('label')->default('Rumah'); // Rumah, Kantor, Toko, Lainnya
                $table->string('recipient_name');
                $table->string('recipient_phone', 25);
                $table->text('full_address');
                $table->string('street')->nullable();
                $table->string('house_number', 50)->nullable();
                $table->string('rt_rw', 50)->nullable();
                $table->string('village', 100)->nullable(); // Kelurahan / Desa
                $table->string('district', 100)->nullable(); // Kecamatan
                $table->string('city', 100)->nullable(); // Kota / Kabupaten (Surabaya, Sidoarjo, dsb.)
                $table->string('province', 100)->nullable()->default('Jawa Timur');
                $table->string('postal_code', 20)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->text('location_notes')->nullable(); // Petunjuk kurir: pagar hitam, seberang masjid, dll
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // 2. Tabel Data Kalibrasi GoSend (Pembanding Harga Riil GoSend vs Estimator Internal)
        if (! Schema::hasTable('gosend_calibrations')) {
            Schema::create('gosend_calibrations', function (Blueprint $table): void {
                $table->id();
                $table->date('date')->index();
                $table->string('service_type')->default('Instant'); // Instant, SameDay
                $table->string('origin_address');
                $table->string('destination_address');
                $table->decimal('origin_latitude', 10, 7);
                $table->decimal('origin_longitude', 10, 7);
                $table->decimal('destination_latitude', 10, 7);
                $table->decimal('destination_longitude', 10, 7);
                $table->decimal('distance_km', 8, 2);
                $table->unsignedBigInteger('actual_gosend_price'); // Harga riil di aplikasi GoSend
                $table->unsignedBigInteger('estimated_internal_price')->nullable(); // Harga kalkulasi internal saat itu
                $table->integer('price_difference')->nullable(); // Selisih (aktual - estimasi)
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. Tambahkan kolom pendukung estimator dan safety margin pada store_settings
        Schema::table('store_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('store_settings', 'safety_margin_percentage')) {
                $table->unsignedTinyInteger('safety_margin_percentage')->default(0)->after('distance_per_km_fee');
            }
            if (! Schema::hasColumn('store_settings', 'safety_margin_flat')) {
                $table->unsignedBigInteger('safety_margin_flat')->default(0)->after('safety_margin_percentage');
            }
            if (! Schema::hasColumn('store_settings', 'shipping_rounding')) {
                $table->unsignedSmallInteger('shipping_rounding')->default(500)->after('safety_margin_flat');
            }
            if (! Schema::hasColumn('store_settings', 'shipping_service_name')) {
                $table->string('shipping_service_name')->default('Kurir Kelana / GoSend')->after('shipping_rounding');
            }
        });

        // 4. Tambahkan snapshot quotation rincian ongkir pada orders
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'shipping_quotation_snapshot')) {
                $table->json('shipping_quotation_snapshot')->nullable()->after('shipping_method_snapshot');
            }
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'shipping_quotation_snapshot')) {
                $table->dropColumn('shipping_quotation_snapshot');
            }
        });

        Schema::table('store_settings', function (Blueprint $table): void {
            $cols = ['safety_margin_percentage', 'safety_margin_flat', 'shipping_rounding', 'shipping_service_name'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('store_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('gosend_calibrations');
        Schema::dropIfExists('user_addresses');
    }
};
