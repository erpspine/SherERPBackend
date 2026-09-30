<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('safari_fuel_entries', function (Blueprint $table): void {
            $table->decimal('litres_filled_end', 10, 2)->nullable()->after('litres_filled');
        });
    }

    public function down(): void
    {
        Schema::table('safari_fuel_entries', function (Blueprint $table): void {
            $table->dropColumn('litres_filled_end');
        });
    }
};
