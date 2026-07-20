<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('incident_reports', 'lease_allocation_id')) {
                $table->foreignId('lease_allocation_id')
                    ->nullable()
                    ->after('lead_id')
                    ->constrained('lease_allocations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('incident_reports', function (Blueprint $table): void {
            if (Schema::hasColumn('incident_reports', 'lease_allocation_id')) {
                $table->dropConstrainedForeignId('lease_allocation_id');
            }
        });
    }
};
