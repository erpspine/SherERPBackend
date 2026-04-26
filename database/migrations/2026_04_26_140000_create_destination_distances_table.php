<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_distances', function (Blueprint $table): void {
            $table->id();
            $table->string('destination_from', 255);
            $table->string('destination_to', 255);
            $table->decimal('distance_km', 10, 2);
            $table->timestamps();

            $table->unique(['destination_from', 'destination_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_distances');
    }
};
