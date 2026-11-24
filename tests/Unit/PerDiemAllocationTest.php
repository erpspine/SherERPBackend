<?php

namespace Tests\Unit;

use App\Support\PerDiemAllocation;
use PHPUnit\Framework\TestCase;

class PerDiemAllocationTest extends TestCase
{
    public function test_adjusted_daily_amounts_keep_the_original_proportions_and_sum_to_the_approved_total(): void
    {
        $items = [
            ['date' => 'Day 1', 'allowancePerDay' => 10.10],
            ['date' => 'Day 2', 'allowancePerDay' => 20.20],
        ];

        $adjusted = PerDiemAllocation::acrossDates($items, 2500);

        $this->assertSame(8.33, $adjusted[0]['allowancePerDay']);
        $this->assertSame(16.67, $adjusted[1]['allowancePerDay']);
        $this->assertSame(2500, (int) round(array_sum(array_column($adjusted, 'allowancePerDay')) * 100));
        $this->assertSame('Day 1', $adjusted[0]['date']);
        $this->assertSame(10.10, $items[0]['allowancePerDay']);
    }

    public function test_rounding_cents_are_distributed_consistently(): void
    {
        $items = array_fill(0, 3, ['allowancePerDay' => 1.00]);

        $adjusted = PerDiemAllocation::acrossDates($items, 100);

        $this->assertSame([0.34, 0.33, 0.33], array_column($adjusted, 'allowancePerDay'));
    }
}
