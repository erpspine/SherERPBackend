<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_requisitions', function (Blueprint $table): void {
            $table->foreignId('approved_by')->nullable()->after('responded_by')->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->foreignId('amended_by')->nullable()->after('rejected_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fuel_requisitions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropConstrainedForeignId('amended_by');
        });
    }
};
