<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Lead;
use App\Models\SafariAllocation;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_without_required_permission_gets_forbidden_response(): void
    {
        $driver = $this->createUserWithRole('Driver');

        Sanctum::actingAs($driver);

        $this->getJson('/api/clients')->assertForbidden();
    }

    public function test_user_with_required_permission_can_access_protected_route(): void
    {
        $viewer = $this->createUserWithRole('Viewer');

        Sanctum::actingAs($viewer);

        $this->getJson('/api/clients')
            ->assertOk()
            ->assertJson([
                'message' => 'Clients fetched successfully.',
            ]);
    }

    public function test_driver_only_sees_own_safari_allocations_and_cannot_view_others(): void
    {
        $driver = $this->createUserWithRole('Driver');
        $otherDriver = $this->createUserWithRole('Driver');

        $ownLead = $this->createLead('OWN-ALLOC');
        $otherLead = $this->createLead('OTHER-ALLOC');
        $vehicleA = $this->createVehicle('VEH-ALLOC-A');
        $vehicleB = $this->createVehicle('VEH-ALLOC-B');

        $ownAllocation = SafariAllocation::create([
            'lead_id' => $ownLead->id,
            'vehicle_id' => $vehicleA->id,
            'driver_id' => $driver->id,
            'status' => 'Assigned',
        ]);

        $otherAllocation = SafariAllocation::create([
            'lead_id' => $otherLead->id,
            'vehicle_id' => $vehicleB->id,
            'driver_id' => $otherDriver->id,
            'status' => 'Assigned',
        ]);

        Sanctum::actingAs($driver);

        $this->getJson('/api/safari-allocations')
            ->assertOk()
            ->assertJsonCount(1, 'allocations')
            ->assertJsonPath('allocations.0.id', $ownAllocation->id);

        $this->getJson('/api/safari-allocations/' . $otherAllocation->id)
            ->assertForbidden();
    }

    public function test_driver_only_sees_job_cards_for_owned_assignments(): void
    {
        $driver = $this->createUserWithRole('Driver');
        $otherDriver = $this->createUserWithRole('Driver');

        $ownLead = $this->createLead('OWN-JOB');
        $otherLead = $this->createLead('OTHER-JOB');
        $vehicleA = $this->createVehicle('VEH-JOB-A');
        $vehicleB = $this->createVehicle('VEH-JOB-B');

        SafariAllocation::create([
            'lead_id' => $ownLead->id,
            'vehicle_id' => $vehicleA->id,
            'driver_id' => $driver->id,
            'status' => 'Assigned',
        ]);

        SafariAllocation::create([
            'lead_id' => $otherLead->id,
            'vehicle_id' => $vehicleB->id,
            'driver_id' => $otherDriver->id,
            'status' => 'Assigned',
        ]);

        $ownJobCard = JobCard::create([
            'lead_id' => $ownLead->id,
            'job_card_no' => 'JC-OWN-0001',
            'booking_reference_no' => 'BR-OWN-0001',
            'tour_operator_client_name' => 'Own Client',
            'contact_person' => 'Own Contact',
            'contact_number' => '+255700000001',
            'adults' => 2,
            'children' => 0,
            'guide_language' => 'English',
            'safari_start_date' => now()->toDateString(),
            'safari_end_date' => now()->addDays(2)->toDateString(),
            'number_of_days' => 3,
        ]);

        $otherJobCard = JobCard::create([
            'lead_id' => $otherLead->id,
            'job_card_no' => 'JC-OTHER-0001',
            'booking_reference_no' => 'BR-OTHER-0001',
            'tour_operator_client_name' => 'Other Client',
            'contact_person' => 'Other Contact',
            'contact_number' => '+255700000002',
            'adults' => 4,
            'children' => 1,
            'guide_language' => 'English',
            'safari_start_date' => now()->toDateString(),
            'safari_end_date' => now()->addDays(4)->toDateString(),
            'number_of_days' => 5,
        ]);

        Sanctum::actingAs($driver);

        $this->getJson('/api/job-cards')
            ->assertOk()
            ->assertJsonCount(1, 'jobCards')
            ->assertJsonPath('jobCards.0.id', $ownJobCard->id);

        $this->getJson('/api/job-cards/' . $otherJobCard->id)
            ->assertForbidden();
    }

    public function test_finance_user_can_approve_invoice(): void
    {
        $finance = $this->createUserWithRole('Finance');
        $invoice = Invoice::create([
            'invoice_no' => 'INV-FIN-0001',
            'client' => 'Finance Client',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total' => 1200,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($finance);

        $this->postJson('/api/invoices/' . $invoice->id . '/approve')
            ->assertOk()
            ->assertJsonPath('invoice.status', 'approved');
    }

    public function test_non_finance_user_cannot_approve_invoice(): void
    {
        $operations = $this->createUserWithRole('Operations');
        $invoice = Invoice::create([
            'invoice_no' => 'INV-OPS-0001',
            'client' => 'Operations Client',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total' => 1500,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($operations);

        $this->postJson('/api/invoices/' . $invoice->id . '/approve')
            ->assertForbidden();
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'Active',
        ]);

        $user->syncRoles([$role]);

        return $user->fresh();
    }

    private function createLead(string $bookingRef): Lead
    {
        return Lead::create([
            'booking_ref' => $bookingRef,
            'client_company' => 'Sher Client ' . $bookingRef,
            'agent_contact' => 'Agent ' . $bookingRef,
            'agent_email' => strtolower($bookingRef) . '@example.com',
            'agent_phone' => '+255700000000',
            'client_country' => 'TZ',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'route_parks' => 'Serengeti',
            'pax_adults' => 2,
            'pax_children' => 0,
            'no_of_vehicles' => 1,
            'booking_status' => 'Confirmed',
        ]);
    }

    private function createVehicle(string $vehicleNo): Vehicle
    {
        return Vehicle::create([
            'vehicle_no' => $vehicleNo,
            'plate_no' => $vehicleNo . '-PLATE',
            'make' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2024,
            'seats' => 6,
            'chassis' => $vehicleNo . '-CHASSIS',
            'status' => 'Available',
        ]);
    }
}
