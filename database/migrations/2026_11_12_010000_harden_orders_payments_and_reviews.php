<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Memperkuat kapasitas kolom URL, indeks transaksi payment, dan integritas unik ulasan per pesanan.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->text('maps_url')->nullable()->change();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->text('snap_redirect_url')->nullable()->change();
            $table->index('transaction_id', 'payments_transaction_id_index');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->unique('order_id', 'reviews_order_unique');
        });
    }

    /**
     * Mengembalikan perubahan penguatan kolom dan indeks database.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropUnique('reviews_order_unique');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_transaction_id_index');
            $table->string('snap_redirect_url')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('maps_url')->nullable()->change();
        });
    }
};
