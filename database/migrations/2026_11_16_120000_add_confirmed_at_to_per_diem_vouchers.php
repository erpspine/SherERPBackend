<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('per_diem_vouchers', function (Blueprint $table): void {
            $table->timestamp('confirmed_at')->nullable()->after('paid_at')->index();
        });

        DB::table('per_diem_vouchers')
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->update(['confirmed_at' => DB::raw('paid_at')]);
    }

    public function down(): void
    {
        Schema::table('per_diem_vouchers', function (Blueprint $table): void {
            $table->dropIndex(['confirmed_at']);
            $table->dropColumn('confirmed_at');
        });
    }
};
