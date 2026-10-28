<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_requisitions', function (Blueprint $table): void {
            $table->date('request_date')->nullable()->after('user_id');
        });

        // Preserve the historical submission date for records created before
        // the explicit record-date field was introduced.
        DB::table('fuel_requisitions')
            ->whereNull('request_date')
            ->update(['request_date' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('fuel_requisitions', function (Blueprint $table): void {
            $table->dropColumn('request_date');
        });
    }
};
