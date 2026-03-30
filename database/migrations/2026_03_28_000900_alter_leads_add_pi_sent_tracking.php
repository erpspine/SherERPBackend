<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('pi_sent_by')->nullable()->after('quotation_sent_at')->constrained('users')->nullOnDelete();
            $table->timestamp('pi_sent_at')->nullable()->after('pi_sent_by');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pi_sent_by');
            $table->dropColumn('pi_sent_at');
        });
    }
};
