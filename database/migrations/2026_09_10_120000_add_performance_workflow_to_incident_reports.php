<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $table): void {
            $table->string('case_reference')->nullable()->unique();
            $table->foreignId('safari_allocation_id')->nullable()->constrained()->nullOnDelete();
            $table->json('performance_data')->nullable();
            $table->unsignedInteger('performance_revision')->default(0);
        });
        Schema::create('incident_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incident_report_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 1)->nullable();
            $table->string('name');
            $table->string('path');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_attachments');
        Schema::table('incident_reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('safari_allocation_id');
            $table->dropColumn(['case_reference', 'performance_data', 'performance_revision']);
        });
    }
};
