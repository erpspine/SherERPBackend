<?php

namespace Tests\Feature;

use App\Models\LeaseAllocation;
use App\Models\LeaseContract;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeaseAllocationItineraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_itinerary_date_rows_are_saved_and_returned_for_create_and_update(): void
    {
        $this->grantAllocationPermissions();
        [$contract, $vehicle] = $this->makeLeaseContractWithVehicle();

        $response = $this->postJson('/api/lease-allocations', [
            'leaseContractId' => $contract->id,
            'vehicleId' => $vehicle->id,
            'startDate' => '2026-10-16',
            'endDate' => '2026-10-18',
            'itinerary' => "2026-10-16: Pickup\n2026-10-17: Transfer",
            'itineraryItems' => [
                ['date' => '2026-10-16', 'details' => 'Pickup'],
                ['date' => '2026-10-17', 'details' => 'Transfer'],
                ['date' => '2026-10-18', 'details' => 'Return'],
            ],
            'status' => 'Scheduled',
        ]);

        $response->assertCreated()
            ->assertJsonPath('allocation.itineraryItems.0.date', '2026-10-16')
            ->assertJsonPath('allocation.itineraryItems.2.details', 'Return');

        $allocationId = $response->json('allocation.id');
        $this->assertSame(
            [
                ['date' => '2026-10-16', 'details' => 'Pickup'],
                ['date' => '2026-10-17', 'details' => 'Transfer'],
                ['date' => '2026-10-18', 'details' => 'Return'],
            ],
            LeaseAllocation::findOrFail($allocationId)->itinerary_items,
        );

        $this->putJson("/api/lease-allocations/{$allocationId}", [
            'leaseContractId' => $contract->id,
            'vehicleId' => $vehicle->id,
            'startDate' => '2026-10-16',
            'endDate' => '2026-10-18',
            'itinerary' => "2026-10-16: Pickup\n2026-10-17: Revised transfer",
            'itineraryItems' => [
                ['date' => '2026-10-16', 'details' => 'Pickup'],
                ['date' => '2026-10-17', 'details' => 'Revised transfer'],
            ],
            'status' => 'Scheduled',
        ])->assertOk()
            ->assertJsonPath(
                'allocation.itineraryItems.1.details',
                'Revised transfer',
            );

        $this->assertSame(
            [
                ['date' => '2026-10-16', 'details' => 'Pickup'],
                ['date' => '2026-10-17', 'details' => 'Revised transfer'],
            ],
            LeaseAllocation::findOrFail($allocationId)->itinerary_items,
        );
    }

    public function test_itinerary_date_must_be_an_iso_date(): void
    {
        $this->grantAllocationPermissions();
        [$contract, $vehicle] = $this->makeLeaseContractWithVehicle();

        $this->postJson('/api/lease-allocations', [
            'leaseContractId' => $contract->id,
            'vehicleId' => $vehicle->id,
            'startDate' => '2026-10-16',
            'endDate' => '2026-10-18',
            'itineraryItems' => [
                ['date' => '16/10/2026', 'details' => 'Pickup'],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itineraryItems.0.date']);
    }

    public function test_itinerary_date_must_fall_within_allocation_period(): void
    {
        $this->grantAllocationPermissions();
        [$contract, $vehicle] = $this->makeLeaseContractWithVehicle();

        $this->postJson('/api/lease-allocations', [
            'leaseContractId' => $contract->id,
            'vehicleId' => $vehicle->id,
            'startDate' => '2026-10-16',
            'endDate' => '2026-10-18',
            'itineraryItems' => [
                ['date' => '2026-10-19', 'details' => 'Outside range'],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itineraryItems.0.date']);
    }

    private function grantAllocationPermissions(): void
    {
        foreach (['vehicles.create', 'vehicles.update'] as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['vehicles.create', 'vehicles.update']);
        Sanctum::actingAs($user);
    }

    /** @return array{LeaseContract, Vehicle} */
    private function makeLeaseContractWithVehicle(): array
    {
        $vehicle = Vehicle::create([
            'vehicle_no' => 'LEASE-ITIN-001',
            'plate_no' => 'T 123 ITN',
            'make' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2025,
            'seats' => 5,
            'chassis' => 'LEASE-ITIN-CHASSIS-001',
            'status' => 'On Lease',
        ]);

        $contract = LeaseContract::create([
            'client_name' => 'Itinerary Test Client',
            'lease_type' => 'Short-Term Lease',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'status' => 'Active',
        ]);
        $contract->vehicles()->attach($vehicle->id);

        return [$contract, $vehicle];
    }
}
