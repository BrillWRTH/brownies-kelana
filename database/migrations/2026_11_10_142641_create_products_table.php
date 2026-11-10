<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel produk Brownies Kelana.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('product_code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description');
            $table->string('allergen_info')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_k3mart')->default(false);
            $table->boolean('is_weekend_only')->default(false);
            $table->boolean('allows_custom_note')->default(false);
            $table->unsignedSmallInteger('custom_note_max_chars')->default(30);
            $table->string('thumbnail')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel produk.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
