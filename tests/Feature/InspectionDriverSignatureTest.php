<?php

namespace Tests\Feature;

use App\Models\LeaseAllocation;
use App\Models\LeaseContract;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InspectionDriverSignatureTest extends TestCase
{
    use RefreshDatabase;

    // 1x1 transparent PNG.
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    public function test_driver_signature_is_stored_and_returned(): void
    {
        Storage::fake('public');
        [$user, $vehicle, $allocation] = $this->seedLease();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/inspections', $this->payload($allocation->id, $vehicle->id) + [
            'driver_signature' => 'data:image/png;base64,' . self::PNG_BASE64,
        ])->assertCreated();

        $path = $response->json('inspection.driverSignature');
        $this->assertNotEmpty($path);
        $this->assertStringStartsWith('inspection-signatures/', $path);
        $this->assertNotNull($response->json('inspection.driverSignedAt'));
        $this->assertSame($user->name, $response->json('inspection.driverName'));
        Storage::disk('public')->assertExists($path);

        // Re-signing on update replaces the stored file.
        $id = $response->json('inspection.id');
        $updated = $this->putJson("/api/inspections/{$id}", [
            'driver_signature' => self::PNG_BASE64,
        ])->assertOk();

        $newPath = $updated->json('inspection.driverSignature');
        $this->assertNotSame($path, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_saved_user_signature_is_applied_as_manager_approval(): void
    {
        Storage::fake('public');
        [$user, $vehicle, $allocation] = $this->seedLease();
        Sanctum::actingAs($user);

        $saved = $this->postJson('/api/me/signature', [
            'signature' => 'data:image/png;base64,' . self::PNG_BASE64,
        ])->assertOk();
        $userSignature = $saved->json('signature');
        Storage::disk('public')->assertExists($userSignature);
        $this->getJson('/api/me/signature')->assertJsonPath('signature', $userSignature);

        $response = $this->postJson('/api/inspections', $this->payload($allocation->id, $vehicle->id) + [
            'driver_signature' => self::PNG_BASE64,
            'driver_name' => 'Juma Guide',
        ])->assertCreated();

        $response->assertJsonPath('inspection.driverName', 'Juma Guide')
            ->assertJsonPath('inspection.managerName', $user->name);
        $managerPath = $response->json('inspection.managerSignature');
        $this->assertNotSame($userSignature, $managerPath, 'inspection keeps its own copy');
        Storage::disk('public')->assertExists($managerPath);

        $this->deleteJson('/api/me/signature')->assertOk()->assertJsonPath('signature', null);
        Storage::disk('public')->assertExists($managerPath);
    }

    public function test_non_png_signature_is_rejected(): void
    {
        Storage::fake('public');
        [$user, $vehicle, $allocation] = $this->seedLease();
        Sanctum::actingAs($user);

        $this->postJson('/api/inspections', $this->payload($allocation->id, $vehicle->id) + [
            'driver_signature' => base64_encode('not an image'),
        ])->assertStatus(422)->assertJsonValidationErrors('driver_signature');

        $this->assertDatabaseCount('inspections', 0);
    }

    public function test_inspection_without_signature_still_saves(): void
    {
        [$user, $vehicle, $allocation] = $this->seedLease();
        Sanctum::actingAs($user);

        $this->postJson('/api/inspections', $this->payload($allocation->id, $vehicle->id))
            ->assertCreated()
            ->assertJsonPath('inspection.driverSignature', null);
    }

    /** @return array{0: User, 1: Vehicle, 2: LeaseAllocation} */
    private function seedLease(): array
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'vehicle_no' => 'SIG-TEST-001',
            'plate_no' => 'T 200 SIG',
            'make' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2025,
            'seats' => 7,
            'chassis' => 'SIG-TEST-CHASSIS-001',
            'status' => 'Assigned',
        ]);
        $contract = LeaseContract::create([
            'client_name' => 'Signature Test Client',
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

        return [$user, $vehicle, $allocation];
    }

    /** @return array<string, mixed> */
    private function payload(int $allocationId, int $vehicleId): array
    {
        return [
            'type' => 'pre_departure',
            'checklistType' => 'pre_departure',
            'lead' => ['id' => null],
            'leaseAllocationId' => $allocationId,
            'vehicle' => ['id' => $vehicleId],
            'odometer' => 1000,
            'items' => [[
                'checklist_id' => 1,
                'checklist_title' => 'Vehicle condition',
                'name' => 'Tyres',
                'text' => 'Tyres',
                'status' => 'OK',
                'issue' => '',
            ]],
        ];
    }
}
