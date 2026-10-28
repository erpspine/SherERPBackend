<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table): void {
            $table->string('parent_client_name', 150)->nullable()->after('client_name');
        });

        $parentName = DB::table('lease_contracts')
            ->whereRaw('LOWER(TRIM(client_name)) = ?', ['mksc'])
            ->value('client_name');

        if ($parentName !== null) {
            DB::table('lease_contracts')
                ->whereRaw('LOWER(TRIM(client_name)) IN (?, ?, ?, ?, ?)', [
                    'mksc - extra',
                    'mksc-extra',
                    'mksc - long term',
                    'mksc-long term',
                    'mksc long term',
                ])
                ->update(['parent_client_name' => $parentName]);
        }
    }

    public function down(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table): void {
            $table->dropColumn('parent_client_name');
        });
    }
};
