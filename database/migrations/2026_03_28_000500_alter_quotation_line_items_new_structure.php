<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old columns in a separate call first (MySQL requirement)
        Schema::table('quotation_line_items', function (Blueprint $table): void {
            $table->dropColumn([
                'date',
                'service_description',
                'number_of_vehicles',
                'cost_per_vehicle',
                'number_of_adults',
                'cost_per_adult_concession',
                'cost_per_park_fee',
                'number_of_children',
                'cost_per_child_concession',
                'cost_per_child_park_fee',
            ]);
        });

        // Add new columns
        Schema::table('quotation_line_items', function (Blueprint $table): void {
            $table->string('day_title', 100)->after('quotation_id');
            $table->text('day_description')->nullable()->after('day_title');
            $table->string('item', 100)->after('day_description');
            $table->string('description', 500)->after('item');
            $table->string('unit', 50)->after('description');
            $table->decimal('qty', 10, 2)->default(1)->after('unit');
            $table->decimal('rate', 15, 2)->default(0)->after('qty');
            $table->decimal('total', 15, 2)->default(0)->after('rate');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table): void {
            $table->dropColumn([
                'day_title',
                'day_description',
                'item',
                'description',
                'unit',
                'qty',
                'rate',
                'total',
            ]);
        });

        Schema::table('quotation_line_items', function (Blueprint $table): void {
            $table->date('date')->after('quotation_id');
            $table->string('service_description', 500)->after('date');
            $table->unsignedInteger('number_of_vehicles')->default(1)->after('service_description');
            $table->decimal('cost_per_vehicle', 15, 2)->default(0)->after('number_of_vehicles');
            $table->unsignedInteger('number_of_adults')->default(0)->after('cost_per_vehicle');
            $table->decimal('cost_per_adult_concession', 15, 2)->default(0)->after('number_of_adults');
            $table->decimal('cost_per_park_fee', 15, 2)->default(0)->after('cost_per_adult_concession');
            $table->unsignedInteger('number_of_children')->default(0)->after('cost_per_park_fee');
            $table->decimal('cost_per_child_concession', 15, 2)->default(0)->after('number_of_children');
            $table->decimal('cost_per_child_park_fee', 15, 2)->default(0)->after('cost_per_child_concession');
        });
    }
};
