<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->decimal('additional_distance_km', 10, 1)->nullable()->after('route_distance_km');
            $table->string('additional_distance_note', 500)->nullable()->after('additional_distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->dropColumn(['additional_distance_km', 'additional_distance_note']);
        });
    }
};
