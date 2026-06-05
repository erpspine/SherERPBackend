<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odometer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safari_allocation_id')
                ->constrained('safari_allocations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Client-supplied UUID used for idempotency between the offline
            // outbox on the driver's device and the server, so we never
            // duplicate a row when the sync worker retries.
            $table->string('client_id', 64)->nullable()->unique();

            // Start | Stop | End | Fuel
            $table->string('entry_type', 16);
            $table->string('location', 255);
            $table->unsignedBigInteger('odometer_reading');

            // Fuel-event fields (only populated when entry_type = Fuel).
            $table->decimal('liters', 10, 2)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->string('station', 255)->nullable();

            $table->text('notes')->nullable();
            $table->string('photo_path', 500)->nullable();

            // Device-local timestamp captured at the moment the driver hit
            // Save (UTC). Falls back to server `created_at` when missing.
            $table->timestamp('recorded_at')->nullable();

            $table->timestamps();

            $table->index('safari_allocation_id');
            $table->index('user_id');
            $table->index('entry_type');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odometer_logs');
    }
};
