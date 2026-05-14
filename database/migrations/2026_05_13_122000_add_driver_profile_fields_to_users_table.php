<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('languages_spoken', 255)->nullable()->after('phone');
            $table->text('work_experience')->nullable()->after('languages_spoken');
            $table->date('driving_started_at')->nullable()->after('work_experience');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['languages_spoken', 'work_experience', 'driving_started_at']);
        });
    }
};
