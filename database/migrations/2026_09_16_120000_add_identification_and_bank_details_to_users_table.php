<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('tin', 50)->nullable();
            $table->string('nida', 50)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_number', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['tin', 'nida', 'bank_name', 'bank_account_number']);
        });
    }
};
