<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table): void {
            if (! Schema::hasColumn('inspections', 'parking_location')) {
                $table->string('parking_location')->nullable()->after('odometer_in');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table): void {
            if (Schema::hasColumn('inspections', 'parking_location')) {
                $table->dropColumn('parking_location');
            }
        });
    }
};
