<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('incident_reports', 'driver_user_id')) {
                $table->unsignedBigInteger('driver_user_id')->nullable()->after('vehicle_id');
            }
            if (! Schema::hasColumn('incident_reports', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('driver_user_id');
            }
            if (! Schema::hasColumn('incident_reports', 'driver_email')) {
                $table->string('driver_email')->nullable()->after('driver_name');
            }
            if (! Schema::hasColumn('incident_reports', 'driver_phone')) {
                $table->string('driver_phone')->nullable()->after('driver_email');
            }
        });

        DB::table('incident_reports')
            ->orderBy('id')
            ->chunkById(100, function ($reports): void {
                foreach ($reports as $report) {
                    $driverId = null;

                    if (! empty($report->lease_allocation_id)) {
                        $driverId = DB::table('lease_allocations')
                            ->where('id', $report->lease_allocation_id)
                            ->value('driver_id');
                    }

                    if ($driverId === null && ! empty($report->vehicle_id)) {
                        $driverId = DB::table('vehicles')
                            ->where('id', $report->vehicle_id)
                            ->value('assigned_driver_id');
                    }

                    if ($driverId === null) {
                        continue;
                    }

                    $driver = DB::table('users')
                        ->where('id', $driverId)
                        ->first(['id', 'name', 'email', 'phone']);

                    if ($driver === null) {
                        continue;
                    }

                    DB::table('incident_reports')
                        ->where('id', $report->id)
                        ->update([
                            'driver_user_id' => $driver->id,
                            'driver_name' => $driver->name,
                            'driver_email' => $driver->email,
                            'driver_phone' => $driver->phone,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('incident_reports', function (Blueprint $table): void {
            foreach (['driver_phone', 'driver_email', 'driver_name', 'driver_user_id'] as $column) {
                if (Schema::hasColumn('incident_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
