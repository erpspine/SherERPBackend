<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The product collapsed Start / Stop / End into a single "Movement"
     * entry type. Coerce any pre-existing rows so the new validator never
     * rejects them and the simplified UI can render every reading.
     */
    public function up(): void
    {
        DB::table('odometer_logs')
            ->whereIn('entry_type', ['Start', 'Stop', 'End'])
            ->update(['entry_type' => 'Movement']);
    }

    public function down(): void
    {
        // No-op: there is no faithful inverse mapping from Movement back
        // to Start / Stop / End. Leaving rows as Movement is the correct
        // historical record once this migration has run.
    }
};
