<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('park_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('park_id')->constrained('parks')->cascadeOnDelete();
            $table->string('type', 50);      // e.g. resident, non_resident
            $table->string('category', 50);  // e.g. adult, child
            $table->decimal('rate', 12, 2)->unsigned();
            $table->timestamps();

            $table->unique(['park_id', 'type', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('park_rates');
    }
};
