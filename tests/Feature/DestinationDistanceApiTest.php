<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DestinationDistanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_crud_destination_distances(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $createResponse = $this->postJson('/api/destination-distances', [
            'destinationFrom' => 'Arusha',
            'destinationTo' => 'Serengeti',
            'distanceKm' => 325,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('destinationDistance.destinationFrom', 'Arusha')
            ->assertJsonPath('destinationDistance.destinationTo', 'Serengeti')
            ->assertJsonPath('destinationDistance.distanceKm', 325);

        $id = $createResponse->json('destinationDistance.id');

        $this->getJson('/api/destination-distances?search=Arusha')
            ->assertOk()
            ->assertJsonCount(1, 'destinationDistances')
            ->assertJsonPath('destinationDistances.0.id', $id);

        $this->putJson('/api/destination-distances/' . $id, [
            'distanceKm' => 330,
        ])
            ->assertOk()
            ->assertJsonPath('destinationDistance.distanceKm', 330);

        $this->deleteJson('/api/destination-distances/' . $id)
            ->assertOk()
            ->assertJson([
                'message' => 'Destination distance deleted successfully.',
            ]);

        $this->getJson('/api/destination-distances')
            ->assertOk()
            ->assertJsonCount(0, 'destinationDistances');
    }

    public function test_distance_must_be_positive(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/destination-distances', [
            'destinationFrom' => 'Arusha',
            'destinationTo' => 'Serengeti',
            'distanceKm' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['distanceKm']);
    }
}
