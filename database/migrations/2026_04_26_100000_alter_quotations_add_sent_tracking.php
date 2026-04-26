<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->foreignId('sent_by_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('sent_at')->nullable()->after('sent_by_id');
        });

        // Change default status to Pending for new rows
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quotations MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
        } else {
            Schema::table('quotations', function (Blueprint $table): void {
                $table->string('status', 30)->default('Pending')->change();
            });
        }

        // Migrate existing 'Draft' rows to 'Pending'
        DB::table('quotations')->where('status', 'Draft')->update(['status' => 'Pending']);
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropForeign(['sent_by_id']);
            $table->dropColumn(['sent_by_id', 'sent_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quotations MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Draft'");
        } else {
            Schema::table('quotations', function (Blueprint $table): void {
                $table->string('status', 30)->default('Draft')->change();
            });
        }
    }
};
