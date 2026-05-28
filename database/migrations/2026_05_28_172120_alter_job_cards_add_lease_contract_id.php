<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->foreignId('lease_contract_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained('lease_contracts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->dropForeign(['lease_contract_id']);
            $table->dropColumn('lease_contract_id');
        });
    }
};
