<?php

namespace App\Services;

class SafariFuelMetrics
{
    public static function calculate(array $entry, ?array $next): array
    {
        $distance = isset($entry['end_km']) && $entry['end_km'] !== '' ? $entry['end_km'] - $entry['start_km'] : null;
        $endLitres = array_key_exists('litres_filled_end', $entry) && $entry['litres_filled_end'] !== null && $entry['litres_filled_end'] !== ''
            ? (float) $entry['litres_filled_end']
            : null;
        $consumed = $endLitres !== null ? (float) $entry['extra_litres'] + $endLitres : null;
        $status = $endLitres === null ? 'Awaiting end fuel' : ($distance === null ? 'Awaiting end KM' : 'Ready');
        if ($status === 'Ready' && $consumed <= 0) {
            $status = 'Check fuel';
        }
        if ($distance === null || $endLitres === null) {
            $distance = null;
            $consumed = null;
        }
        $average = $status === 'Ready' ? $distance / $consumed : null;
        $efficiency = $average !== null ? 100 : null;
        if ($efficiency !== null) {
            $status = $efficiency >= 100 ? 'On target' : ($efficiency >= 90 ? 'Watch' : 'Below target');
        }
        return [
            'km_done' => $distance,
            'litres_after_safari' => $endLitres !== null && $endLitres >= 0 ? round($endLitres, 2) : null,
            'litres_consumed' => $consumed,
            'vehicle_average' => $average !== null ? round($average, 2) : null,
            'driver_average' => $average !== null ? round($average, 2) : null,
            'efficiency' => $efficiency !== null ? round($efficiency, 1) : null,
            'status' => $status,
        ];
    }
}
