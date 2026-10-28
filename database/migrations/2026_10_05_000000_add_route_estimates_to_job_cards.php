<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->json('route_points')->nullable()->after('route_itinerary');
            $table->decimal('route_distance_km', 10, 1)->nullable()->after('route_points');
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->dropColumn(['route_points', 'route_distance_km']);
        });
    }
};
