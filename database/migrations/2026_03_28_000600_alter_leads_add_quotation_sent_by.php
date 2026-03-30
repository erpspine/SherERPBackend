<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('quotation_sent_by')->nullable()->after('booking_status')->constrained('users')->nullOnDelete();
            $table->timestamp('quotation_sent_at')->nullable()->after('quotation_sent_by');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('quotation_sent_by');
            $table->dropColumn('quotation_sent_at');
        });
    }
};
