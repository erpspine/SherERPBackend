<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('proforma_invoices', 'currency')) {
                $table->string('currency', 8)->default('USD')->after('total');
            }
            if (! Schema::hasColumn('proforma_invoices', 'exchange_rate')) {
                $table->decimal('exchange_rate', 14, 4)->default(1)->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('proforma_invoices', 'exchange_rate')) {
                $table->dropColumn('exchange_rate');
            }
            if (Schema::hasColumn('proforma_invoices', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
