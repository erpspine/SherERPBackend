<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('checklists', function (Blueprint $table): void {
            $table->string('checklist_type', 30)
                ->default('pre_departure')
                ->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('checklists', function (Blueprint $table): void {
            $table->dropColumn('checklist_type');
        });
    }
};
