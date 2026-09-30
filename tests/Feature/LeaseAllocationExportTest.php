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

class LeaseAllocationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_keeps_original_dates_by_default(): void
    {
        $this->grantVehiclesViewPermission();
        $this->seedOverlappingAllocation();

        $response = $this->get('/api/lease-allocations/export/excel?dateFrom=2026-07-01&dateTo=2026-07-31');

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('28 June 2026', $content);
        $this->assertStringContainsString('05 July 2026', $content);
        $this->assertStringContainsString(',5,', $content);
    }

    public function test_csv_export_can_clip_overlapping_dates_to_selected_period(): void
    {
        $this->grantVehiclesViewPermission();
        $this->seedOverlappingAllocation();

        $response = $this->get('/api/lease-allocations/export/excel?dateFrom=2026-07-01&dateTo=2026-07-31&clipOverlapDates=1');

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('01 July 2026', $content);
        $this->assertStringContainsString('05 July 2026', $content);
        $this->assertStringContainsString(',5,', $content);
        $this->assertStringNotContainsString('28 June 2026', $content);
    }

    private function grantVehiclesViewPermission(): void
    {
        Permission::findOrCreate('vehicles.view', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('vehicles.view');

        Sanctum::actingAs($user);
    }

    private function seedOverlappingAllocation(): void
    {
        $vehicle = Vehicle::create([
            'vehicle_no' => 'LEASE-EXP-001',
            'plate_no' => 'T 321 EXP',
            'make' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2025,
            'seats' => 5,
            'chassis' => 'LEASE-EXP-CHASSIS-001',
            'status' => 'Assigned',
        ]);

        $contract = LeaseContract::create([
            'client_name' => 'Export Test Client',
            'lease_type' => 'Long-Term Lease',
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'status' => 'Active',
        ]);

        $contract->vehicles()->attach($vehicle->id);

        LeaseAllocation::create([
            'lease_contract_id' => $contract->id,
            'group_name' => 'Overlap Group',
            'vehicle_id' => $vehicle->id,
            'start_date' => '2026-06-28',
            'end_date' => '2026-07-05',
            'status' => 'In Progress',
        ]);
    }
}
