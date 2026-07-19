<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odometer_logs', function (Blueprint $table): void {
            $table->string('fuel_fill_type', 20)->nullable()->after('entry_type');
            $table->index('fuel_fill_type');
        });
    }

    public function down(): void
    {
        Schema::table('odometer_logs', function (Blueprint $table): void {
            $table->dropIndex(['fuel_fill_type']);
            $table->dropColumn('fuel_fill_type');
        });
    }
};
