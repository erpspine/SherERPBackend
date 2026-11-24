<?php

namespace App\Support;

use InvalidArgumentException;

class PerDiemAllocation
{
    /** Distribute the approved total across itinerary dates in exact cents. */
    public static function acrossDates(array $items, int $approvedCents): array
    {
        $weights = array_map(
            fn (array $item): int => max(0, (int) round((float) ($item['allowancePerDay'] ?? 0) * 100)),
            $items,
        );
        $originalCents = array_sum($weights);

        if ($approvedCents > 0 && $originalCents === 0) {
            throw new InvalidArgumentException('Cannot distribute an approved allowance across dates with no original allowance.');
        }

        $allocated = [];
        $remainders = [];
        foreach ($weights as $index => $weight) {
            $numerator = $weight * $approvedCents;
            $allocated[$index] = $originalCents ? intdiv($numerator, $originalCents) : 0;
            $remainders[$index] = $originalCents ? $numerator % $originalCents : 0;
        }

        $remainingCents = $approvedCents - array_sum($allocated);
        $order = array_keys($weights);
        usort($order, fn (int $a, int $b): int => ($remainders[$b] <=> $remainders[$a]) ?: ($a <=> $b));
        for ($i = 0; $i < $remainingCents; $i++) {
            $allocated[$order[$i]]++;
        }

        foreach ($items as $index => &$item) {
            $item['allowancePerDay'] = $allocated[$index] / 100;
        }
        unset($item);

        return $items;
    }
}
