<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE safari_allocations MODIFY driver_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ensure non-null before restoring NOT NULL constraint.
        DB::statement('UPDATE safari_allocations SET driver_id = (SELECT MIN(id) FROM users) WHERE driver_id IS NULL');
        DB::statement('ALTER TABLE safari_allocations MODIFY driver_id BIGINT UNSIGNED NOT NULL');
    }
};
