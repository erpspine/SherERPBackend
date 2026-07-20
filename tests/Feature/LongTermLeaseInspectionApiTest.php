<?php

namespace Tests\Feature;

use App\Models\LeaseAllocation;
use App\Models\LeaseContract;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LongTermLeaseInspectionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pre_and_post_inspections_save_for_a_long_term_lease(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'vehicle_no' => 'LEASE-TEST-001',
            'plate_no' => 'T 100 LTL',
            'make' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2025,
            'seats' => 7,
            'chassis' => 'LEASE-TEST-CHASSIS-001',
            'status' => 'Assigned',
        ]);
        $contract = LeaseContract::create([
            'client_name' => 'Long Term Test Client',
            'lease_type' => 'Long-Term Lease',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'Active',
        ]);
        $allocation = LeaseAllocation::create([
            'lease_contract_id' => $contract->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $user->id,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'In Progress',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/my-lease-allocations?compact=1')
            ->assertOk()
            ->assertJsonCount(1, 'allocations')
            ->assertJsonPath('allocations.0.id', $allocation->id)
            ->assertJsonPath('allocations.0.contract.clientName', 'Long Term Test Client')
            ->assertJsonMissingPath('allocations.0.itinerary');

        $preResponse = $this->postJson('/api/inspections', $this->payload(
            allocationId: $allocation->id,
            vehicleId: $vehicle->id,
            type: 'pre_departure',
            odometer: 12000,
        ));

        $preResponse
            ->assertCreated()
            ->assertJsonPath('inspection.type', 'pre_departure')
            ->assertJsonPath('inspection.leaseAllocationId', $allocation->id)
            ->assertJsonPath('inspection.lead.id', 'lease:'.$allocation->id);

        $postResponse = $this->postJson('/api/inspections', $this->payload(
            allocationId: $allocation->id,
            vehicleId: $vehicle->id,
            type: 'post_departure',
            odometer: 12500,
        ));

        $postResponse
            ->assertCreated()
            ->assertJsonPath('inspection.type', 'post_departure')
            ->assertJsonPath('inspection.leaseAllocationId', $allocation->id)
            ->assertJsonPath('inspection.odometerIn', 12500);

        $this->assertDatabaseHas('inspections', [
            'lease_allocation_id' => $allocation->id,
            'lead_id' => null,
            'vehicle_id' => $vehicle->id,
            'type' => 'pre_departure',
            'odometer_out' => 12000,
        ]);
        $this->assertDatabaseHas('inspections', [
            'lease_allocation_id' => $allocation->id,
            'lead_id' => null,
            'vehicle_id' => $vehicle->id,
            'type' => 'post_departure',
            'odometer_in' => 12500,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(int $allocationId, int $vehicleId, string $type, int $odometer): array
    {
        return [
            'type' => $type,
            'checklistType' => $type,
            'lead' => ['id' => null],
            'leaseAllocationId' => $allocationId,
            'vehicle' => ['id' => $vehicleId],
            'odometer' => $odometer,
            'odometer_reading' => $odometer,
            $type === 'pre_departure' ? 'odometer_out' : 'odometer_in' => $odometer,
            'parkingLocation' => 'Main yard',
            'parking_location' => 'Main yard',
            'items' => [[
                'id' => 1,
                'checklist_id' => 1,
                'checklist_title' => 'Vehicle condition',
                'name' => 'Tyres',
                'text' => 'Tyres',
                'status' => 'OK',
                'issue' => '',
            ]],
            'remarks' => 'Long-term lease API test',
            'images' => [],
        ];
    }
}
