<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('per_diem_vouchers', function (Blueprint $table): void {
            $table->timestamp('requested_for_approval_at')->nullable()->after('snapshot')->index();
        });
    }

    public function down(): void
    {
        Schema::table('per_diem_vouchers', function (Blueprint $table): void {
            $table->dropColumn('requested_for_approval_at');
        });
    }
};
