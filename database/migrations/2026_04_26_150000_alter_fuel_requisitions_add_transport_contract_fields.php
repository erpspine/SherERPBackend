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
            $table->decimal('base_rate_per_km', 10, 4)->nullable()->after('litres');
            $table->json('transport_itinerary')->nullable()->after('reason');
            $table->decimal('total_distance_km', 10, 2)->nullable()->after('transport_itinerary');
            $table->decimal('total_fuel_litres', 10, 2)->nullable()->after('total_distance_km');
        });

        DB::table('fuel_requisitions')
            ->whereNull('total_fuel_litres')
            ->update(['total_fuel_litres' => DB::raw('litres')]);
    }

    public function down(): void
    {
        Schema::table('fuel_requisitions', function (Blueprint $table): void {
            $table->dropColumn([
                'base_rate_per_km',
                'transport_itinerary',
                'total_distance_km',
                'total_fuel_litres',
            ]);
        });
    }
};
