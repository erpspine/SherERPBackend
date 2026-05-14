<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lease_contracts', 'contract_no')) {
            Schema::table('lease_contracts', function (Blueprint $table): void {
                $table->dropUnique('lease_contracts_contract_no_unique');
                $table->dropColumn('contract_no');
            });
        }

        if (Schema::hasColumn('vehicles', 'lease_contract_no')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->dropColumn('lease_contract_no');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lease_contracts', 'contract_no')) {
            Schema::table('lease_contracts', function (Blueprint $table): void {
                $table->string('contract_no', 80)->nullable()->after('id');
                $table->unique('contract_no');
            });
        }

        if (! Schema::hasColumn('vehicles', 'lease_contract_no')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->string('lease_contract_no', 80)->nullable()->after('lease_end_date');
            });
        }
    }
};
