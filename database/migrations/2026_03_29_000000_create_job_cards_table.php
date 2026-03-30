<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('job_card_no', 30)->unique()->nullable();
            $table->string('booking_reference_no', 50);
            $table->string('tour_operator_client_name', 255);
            $table->string('contact_person', 255);
            $table->string('contact_number', 50);
            $table->string('contact_email', 255)->nullable();
            $table->unsignedInteger('adults')->default(0);
            $table->unsignedInteger('children')->default(0);
            $table->string('nationality', 120)->nullable();
            $table->string('guide_language', 60)->default('English');
            $table->date('safari_start_date');
            $table->date('safari_end_date');
            $table->unsignedSmallInteger('number_of_days')->default(1);
            $table->string('route_summary', 500)->nullable();
            $table->json('route_itinerary')->nullable();
            $table->string('pickup_location', 255)->nullable();
            $table->string('dropoff_location', 255)->nullable();
            $table->text('additional_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_cards');
    }
};
