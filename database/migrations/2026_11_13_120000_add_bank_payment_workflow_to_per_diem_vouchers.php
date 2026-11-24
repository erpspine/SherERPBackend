<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('per_diem_vouchers', function (Blueprint $table): void {
            $table->string('payment_status', 20)->default('draft')->after('snapshot');
            $table->string('payment_reference', 80)->nullable()->unique()->after('payment_status');
            $table->string('payment_batch_reference', 80)->nullable()->index()->after('payment_reference');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('payment_batch_reference');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('exported_at')->nullable()->after('approved_at');
            $table->timestamp('paid_at')->nullable()->after('exported_at');
        });
    }

    public function down(): void
    {
        Schema::table('per_diem_vouchers', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropUnique(['payment_reference']);
            $table->dropColumn([
                'payment_status',
                'payment_reference',
                'payment_batch_reference',
                'approved_by',
                'approved_at',
                'exported_at',
                'paid_at',
            ]);
        });
    }
};
