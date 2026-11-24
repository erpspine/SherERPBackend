<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('per_diem_payment_lines', function (Blueprint $table): void {
            $table->decimal('requested_amount', 12, 2)->nullable()->after('amount');
            $table->text('approval_reason')->nullable()->after('approved_at');
            $table->string('payment_proof_path', 255)->nullable()->after('exported_at');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete()->after('payment_proof_path');
            $table->timestamp('paid_at')->nullable()->after('paid_by');
        });
    }

    public function down(): void
    {
        Schema::table('per_diem_payment_lines', function (Blueprint $table): void {
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['requested_amount', 'approval_reason', 'payment_proof_path', 'paid_by', 'paid_at']);
        });
    }
};
