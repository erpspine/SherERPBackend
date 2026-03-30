<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('booking_ref', 50)->unique();
            $table->string('client_company', 255);
            $table->string('agent_contact', 255);
            $table->string('agent_email', 255);
            $table->string('agent_phone', 50);
            $table->string('client_country', 120);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('route_parks', 500);
            $table->unsignedInteger('pax_adults')->default(0);
            $table->unsignedInteger('pax_children')->default(0);
            $table->unsignedInteger('no_of_vehicles')->default(1);
            $table->text('special_requirements')->nullable();
            $table->string('booking_status', 40)->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
