<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom bukti pembayaran QRIS dan verifikasi manual admin pada tabel payments.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'proof_path')) {
                $table->string('proof_path')->nullable()->after('qris_string');
            }
            if (! Schema::hasColumn('payments', 'proof_uploaded_at')) {
                $table->timestamp('proof_uploaded_at')->nullable()->after('proof_path');
            }
            if (! Schema::hasColumn('payments', 'sender_name')) {
                $table->string('sender_name', 150)->nullable()->after('proof_uploaded_at');
            }
            if (! Schema::hasColumn('payments', 'customer_payment_note')) {
                $table->text('customer_payment_note')->nullable()->after('sender_name');
            }
            if (! Schema::hasColumn('payments', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
            if (! Schema::hasColumn('payments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('verified_at');
            }
        });
    }

    /**
     * Menghapus kolom tambahan bukti pembayaran QRIS.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'verified_by')) {
                $table->dropConstrainedForeignId('verified_by');
            }

            $columns = [
                'proof_path',
                'proof_uploaded_at',
                'sender_name',
                'customer_payment_note',
                'verified_at',
                'rejection_reason',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
