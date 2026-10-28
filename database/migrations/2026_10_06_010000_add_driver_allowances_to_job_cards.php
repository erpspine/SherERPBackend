<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            if (! Schema::hasColumn('job_cards', 'driver_allowances')) {
                $table->json('driver_allowances')->nullable()->after('driver_allowance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            if (Schema::hasColumn('job_cards', 'driver_allowances')) {
                $table->dropColumn('driver_allowances');
            }
        });
    }
};
