<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_api_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('name'); // e.g., "Main Website", "Partner Site"
            $table->string('key')->unique(); // API key (hashed)
            $table->string('website_url')->nullable(); // For tracking which website
            $table->boolean('active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_api_keys');
    }
};
