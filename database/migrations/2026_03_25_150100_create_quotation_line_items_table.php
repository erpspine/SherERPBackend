<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_line_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->date('date');
            $table->string('service_description', 500);
            $table->unsignedInteger('number_of_vehicles')->default(1);
            $table->decimal('cost_per_vehicle', 15, 2)->default(0);
            $table->unsignedInteger('number_of_adults')->default(0);
            $table->decimal('cost_per_adult_concession', 15, 2)->default(0);
            $table->decimal('cost_per_park_fee', 15, 2)->default(0);
            $table->unsignedInteger('number_of_children')->default(0);
            $table->decimal('cost_per_child_concession', 15, 2)->default(0);
            $table->decimal('cost_per_child_park_fee', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_line_items');
    }
};
