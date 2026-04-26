<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\FuelRequisition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FuelRequisitionCreateContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_store_accepts_frontend_contract_payload(): void
    {
        $user = $this->createUserWithRole('Operations');
        $lead = $this->createLead('FUEL-CONTRACT-001');

        Sanctum::actingAs($user);

        $payload = [
            'leadId' => $lead->id,
            'litres' => 189.5,
            'baseRatePerKm' => 0.5,
            'reason' => 'Fuel for safari operations',
            'transportItinerary' => [
                [
                    'day' => 1,
                    'date' => '2026-04-26',
                    'destinations' => [
                        [
                            'destinationFrom' => 'Arusha',
                            'destinationTo' => 'Tarangire',
                            'distanceKm' => 120,
                        ],
                        [
                            'destinationFrom' => 'Tarangire',
                            'destinationTo' => 'Karatu',
                            'distanceKm' => 75,
                        ],
                    ],
                    'distanceKm' => 195,
                ],
                [
                    'day' => 2,
                    'date' => '2026-04-27',
                    'destinations' => [
                        [
                            'destinationFrom' => 'Karatu',
                            'destinationTo' => 'Ngorongoro',
                            'distanceKm' => 55,
                        ],
                    ],
                    'distanceKm' => 55,
                ],
            ],
            'totalDistanceKm' => 250,
            'totalFuelLitres' => 189.5,
        ];

        $response = $this->postJson('/api/fuel-requisitions', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('fuelRequisition.leadId', $lead->id)
            ->assertJsonPath('fuelRequisition.baseRatePerKm', 0.5)
            ->assertJsonPath('fuelRequisition.totalDistanceKm', 250)
            ->assertJsonPath('fuelRequisition.totalFuelLitres', 189.5)
            ->assertJsonPath('fuelRequisition.status', 'Pending');
    }

    public function test_store_rejects_inconsistent_day_and_total_distances(): void
    {
        $user = $this->createUserWithRole('Operations');
        $lead = $this->createLead('FUEL-CONTRACT-002');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/fuel-requisitions', [
            'leadId' => $lead->id,
            'litres' => 100,
            'baseRatePerKm' => 0.5,
            'reason' => 'Fuel request',
            'transportItinerary' => [
                [
                    'day' => 1,
                    'date' => '2026-04-26',
                    'destinations' => [
                        [
                            'destinationFrom' => 'Arusha',
                            'destinationTo' => '',
                            'distanceKm' => 20,
                        ],
                    ],
                    'distanceKm' => 25,
                ],
            ],
            'totalDistanceKm' => 25,
            'totalFuelLitres' => 100,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transportItinerary.0.distanceKm', 'totalDistanceKm']);
    }

    public function test_store_rejects_duplicate_requisition_for_same_lead(): void
    {
        $user = $this->createUserWithRole('Operations');
        $lead = $this->createLead('FUEL-CONTRACT-003');

        Sanctum::actingAs($user);

        $payload = [
            'leadId' => $lead->id,
            'litres' => 120,
            'baseRatePerKm' => 0.5,
            'reason' => 'Initial fuel request',
            'transportItinerary' => [
                [
                    'day' => 1,
                    'date' => '2026-04-26',
                    'destinations' => [
                        [
                            'destinationFrom' => 'Arusha',
                            'destinationTo' => 'Manyara',
                            'distanceKm' => 100,
                        ],
                    ],
                    'distanceKm' => 100,
                ],
            ],
            'totalDistanceKm' => 100,
            'totalFuelLitres' => 120,
        ];

        $this->postJson('/api/fuel-requisitions', $payload)->assertCreated();

        $this->postJson('/api/fuel-requisitions', array_merge($payload, ['reason' => 'Second request']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['leadId']);
    }

    public function test_patch_updates_status_and_note_for_approval_module(): void
    {
        $requester = $this->createUserWithRole('Operations');
        $responder = $this->createUserWithRole('Finance');
        $lead = $this->createLead('FUEL-CONTRACT-004');

        $fuelRequisition = FuelRequisition::create([
            'lead_id' => $lead->id,
            'user_id' => $requester->id,
            'litres' => 150,
            'base_rate_per_km' => 0.5,
            'reason' => 'Pending approval',
            'transport_itinerary' => [],
            'total_distance_km' => 0,
            'total_fuel_litres' => 150,
            'status' => 'Pending',
        ]);

        Sanctum::actingAs($responder);

        $this->patchJson('/api/fuel-requisitions/' . $fuelRequisition->id, [
            'status' => 'Approved',
            'note' => 'Approved by admin.',
        ])
            ->assertOk()
            ->assertJsonPath('id', $fuelRequisition->id)
            ->assertJsonPath('status', 'Approved')
            ->assertJsonPath('note', 'Approved by admin.')
            ->assertJsonPath('approvedBy', $responder->id)
            ->assertJsonPath('fuelRequisition.status', 'Approved')
            ->assertJsonPath('fuelRequisition.note', 'Approved by admin.')
            ->assertJsonPath('fuelRequisition.approvedBy.id', $responder->id);
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
}
