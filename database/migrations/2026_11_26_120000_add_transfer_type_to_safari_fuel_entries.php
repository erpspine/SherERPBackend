<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('safari_fuel_entries', function (Blueprint $table): void {
            $table->string('entry_type', 20)->default('allocation')->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('safari_fuel_entries', function (Blueprint $table): void {
            $table->dropColumn('entry_type');
        });
    }
};
