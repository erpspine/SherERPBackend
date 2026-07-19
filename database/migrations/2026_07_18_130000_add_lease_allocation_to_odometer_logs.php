<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odometer_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('safari_allocation_id')->nullable()->change();
            $table->foreignId('lease_allocation_id')
                ->nullable()
                ->after('safari_allocation_id')
                ->constrained('lease_allocations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('odometer_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('lease_allocation_id');
            $table->unsignedBigInteger('safari_allocation_id')->nullable(false)->change();
        });
    }
};
