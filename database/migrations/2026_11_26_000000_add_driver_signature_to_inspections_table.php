<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table): void {
            if (! Schema::hasColumn('inspections', 'driver_signature_path')) {
                $table->string('driver_signature_path')->nullable()->after('remarks');
            }

            if (! Schema::hasColumn('inspections', 'driver_signed_at')) {
                $table->timestamp('driver_signed_at')->nullable()->after('driver_signature_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table): void {
            foreach (['driver_signed_at', 'driver_signature_path'] as $column) {
                if (Schema::hasColumn('inspections', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
