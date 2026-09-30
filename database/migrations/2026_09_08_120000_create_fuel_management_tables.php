<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_fuel_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('tank_capacity', 10, 2);
            $table->decimal('expected_average', 10, 2);
            $table->timestamps();
        });
        Schema::create('safari_fuel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safari_allocation_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('lease_allocation_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->date('filled_at');
            $table->decimal('litres_filled', 10, 2);
            $table->decimal('extra_litres', 10, 2)->default(0);
            $table->decimal('tank_capacity', 10, 2);
            $table->decimal('expected_average', 10, 2);
            $table->unsignedInteger('start_km');
            $table->unsignedInteger('end_km')->nullable();
            $table->timestamps();
            $table->index(['vehicle_id', 'start_km']);
            $table->unique('lease_allocation_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('safari_fuel_entries');
        Schema::dropIfExists('vehicle_fuel_profiles');
    }
};
