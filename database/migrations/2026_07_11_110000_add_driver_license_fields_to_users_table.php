<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'driver_license')) {
                $table->string('driver_license', 120)->nullable()->after('driving_started_at');
            }

            if (! Schema::hasColumn('users', 'tour_guide_license')) {
                $table->string('tour_guide_license', 120)->nullable()->after('driver_license');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'driver_license')) {
                $table->dropColumn('driver_license');
            }

            if (Schema::hasColumn('users', 'tour_guide_license')) {
                $table->dropColumn('tour_guide_license');
            }
        });
    }
};
