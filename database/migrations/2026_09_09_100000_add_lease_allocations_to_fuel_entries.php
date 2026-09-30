<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('safari_fuel_entries', function (Blueprint $table): void {
            $table->dropForeign(['safari_allocation_id']);
            $table->dropUnique(['safari_allocation_id']);
            $table->unsignedBigInteger('safari_allocation_id')->nullable()->change();
            $table->foreign('safari_allocation_id')->references('id')->on('safari_allocations')->restrictOnDelete();
            $table->foreignId('lease_allocation_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('safari_fuel_entries', function (Blueprint $table): void {
            $table->dropForeign(['lease_allocation_id']);
            $table->dropUnique(['lease_allocation_id']);
            $table->dropColumn('lease_allocation_id');
            $table->dropForeign(['safari_allocation_id']);
            $table->unsignedBigInteger('safari_allocation_id')->nullable(false)->change();
            $table->foreign('safari_allocation_id')->references('id')->on('safari_allocations')->restrictOnDelete();
            $table->unique('safari_allocation_id');
        });
    }
};
