<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserDetailsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_identification_and_bank_details_survive_create_edit_and_can_be_cleared(): void
    {
        Mail::fake();
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['role' => 'Admin']);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $base = ['name' => 'Test User', 'email' => 'details@example.test', 'phone' => '0712345678', 'role' => 'Viewer', 'status' => 'Active'];
        $details = ['tin' => '001234567', 'nida' => '00123456789012345678', 'bank_name' => 'Test Bank', 'bank_account_number' => '000123456789'];
        $response = $this->postJson('/api/users', $base + $details)->assertCreated()->assertJson(['user' => $details]);
        $id = $response->json('user.id');
        $this->assertDatabaseHas('users', ['id' => $id] + $details);
        $this->getJson('/api/users')->assertOk()->assertJsonFragment($details);
        $this->getJson("/api/users/$id")->assertOk()->assertJson(['user' => $details]);
        $this->putJson("/api/users/$id", $base)->assertOk()->assertJson(['user' => $details]);
        $details['bank_name'] = 'Updated Bank';
        $this->putJson("/api/users/$id", $base + $details)->assertOk()->assertJson(['user' => $details]);
        $cleared = array_fill_keys(array_keys($details), null);
        $this->putJson("/api/users/$id", $base + $cleared)->assertOk()->assertJson(['user' => $cleared]);
        $this->assertDatabaseHas('users', ['id' => $id] + $cleared);
        $this->putJson("/api/users/$id", $base + ['bank_account_number' => str_repeat('1', 101)])
            ->assertUnprocessable()->assertJsonValidationErrors('bank_account_number');
    }
}
