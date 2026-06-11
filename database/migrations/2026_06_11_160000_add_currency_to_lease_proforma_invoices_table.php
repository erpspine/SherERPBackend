<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_proforma_invoices', function (Blueprint $table): void {
            $table->string('currency', 10)->default('USD')->after('client_name');
        });
    }

    public function down(): void
    {
        Schema::table('lease_proforma_invoices', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });
    }
};
