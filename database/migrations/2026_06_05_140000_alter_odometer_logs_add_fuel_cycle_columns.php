<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odometer_logs', function (Blueprint $table) {
            // The Fuel log that opened the tank cycle this reading belongs
            // to. Null for the Fuel logs themselves (a Fuel log opens its
            // own cycle) and for any orphan readings recorded before the
            // first ever fuel-up on the trip.
            $table->foreignId('fuel_log_id')
                ->nullable()
                ->after('user_id')
                ->constrained('odometer_logs')
                ->nullOnDelete();

            // Stamped on a Fuel log when the next Fuel log on the same
            // trip is recorded. While null, that tank cycle is still open.
            $table->timestamp('closed_at')->nullable()->after('recorded_at');

            $table->index('fuel_log_id');
            $table->index('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('odometer_logs', function (Blueprint $table) {
            $table->dropForeign(['fuel_log_id']);
            $table->dropIndex(['fuel_log_id']);
            $table->dropIndex(['closed_at']);
            $table->dropColumn(['fuel_log_id', 'closed_at']);
        });
    }
};
