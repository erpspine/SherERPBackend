<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the first row and remove only exact duplicates before adding
        // the database-level guard.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                DELETE duplicate
                FROM safari_allocations AS duplicate
                INNER JOIN safari_allocations AS original
                    ON original.lead_id = duplicate.lead_id
                    AND original.vehicle_id = duplicate.vehicle_id
                    AND original.start_date = duplicate.start_date
                    AND original.end_date = duplicate.end_date
                    AND original.id < duplicate.id
            SQL);
        } else {
            DB::table('safari_allocations')
                ->select('lead_id', 'vehicle_id', 'start_date', 'end_date')
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->groupBy('lead_id', 'vehicle_id', 'start_date', 'end_date')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->each(function (object $group): void {
                    $duplicateIds = DB::table('safari_allocations')
                        ->where('lead_id', $group->lead_id)
                        ->where('vehicle_id', $group->vehicle_id)
                        ->where('start_date', $group->start_date)
                        ->where('end_date', $group->end_date)
                        ->orderBy('id')
                        ->pluck('id')
                        ->slice(1);

                    DB::table('safari_allocations')->whereIn('id', $duplicateIds)->delete();
                });
        }

        Schema::table('safari_allocations', function (Blueprint $table): void {
            $table->unique(
                ['lead_id', 'vehicle_id', 'start_date', 'end_date'],
                'safari_allocations_unique_vehicle_dates',
            );
        });
    }

    public function down(): void
    {
        Schema::table('safari_allocations', function (Blueprint $table): void {
            $table->dropUnique('safari_allocations_unique_vehicle_dates');
        });
    }
};
