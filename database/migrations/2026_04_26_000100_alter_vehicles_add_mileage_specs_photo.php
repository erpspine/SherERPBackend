<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->unsignedInteger('mileage')->nullable()->after('seats');
            $table->text('specs')->nullable()->after('status');
            $table->string('photo')->nullable()->after('specs');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn(['mileage', 'specs', 'photo']);
        });
    }
};
