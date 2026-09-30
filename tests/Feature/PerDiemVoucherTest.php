<?php

namespace Tests\Feature;

use App\Models\JobCard;
use App\Models\PerDiemVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PerDiemVoucherTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_download_is_serialized_and_preserves_original_amounts(): void
    {
        Permission::findOrCreate('job-cards.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('job-cards.view');
        Sanctum::actingAs($user);
        $card = JobCard::create([
            'type' => 'Others', 'status' => 'Open', 'job_card_no' => 'JC-TEST',
            'driver_details' => 'Test Driver',
            'route_itinerary' => [
                ['date' => '2026-09-17', 'dayDescription' => 'Arusha', 'allowancePerDay' => 10.10],
                ['date' => '2026-09-18', 'dayDescription' => 'Safari', 'allowancePerDay' => 20.20],
            ],
        ]);
        $url = '/api/job-cards/'.$card->id.'/per-diem';
        $body = ['driverKey' => 'manual-'.hash('sha256', 'Test Driver')];
        $this->postJson($url, $body)->assertOk()->assertHeader('content-type', 'application/pdf');
        $voucher = PerDiemVoucher::sole();
        $this->assertEquals(30.30, $voucher->snapshot['total']);
        $this->assertEquals($user->name, $voucher->snapshot['preparedBy']);
        $this->getJson('/api/job-cards')->assertOk()
            ->assertJsonPath('jobCards.0.perDiemCount', 1)
            ->assertJsonPath('jobCards.0.perDiemUpdatedAt', $voucher->fresh()->updated_at->toISOString());
        $card->update(['route_itinerary' => []]);
        $this->postJson($url, $body)->assertConflict();
        $this->getJson('/api/per-diems?jobCardId='.$card->id)->assertOk()
            ->assertJsonPath('perDiems.0.number', $voucher->number())
            ->assertJsonCount(1, 'perDiems');
        $this->get('/api/per-diems/'.$voucher->id.'/pdf')->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $card->update(['route_itinerary' => [
            ['date' => '2026-09-17', 'dayDescription' => 'Updated Arusha', 'allowancePerDay' => 11.11],
        ]]);
        $this->postJson($url, $body)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertDatabaseCount('per_diem_vouchers', 1);
        $this->assertEquals($voucher->id, $voucher->fresh()->id);
        $this->assertEquals(11.11, $voucher->fresh()->snapshot['total']);
        $this->postJson($url, ['driverKey' => 'unassigned'])->assertUnprocessable();
    }

    public function test_missing_allowances_are_rejected_without_creating_vouchers(): void
    {
        Permission::findOrCreate('job-cards.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('job-cards.view');
        Sanctum::actingAs($user);
        $card = JobCard::create([
            'type' => 'Others', 'status' => 'Open', 'driver_details' => 'Driver',
            'route_itinerary' => [['date' => 'Day 1', 'allowancePerDay' => null]],
        ]);
        $this->postJson('/api/job-cards/'.$card->id.'/per-diem', [
            'driverKey' => 'manual-'.hash('sha256', 'Driver'),
        ])->assertUnprocessable();
        $this->assertDatabaseCount('per_diem_vouchers', 0);
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/job-cards/'.$card->id.'/per-diem')->assertForbidden();
    }
}
