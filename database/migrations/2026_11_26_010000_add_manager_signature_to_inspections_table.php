<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table): void {
            if (! Schema::hasColumn('inspections', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('remarks');
            }

            if (! Schema::hasColumn('inspections', 'manager_name')) {
                $table->string('manager_name')->nullable()->after('driver_signed_at');
            }

            if (! Schema::hasColumn('inspections', 'manager_signature_path')) {
                $table->string('manager_signature_path')->nullable()->after('manager_name');
            }

            if (! Schema::hasColumn('inspections', 'manager_signed_at')) {
                $table->timestamp('manager_signed_at')->nullable()->after('manager_signature_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table): void {
            foreach (['manager_signed_at', 'manager_signature_path', 'manager_name', 'driver_name'] as $column) {
                if (Schema::hasColumn('inspections', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
