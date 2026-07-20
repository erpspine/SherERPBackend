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
        // A fresh SQLite database (used by the feature suite) has no legacy
        // allocations to backfill, and SQLite cannot execute Laravel's
        // MySQL-style joined UPDATE generated below.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::table('safari_allocations as sa')
            ->join('leads as l', 'l.id', '=', 'sa.lead_id')
            ->where(function ($query): void {
                $query->whereNull('sa.start_date')
                    ->orWhereNull('sa.end_date');
            })
            ->update([
                'sa.start_date' => DB::raw('COALESCE(sa.start_date, DATE(l.start_date))'),
                'sa.end_date' => DB::raw('COALESCE(sa.end_date, DATE(l.end_date))'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank: this migration fixes historical data and is not safely reversible.
    }
};
