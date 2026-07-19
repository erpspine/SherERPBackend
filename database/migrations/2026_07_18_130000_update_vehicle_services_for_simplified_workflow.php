<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_services', function (Blueprint $table): void {
            if (! Schema::hasColumn('vehicle_services', 'service_date')) {
                $table->date('service_date')->nullable()->after('service_type');
            }

            if (! Schema::hasColumn('vehicle_services', 'parts_replaced')) {
                $table->text('parts_replaced')->nullable()->after('service_date_in');
            }
        });

        DB::table('vehicle_services')
            ->whereNull('service_date')
            ->update(['service_date' => DB::raw('service_date_out')]);

        DB::statement('ALTER TABLE vehicle_services MODIFY service_date_out DATE NULL');
        DB::statement('ALTER TABLE vehicle_services MODIFY odometer_out INT UNSIGNED NULL');
        DB::statement('ALTER TABLE vehicle_services MODIFY fuel_out TINYINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::table('vehicle_services')
            ->whereNull('service_date_out')
            ->update(['service_date_out' => DB::raw('COALESCE(service_date, CURRENT_DATE)')]);
        DB::table('vehicle_services')->whereNull('odometer_out')->update(['odometer_out' => 0]);
        DB::table('vehicle_services')->whereNull('fuel_out')->update(['fuel_out' => 0]);

        DB::statement('ALTER TABLE vehicle_services MODIFY service_date_out DATE NOT NULL');
        DB::statement('ALTER TABLE vehicle_services MODIFY odometer_out INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE vehicle_services MODIFY fuel_out TINYINT UNSIGNED NOT NULL');

        Schema::table('vehicle_services', function (Blueprint $table): void {
            if (Schema::hasColumn('vehicle_services', 'parts_replaced')) {
                $table->dropColumn('parts_replaced');
            }

            if (Schema::hasColumn('vehicle_services', 'service_date')) {
                $table->dropColumn('service_date');
            }
        });
    }
};
