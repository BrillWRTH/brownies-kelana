<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom profil pelanggan dan role admin pada tabel users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('customer_code')->nullable()->unique()->after('id');
            $table->string('phone')->nullable()->after('email');
            $table->string('secondary_phone')->nullable()->after('phone');
            $table->text('saved_address')->nullable()->after('secondary_phone');
            $table->string('saved_address_detail')->nullable()->after('saved_address');
            $table->decimal('saved_latitude', 10, 7)->nullable()->after('saved_address_detail');
            $table->decimal('saved_longitude', 10, 7)->nullable()->after('saved_latitude');
            $table->string('role')->default('customer')->index()->after('saved_longitude');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('deactivated_reason')->nullable()->after('is_active');
            $table->timestamp('deactivated_at')->nullable()->after('deactivated_reason');
        });
    }

    /**
     * Mengembalikan perubahan kolom pada tabel users.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_code',
                'phone',
                'secondary_phone',
                'saved_address',
                'saved_address_detail',
                'saved_latitude',
                'saved_longitude',
                'role',
                'is_active',
                'deactivated_reason',
                'deactivated_at',
            ]);
        });
    }
};
