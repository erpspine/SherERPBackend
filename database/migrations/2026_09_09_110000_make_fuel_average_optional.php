<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('safari_fuel_entries', function (Blueprint $table): void {
            $table->decimal('expected_average', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('safari_fuel_entries', function (Blueprint $table): void {
            $table->decimal('expected_average', 10, 2)->nullable(false)->change();
        });
    }
};
