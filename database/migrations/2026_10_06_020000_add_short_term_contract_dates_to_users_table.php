<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'contract_start_date')) {
                $table->date('contract_start_date')->nullable()->after('contract_type');
            }

            if (! Schema::hasColumn('users', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('contract_start_date');
            }

            if (! Schema::hasColumn('users', 'contract_expiry_notified_at')) {
                $table->timestamp('contract_expiry_notified_at')->nullable()->after('contract_end_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'contract_start_date') ? 'contract_start_date' : null,
                Schema::hasColumn('users', 'contract_end_date') ? 'contract_end_date' : null,
                Schema::hasColumn('users', 'contract_expiry_notified_at') ? 'contract_expiry_notified_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
