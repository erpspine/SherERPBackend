<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->enum('lease_type', ['Daily Lease', 'Short-Term Lease', 'Long-Term Lease'])->nullable()->after('status');
            $table->date('lease_start_date')->nullable()->after('lease_type');
            $table->date('lease_end_date')->nullable()->after('lease_start_date');
            $table->string('lease_contract_no', 80)->nullable()->after('lease_end_date');
            $table->string('lease_client_name', 150)->nullable()->after('lease_contract_no');
            $table->decimal('lease_monthly_rate', 14, 2)->nullable()->after('lease_client_name');
            $table->text('lease_notes')->nullable()->after('lease_monthly_rate');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn([
                'lease_type',
                'lease_start_date',
                'lease_end_date',
                'lease_contract_no',
                'lease_client_name',
                'lease_monthly_rate',
                'lease_notes',
            ]);
        });
    }
};
