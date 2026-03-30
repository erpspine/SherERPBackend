<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concession_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('park_id')->constrained('parks')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('category', 50);
            $table->decimal('rate', 10, 2)->unsigned();
            $table->timestamps();

            $table->unique(['park_id', 'type', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concession_rates');
    }
};
