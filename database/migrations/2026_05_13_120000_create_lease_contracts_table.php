<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('contract_no', 80)->unique();
            $table->string('client_name', 150);
            $table->enum('lease_type', ['Daily Lease', 'Short-Term Lease', 'Long-Term Lease']);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('duration_days')->nullable();
            $table->decimal('monthly_rate', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('Active');
            $table->timestamps();
        });

        Schema::create('lease_contract_vehicle', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lease_contract_id')->constrained('lease_contracts')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lease_contract_id', 'vehicle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_contract_vehicle');
        Schema::dropIfExists('lease_contracts');
    }
};
