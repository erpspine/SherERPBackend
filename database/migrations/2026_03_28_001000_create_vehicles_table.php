<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('vehicle_no', 50)->unique();
            $table->string('plate_no', 50)->unique();
            $table->string('make', 100);
            $table->string('model', 120);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('seats');
            $table->string('chassis', 100)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
