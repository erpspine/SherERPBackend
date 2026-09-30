<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('per_diem_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained()->cascadeOnDelete();
            $table->string('driver_key', 100);
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['job_card_id', 'driver_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('per_diem_vouchers');
    }
};
