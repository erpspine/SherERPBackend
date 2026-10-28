<?php

namespace Tests\Feature;

use App\Models\JobCard;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JobCardStatusAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_approved_users_can_close_and_reopen_safari_job_cards(): void
    {
        $user = $this->createUser('arif@sher.co.tz');
        $jobCard = $this->createSafariJobCard();
        Sanctum::actingAs($user);

        $this->patchJson('/api/job-cards/' . $jobCard->id . '/status', [
            'status' => 'Closed',
            'safariEndDate' => '2026-10-24',
            'timeIn' => '18:30',
        ])->assertOk()->assertJsonPath('jobCard.status', 'Closed');

        $this->patchJson('/api/job-cards/' . $jobCard->id . '/status', [
            'status' => 'Open',
        ])->assertOk()->assertJsonPath('jobCard.status', 'Open');
    }

    public function test_other_users_cannot_open_or_close_safari_or_lease_job_cards(): void
    {
        $user = $this->createUser('other.user@example.com');
        $safariCard = $this->createSafariJobCard();
        $leaseCard = $this->createSafariJobCard('Long Term Lease');
        Sanctum::actingAs($user);

        $this->patchJson('/api/job-cards/' . $safariCard->id . '/status', [
            'status' => 'Closed',
            'safariEndDate' => '2026-10-24',
            'timeIn' => '18:30',
        ])->assertForbidden();

        $this->patchJson('/api/job-cards/' . $leaseCard->id . '/status', [
            'status' => 'Open',
        ])->assertForbidden();
    }

    public function test_general_job_card_update_cannot_change_safari_or_lease_status(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'Admin',
            'status' => 'Active',
        ]);
        $user->syncRoles(['Admin']);
        $jobCard = $this->createSafariJobCard();
        Sanctum::actingAs($user);

        $this->putJson('/api/job-cards/' . $jobCard->id, [
            'status' => 'Closed',
        ])->assertForbidden();
    }

    public function test_open_safari_job_card_remains_editable(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'Admin',
            'status' => 'Active',
        ]);
        $user->syncRoles(['Admin']);
        $lead = $this->createLead();
        $jobCard = $this->createSafariJobCard();
        $jobCard->update(['lead_id' => $lead->id]);
        Sanctum::actingAs($user);

        $this->putJson('/api/job-cards/' . $jobCard->id, [
            'routeSummary' => 'Updated open itinerary',
        ])->assertOk()
            ->assertJsonPath('jobCard.routeSummary', 'Updated open itinerary');
    }

    public function test_closed_safari_job_card_cannot_be_edited_until_reopened(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'Admin',
            'status' => 'Active',
        ]);
        $user->syncRoles(['Admin']);
        $jobCard = $this->createSafariJobCard();
        $jobCard->update(['status' => 'Closed']);
        Sanctum::actingAs($user);

        $this->putJson('/api/job-cards/' . $jobCard->id, [
            'routeSummary' => 'Should remain unchanged',
        ])->assertForbidden();
    }

    private function createUser(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => 'Viewer',
            'status' => 'Active',
        ]);
        $user->syncRoles(['Viewer']);
        return $user->fresh();
    }

    private function createSafariJobCard(string $type = 'Safari'): JobCard
    {
        return JobCard::create([
            'type' => $type,
            'status' => 'Open',
            'job_card_no' => 'JC-STATUS-' . uniqid(),
            'booking_reference_no' => 'STATUS-REF',
            'tour_operator_client_name' => 'Status Client',
            'contact_person' => 'Status Contact',
            'contact_number' => '+255700000000',
            'safari_start_date' => '2026-10-20',
            'safari_end_date' => '2026-10-24',
            'number_of_days' => 5,
        ]);
    }

    private function createLead(): Lead
    {
        $bookingRef = 'OPEN-EDIT-' . uniqid();

        return Lead::create([
            'booking_ref' => $bookingRef,
            'client_company' => 'Open Edit Client',
            'agent_contact' => 'Open Edit Contact',
            'agent_email' => 'open-edit@example.com',
            'agent_phone' => '+255700000000',
            'client_country' => 'TZ',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-24',
            'route_parks' => 'Serengeti',
            'pax_adults' => 2,
            'pax_children' => 0,
            'no_of_vehicles' => 1,
            'booking_status' => 'Confirmed',
        ]);
    }
}
