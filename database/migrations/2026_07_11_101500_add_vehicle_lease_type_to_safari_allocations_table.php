<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safari_allocations', function (Blueprint $table): void {
            $table->string('vehicle_lease_type', 50)->default('Short-Term Lease')->after('driver_id');
            $table->index('vehicle_lease_type');
        });

        DB::table('safari_allocations')
            ->join('vehicles', 'vehicles.id', '=', 'safari_allocations.vehicle_id')
            ->select([
                'safari_allocations.id',
                'vehicles.lease_type',
                'vehicles.lease_start_date',
                'vehicles.lease_end_date',
            ])
            ->orderBy('safari_allocations.id')
            ->chunk(100, function ($allocations): void {
                foreach ($allocations as $allocation) {
                    $leaseType = 'Short-Term Lease';

                    if ($allocation->lease_type === 'Long-Term Lease') {
                        $leaseType = 'Long-Term Lease';
                    } elseif ($allocation->lease_start_date && $allocation->lease_end_date) {
                        $leaseDays = Carbon::parse($allocation->lease_start_date)
                            ->diffInDays(Carbon::parse($allocation->lease_end_date), true);

                        if ($leaseDays > 365) {
                            $leaseType = 'Long-Term Lease';
                        }
                    }

                    DB::table('safari_allocations')
                        ->where('id', $allocation->id)
                        ->update(['vehicle_lease_type' => $leaseType]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('safari_allocations', function (Blueprint $table): void {
            $table->dropIndex(['vehicle_lease_type']);
            $table->dropColumn('vehicle_lease_type');
        });
    }
};
