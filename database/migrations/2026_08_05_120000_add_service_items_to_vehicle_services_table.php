<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_services', function (Blueprint $table): void {
            if (! Schema::hasColumn('vehicle_services', 'service_items')) {
                $table->json('service_items')->nullable()->after('service_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_services', function (Blueprint $table): void {
            if (Schema::hasColumn('vehicle_services', 'service_items')) {
                $table->dropColumn('service_items');
            }
        });
    }
};
