<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'saved_maps_url')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->text('saved_maps_url')->nullable()->after('saved_address_detail');
            });
        }

        if (Schema::hasTable('user_addresses') && ! Schema::hasColumn('user_addresses', 'maps_url')) {
            Schema::table('user_addresses', function (Blueprint $table): void {
                $table->text('maps_url')->nullable()->after('location_notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'saved_maps_url')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('saved_maps_url');
            });
        }

        if (Schema::hasTable('user_addresses') && Schema::hasColumn('user_addresses', 'maps_url')) {
            Schema::table('user_addresses', function (Blueprint $table): void {
                $table->dropColumn('maps_url');
            });
        }
    }
};
