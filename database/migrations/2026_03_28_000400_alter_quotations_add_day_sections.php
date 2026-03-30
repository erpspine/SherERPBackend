<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            // Make lead_id nullable to support quotations without a linked lead
            $table->foreignId('lead_id')->nullable()->change();
            // Store the full day-sections structure (raw form state) as JSON
            $table->json('day_sections')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropColumn('day_sections');
            $table->foreignId('lead_id')->nullable(false)->change();
        });
    }
};
