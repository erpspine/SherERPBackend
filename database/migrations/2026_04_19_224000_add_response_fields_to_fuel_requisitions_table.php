<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_requisitions', static function (Blueprint $table): void {
            $table->string('status', 20)->default('Pending')->after('reason');
            $table->foreignId('responded_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable()->after('responded_by');
            $table->text('response_note')->nullable()->after('responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('fuel_requisitions', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('responded_by');
            $table->dropColumn(['status', 'responded_at', 'response_note']);
        });
    }
};
