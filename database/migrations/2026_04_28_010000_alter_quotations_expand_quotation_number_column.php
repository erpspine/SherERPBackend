<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('quotations', function (Blueprint $table): void {
                $table->string('quotation_number', 50)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('quotations', function (Blueprint $table): void {
                $table->string('quotation_number', 20)->nullable()->change();
            });
        }
    }
};
