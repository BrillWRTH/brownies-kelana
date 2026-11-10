<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel pesanan pelanggan.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('website'); // website, whatsapp
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->string('customer_secondary_phone')->nullable();
            $table->string('delivery_type')->default('delivery'); // delivery, pickup
            $table->foreignId('pickup_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('recipient_secondary_phone')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('delivery_address_detail')->nullable();
            $table->decimal('delivery_latitude', 10, 7)->nullable();
            $table->decimal('delivery_longitude', 10, 7)->nullable();
            $table->decimal('delivery_distance_km', 8, 2)->nullable();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_method_snapshot')->default('flat'); // flat, zone, distance, pickup
            $table->string('maps_url')->nullable();
            $table->date('scheduled_date')->index();
            $table->string('scheduled_time_slot');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('shipping_fee')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->text('customer_note')->nullable();
            $table->string('order_status')->default('menunggu_pembayaran')->index();
            $table->string('payment_status')->default('belum_dibayar')->index();
            $table->string('courier_name')->nullable();
            $table->string('courier_phone')->nullable();
            $table->string('courier_vehicle_plate')->nullable();
            $table->string('delivery_proof_path')->nullable();
            $table->string('received_by')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('payment_deadline_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel pesanan pelanggan.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
