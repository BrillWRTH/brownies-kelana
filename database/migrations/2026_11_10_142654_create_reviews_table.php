<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel ulasan pelanggan untuk pesanan yang telah selesai.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->string('moderation_status')->default('menunggu_persetujuan')->index();
            $table->string('hide_reason')->nullable();
            $table->boolean('is_featured_home')->default(false);
            $table->text('admin_reply')->nullable();
            $table->timestamp('admin_replied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel ulasan pelanggan.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
