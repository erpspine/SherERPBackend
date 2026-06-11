<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_allocations', function (Blueprint $table): void {
            $table->string('group_name', 150)->nullable()->after('lease_contract_id');
        });
    }

    public function down(): void
    {
        Schema::table('lease_allocations', function (Blueprint $table): void {
            $table->dropColumn('group_name');
        });
    }
};
