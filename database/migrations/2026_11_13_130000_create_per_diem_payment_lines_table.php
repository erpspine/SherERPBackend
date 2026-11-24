<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('per_diem_payment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('per_diem_voucher_id')->constrained('per_diem_vouchers')->cascadeOnDelete();
            $table->string('stage', 20);
            $table->decimal('amount', 12, 2);
            $table->decimal('fuel_deduction', 12, 2)->default(0);
            $table->text('deduction_reason')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('payment_reference', 100)->nullable()->unique();
            $table->string('payment_batch_reference', 100)->nullable()->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();
            $table->unique(['per_diem_voucher_id', 'stage']);
        });

        DB::table('per_diem_vouchers')->orderBy('id')->each(function (object $voucher): void {
            $snapshot = json_decode($voucher->snapshot ?: '{}', true) ?: [];
            $total = round((float) ($snapshot['total'] ?? 0), 2);
            $contractType = strtolower(trim((string) ($snapshot['contractType'] ?? '')));
            $status = $voucher->payment_status ?: 'draft';

            if ($contractType === 'freelance') {
                foreach ([['advance', round($total / 2, 2)], ['balance', round($total - round($total / 2, 2), 2)]] as [$stage, $amount]) {
                    DB::table('per_diem_payment_lines')->insert([
                        'per_diem_voucher_id' => $voucher->id,
                        'stage' => $stage,
                        'amount' => $amount,
                        'status' => $status,
                        'payment_reference' => $voucher->payment_reference ? $voucher->payment_reference . '-' . strtoupper(substr($stage, 0, 3)) : null,
                        'payment_batch_reference' => $voucher->payment_batch_reference,
                        'approved_at' => $voucher->approved_at,
                        'exported_at' => $voucher->exported_at,
                        'created_at' => $voucher->created_at,
                        'updated_at' => $voucher->updated_at,
                    ]);
                }
            } else {
                DB::table('per_diem_payment_lines')->insert([
                    'per_diem_voucher_id' => $voucher->id,
                    'stage' => 'full',
                    'amount' => $total,
                    'status' => $status,
                    'payment_reference' => $voucher->payment_reference,
                    'payment_batch_reference' => $voucher->payment_batch_reference,
                    'approved_at' => $voucher->approved_at,
                    'exported_at' => $voucher->exported_at,
                    'created_at' => $voucher->created_at,
                    'updated_at' => $voucher->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('per_diem_payment_lines');
    }
};
