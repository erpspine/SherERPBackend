<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table): void {
            $table->unsignedInteger('odometer_out')->nullable()->after('type');
            $table->unsignedInteger('odometer_in')->nullable()->after('odometer_out');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table): void {
            $table->dropColumn(['odometer_out', 'odometer_in']);
        });
    }
};
