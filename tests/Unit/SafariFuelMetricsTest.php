<?php
namespace Tests\Unit;
use App\Services\SafariFuelMetrics;
use PHPUnit\Framework\TestCase;
class SafariFuelMetricsTest extends TestCase
{
    private function entry(array $overrides = []): array
    {
        return array_merge(['extra_litres' => 20, 'start_km' => 1000, 'end_km' => 1500, 'litres_filled_end' => 80], $overrides);
    }
    public function test_complete_trip_counts_end_and_extra_fuel(): void
    {
        $result = SafariFuelMetrics::calculate($this->entry(), null);
        $this->assertSame(100.0, $result['litres_consumed']);
        $this->assertSame(5.0, $result['vehicle_average']);
    }
    public function test_incomplete_trip_never_uses_next_trip_to_calculate_average(): void
    {
        foreach ([['end_km' => null], ['litres_filled_end' => null], ['end_km' => null, 'litres_filled_end' => null], ['end_km' => ''], ['litres_filled_end' => '']] as $missing) {
            $result = SafariFuelMetrics::calculate($this->entry($missing), ['litres_filled' => 80, 'start_km' => 1500]);
            $this->assertNull($result['km_done']);
            $this->assertNull($result['litres_consumed']);
            $this->assertNull($result['efficiency']);
            $this->assertNull($result['vehicle_average']);
            $this->assertNull($result['driver_average']);
        }
    }
    public function test_explicit_end_readings_are_independent_of_next_trip(): void
    {
        $result = SafariFuelMetrics::calculate($this->entry(), ['litres_filled' => 70, 'start_km' => 1600]);
        $this->assertSame(5.0, $result['vehicle_average']);
    }
    public function test_zero_consumption_does_not_divide_by_zero(): void
    {
        $result = SafariFuelMetrics::calculate($this->entry(['extra_litres' => 0, 'litres_filled_end' => 0]), null);
        $this->assertSame('Check fuel', $result['status']);
        $this->assertNull($result['vehicle_average']);
    }
}
