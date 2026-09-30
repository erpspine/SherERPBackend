<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $records = DB::table('vehicle_services')
            ->whereNotNull('parts_replaced')
            ->where('parts_replaced', '!=', '')
            ->get(['id', 'parts_replaced', 'cost']);

        foreach ($records as $record) {
            $parts = collect(preg_split('/\r?\n|,|;/', (string) $record->parts_replaced))
                ->map(fn($part) => trim((string) $part))
                ->filter()
                ->values()
                ->map(fn($part) => [
                    'service' => $part,
                    'details' => '',
                    'cost' => $record->cost !== null ? (float) $record->cost : null,
                ])
                ->all();

            if ($parts === []) {
                continue;
            }

            DB::table('vehicle_services')
                ->where('id', $record->id)
                ->update([
                    'service_items' => json_encode($parts),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('vehicle_services')
            ->whereNotNull('service_items')
            ->update(['service_items' => null]);
    }
};
