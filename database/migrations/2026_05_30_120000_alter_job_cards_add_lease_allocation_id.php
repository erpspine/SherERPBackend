<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('job_cards', 'lease_allocation_id')) {
                $table->foreignId('lease_allocation_id')
                    ->nullable()
                    ->after('lease_contract_id')
                    ->constrained('lease_allocations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            if (Schema::hasColumn('job_cards', 'lease_allocation_id')) {
                $table->dropConstrainedForeignId('lease_allocation_id');
            }
        });
    }
};
