<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('job_cards', 'driver_allowance')) {
                $table->decimal('driver_allowance', 12, 2)->nullable()->after('driver_details');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            if (Schema::hasColumn('job_cards', 'driver_allowance')) {
                $table->dropColumn('driver_allowance');
            }
        });
    }
};
