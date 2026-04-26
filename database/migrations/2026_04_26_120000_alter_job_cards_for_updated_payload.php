<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('lead_id')
                ->constrained('vehicles')
                ->nullOnDelete();

            $table->string('type', 30)->default('Safari')->after('job_card_no');
            $table->time('time_out')->nullable()->after('safari_end_date');
            $table->time('time_in')->nullable()->after('time_out');

            $table->string('reason', 500)->nullable()->after('additional_details');
            $table->text('client_details')->nullable()->after('reason');
            $table->string('location', 255)->nullable()->after('client_details');
            $table->decimal('kms', 10, 2)->nullable()->after('location');

            $table->unsignedInteger('odometer_out')->nullable()->after('kms');
            $table->unsignedInteger('odometer_in')->nullable()->after('odometer_out');
            $table->unsignedInteger('mileage')->nullable()->after('odometer_in');
            $table->decimal('fuel_gauge_out', 8, 2)->nullable()->after('mileage');
            $table->decimal('fuel_gauge_in', 8, 2)->nullable()->after('fuel_gauge_out');
            $table->decimal('approximate_fuel_used', 8, 2)->nullable()->after('fuel_gauge_in');
            $table->string('driver_details', 255)->nullable()->after('approximate_fuel_used');
        });

        Schema::table('job_cards', function (Blueprint $table): void {
            $table->string('booking_reference_no', 50)->nullable()->change();
            $table->string('tour_operator_client_name', 255)->nullable()->change();
            $table->string('contact_person', 255)->nullable()->change();
            $table->string('contact_number', 50)->nullable()->change();
            $table->unsignedInteger('adults')->nullable()->change();
            $table->unsignedInteger('children')->nullable()->change();
            $table->string('guide_language', 60)->nullable()->change();
            $table->date('safari_start_date')->nullable()->change();
            $table->date('safari_end_date')->nullable()->change();
            $table->unsignedSmallInteger('number_of_days')->nullable()->change();
        });

        DB::table('job_cards')
            ->whereNull('type')
            ->update(['type' => 'Safari']);
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table): void {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn([
                'vehicle_id',
                'type',
                'time_out',
                'time_in',
                'reason',
                'client_details',
                'location',
                'kms',
                'odometer_out',
                'odometer_in',
                'mileage',
                'fuel_gauge_out',
                'fuel_gauge_in',
                'approximate_fuel_used',
                'driver_details',
            ]);
        });

        Schema::table('job_cards', function (Blueprint $table): void {
            $table->string('booking_reference_no', 50)->nullable(false)->change();
            $table->string('tour_operator_client_name', 255)->nullable(false)->change();
            $table->string('contact_person', 255)->nullable(false)->change();
            $table->string('contact_number', 50)->nullable(false)->change();
            $table->unsignedInteger('adults')->default(0)->nullable(false)->change();
            $table->unsignedInteger('children')->default(0)->nullable(false)->change();
            $table->string('guide_language', 60)->default('English')->nullable(false)->change();
            $table->date('safari_start_date')->nullable(false)->change();
            $table->date('safari_end_date')->nullable(false)->change();
            $table->unsignedSmallInteger('number_of_days')->default(1)->nullable(false)->change();
        });
    }
};
