<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('service_center', 255)->nullable();
            $table->string('service_type', 255)->nullable();
            $table->date('service_date_out');
            $table->date('service_date_in')->nullable();
            $table->unsignedInteger('odometer_out');
            $table->unsignedInteger('odometer_in')->nullable();
            $table->unsignedTinyInteger('fuel_out');
            $table->unsignedTinyInteger('fuel_in')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('In Service');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_services');
    }
};
