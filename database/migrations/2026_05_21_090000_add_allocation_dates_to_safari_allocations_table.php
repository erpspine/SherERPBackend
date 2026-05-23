<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('safari_allocations', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('driver_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->index(['vehicle_id', 'start_date', 'end_date'], 'safari_allocations_vehicle_date_idx');
        });

        DB::table('safari_allocations as sa')
            ->join('leads as l', 'l.id', '=', 'sa.lead_id')
            ->update([
                'sa.start_date' => DB::raw('DATE(l.start_date)'),
                'sa.end_date' => DB::raw('DATE(l.end_date)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safari_allocations', function (Blueprint $table) {
            $table->dropIndex('safari_allocations_vehicle_date_idx');
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
