<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lease_contracts', 'is_parent_company')) {
            Schema::table('lease_contracts', function (Blueprint $table): void {
                $table->boolean('is_parent_company')->default(false)->after('parent_client_name');
            });
        }

        DB::statement("ALTER TABLE lease_contracts MODIFY lease_type ENUM('Daily Lease', 'Short-Term Lease', 'Long-Term Lease') NULL, MODIFY start_date DATE NULL, MODIFY end_date DATE NULL");

        DB::transaction(function (): void {
            $parentId = DB::table('lease_contracts')
                ->whereRaw('LOWER(TRIM(client_name)) = ?', ['mksc parent'])
                ->where('is_parent_company', true)
                ->value('id');

            if (!$parentId) {
                $parentId = DB::table('lease_contracts')->insertGetId([
                    'client_name' => 'MKSC Parent',
                    'parent_client_name' => null,
                    'is_parent_company' => true,
                    'group_name' => null,
                    'lease_type' => null,
                    'start_date' => null,
                    'end_date' => null,
                    'duration_days' => null,
                    'monthly_rate' => null,
                    'notes' => null,
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $longTermIds = DB::table('lease_contracts')
                ->whereRaw('LOWER(TRIM(client_name)) = ?', ['mksc'])
                ->where('is_parent_company', false)
                ->pluck('id');

            foreach ($longTermIds as $contractId) {
                DB::table('lease_contracts')->where('id', $contractId)->update([
                    'client_name' => 'MKSC Long Term Child MKSC',
                    'parent_client_name' => 'MKSC Parent',
                    'updated_at' => now(),
                ]);

                DB::table('job_cards')
                    ->where('lease_contract_id', $contractId)
                    ->where('tour_operator_client_name', 'MKSC')
                    ->update(['tour_operator_client_name' => 'MKSC Long Term Child MKSC']);

                DB::table('vehicles')
                    ->whereIn('id', DB::table('lease_contract_vehicle')
                        ->where('lease_contract_id', $contractId)
                        ->select('vehicle_id'))
                    ->where('lease_client_name', 'MKSC')
                    ->update(['lease_client_name' => 'MKSC Long Term Child MKSC']);
            }

            DB::table('lease_contracts')
                ->whereRaw('LOWER(TRIM(client_name)) IN (?, ?, ?, ?, ?, ?)', [
                    'mksc - extra',
                    'mksc-extra',
                    'mksc - long term',
                    'mksc-long term',
                    'mksc long term',
                    'mksc long term child mksc',
                ])
                ->update([
                    'parent_client_name' => 'MKSC Parent',
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            if (Schema::hasColumn('lease_contracts', 'is_parent_company')) {
                DB::table('lease_contracts')
                    ->where('is_parent_company', true)
                    ->where('client_name', 'MKSC Parent')
                    ->delete();
            }

            DB::table('lease_contracts')
                ->where('client_name', 'MKSC Long Term Child MKSC')
                ->update([
                    'client_name' => 'MKSC',
                    'parent_client_name' => null,
                ]);

            DB::table('lease_contracts')
                ->where('client_name', 'MKSC - EXTRA')
                ->update(['parent_client_name' => 'MKSC']);

            DB::table('job_cards')
                ->where('tour_operator_client_name', 'MKSC Long Term Child MKSC')
                ->update(['tour_operator_client_name' => 'MKSC']);

            DB::table('vehicles')
                ->where('lease_client_name', 'MKSC Long Term Child MKSC')
                ->update(['lease_client_name' => 'MKSC']);
        });

        DB::statement("ALTER TABLE lease_contracts MODIFY lease_type ENUM('Daily Lease', 'Short-Term Lease', 'Long-Term Lease') NOT NULL, MODIFY start_date DATE NOT NULL, MODIFY end_date DATE NOT NULL");

        if (Schema::hasColumn('lease_contracts', 'is_parent_company')) {
            Schema::table('lease_contracts', function (Blueprint $table): void {
                $table->dropColumn('is_parent_company');
            });
        }
    }
};
