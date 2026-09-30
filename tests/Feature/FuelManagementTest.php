<?php

namespace Tests\Feature;

use App\Models\{Lead, SafariAllocation, User, Vehicle};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FuelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_prices_are_saved_validated_and_permission_protected(): void
    {
        $user = User::factory()->create();
        foreach (['safari-allocations.view', 'odometer-logs.view', 'odometer-logs.update'] as $name) {
            Permission::findOrCreate($name, 'web');
            $user->givePermissionTo($name);
        }
        Sanctum::actingAs($user);
        $this->putJson('/api/fuel-management/monthly-price', ['month' => '2026-06', 'price' => 3000])->assertOk();
        $this->putJson('/api/fuel-management/monthly-price', ['month' => '2026-07', 'price' => 3200])->assertOk();
        $this->putJson('/api/fuel-management/monthly-price', ['month' => '2026-06', 'price' => 3100])->assertOk()
            ->assertJsonPath('prices.2026-06', 3100)->assertJsonPath('prices.2026-07', 3200);
        $this->getJson('/api/fuel-management')->assertOk()->assertJsonPath('monthly_prices.2026-06', 3100);
        $this->putJson('/api/fuel-management/monthly-price', ['month' => '2026-13', 'price' => 1])->assertUnprocessable();
        $this->putJson('/api/fuel-management/monthly-price', ['month' => '2026-06', 'price' => -1])->assertUnprocessable();
        $user->revokePermissionTo('odometer-logs.update');
        $this->putJson('/api/fuel-management/monthly-price', ['month' => '2026-06', 'price' => 1])->assertForbidden();
    }

    public function test_full_refill_sequence_validation_and_permissions(): void
    {
        $user = User::factory()->create();
        foreach (['safari-allocations.view', 'odometer-logs.view', 'odometer-logs.create', 'odometer-logs.update'] as $name) {
            Permission::findOrCreate($name, 'web');
            $user->givePermissionTo($name);
        }
        Sanctum::actingAs($user);
        $lead = Lead::create(['booking_ref' => 'FUEL-TEST', 'client_company' => 'Test', 'agent_contact' => 'Test', 'agent_email' => 'test@example.com', 'agent_phone' => '123', 'client_country' => 'Kenya', 'start_date' => '2026-09-01', 'end_date' => '2026-09-05', 'route_parks' => 'Test']);
        $vehicle = Vehicle::create(['vehicle_no' => 'F1', 'plate_no' => 'FUEL1', 'make' => 'Toyota', 'model' => 'Test', 'year' => 2020, 'seats' => 7, 'chassis' => 'FUELCHASSIS']);
        $trip = SafariAllocation::create(['lead_id' => $lead->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $user->id]);
        $next = SafariAllocation::create(['lead_id' => $lead->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $user->id]);
        $payload = ['filled_at' => '2026-09-01', 'litres_filled' => 100, 'extra_litres' => 20, 'expected_average' => 5, 'start_km' => 1000, 'end_km' => 1500];
        $this->putJson('/api/fuel-management/safari/' . $trip->id, array_merge($payload, ['end_km' => null, 'litres_filled_end' => null]))->assertOk();
        $this->assertNull($this->getJson('/api/fuel-management')->assertOk()->json('entries.0.vehicle_average'));
        $this->putJson('/api/fuel-management/safari/' . $trip->id, $payload)->assertOk();
        $this->putJson('/api/fuel-management/safari/' . $next->id, array_merge($payload, ['filled_at' => '2026-09-06', 'litres_filled' => 80, 'extra_litres' => 0, 'start_km' => 1500, 'end_km' => null]))->assertOk();
        $rows = $this->getJson('/api/fuel-management')->assertOk()->json('entries');
        $previous = collect($rows)->firstWhere('safari_allocation_id', $trip->id);
        $this->assertNull($previous['driver_average']);
        $payload['litres_filled_end'] = 80;
        $this->putJson('/api/fuel-management/safari/' . $trip->id, $payload)->assertOk();
        $rows = $this->getJson('/api/fuel-management')->assertOk()->json('entries');
        $previous = collect($rows)->firstWhere('safari_allocation_id', $trip->id);
        $this->assertEquals(80, $previous['litres_after_safari']);
        $this->assertEquals(5, $previous['driver_average']);
        $this->assertSame('On target', $previous['status']);
        foreach ([['end_km' => 1600], ['end_km' => 900]] as $invalid) {
            $this->putJson('/api/fuel-management/safari/' . $trip->id, array_merge($payload, $invalid))->assertUnprocessable();
        }
        $this->putJson('/api/fuel-management/safari/' . $trip->id, array_merge($payload, ['extra_litres' => 40]))->assertOk();
        $rows = $this->getJson('/api/fuel-management')->assertOk()->json('entries');
        $this->assertSame('On target', collect($rows)->firstWhere('safari_allocation_id', $trip->id)['status']);
        $this->assertDatabaseCount('safari_fuel_entries', 2);
        \App\Models\Setting::set('fuel_deduction_price_2026-09', '3000');
        $pdf = \Mockery::mock();
        $pdf->shouldReceive('setPaper')->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF-test');
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadHTML')->once()->with(\Mockery::on(function ($html) {
            $this->assertStringContainsString('2026-09', $html);
            $this->assertStringContainsString('Deduction', $html);
            $this->assertStringContainsString('145710', $html);
            return true;
        }))->andReturn($pdf);
        $this->get('/api/fuel-management/export/pdf?report=1&dateFrom=2026-09-01&dateTo=2026-09-30&fuelPrice=1')->assertOk();

        $user->revokePermissionTo('odometer-logs.update');
        $this->putJson('/api/fuel-management/safari/' . $trip->id, $payload)->assertForbidden();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/fuel-management')->assertForbidden();
    }
}
