<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table): void {
            $table->id();
            $table->date('incident_date');
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('report_type', 30);
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->json('photos')->nullable();
            $table->string('status', 20)->default('Open');
            $table->text('closing_remarks')->nullable();
            $table->timestamps();

            $table->index(['incident_date', 'status']);
            $table->index('report_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};
