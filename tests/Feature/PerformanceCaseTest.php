<?php

namespace Tests\Feature;

use App\Models\{IncidentAttachment, IncidentReport, Lead, LeaseAllocation, LeaseContract, SafariAllocation, User, Vehicle};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PerformanceCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        // Build the incident module's schema without the unrelated fuel migration chain.
        $paths = array_values(array_filter(glob(database_path('migrations/*.php')), function ($path) {
            $name = basename($path);
            return $name < '2026_09_08' || str_contains($name, 'add_performance_workflow');
        }));
        return ['--path' => $paths, '--realpath' => true];
    }

    private function setupBooking(): array
    {
        $user = User::factory()->create();
        foreach (['vehicles.view', 'vehicles.update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }
        Sanctum::actingAs($user);
        $driver = User::factory()->create(['name' => 'Booking Driver']);
        $lead = Lead::create(['booking_ref' => 'CASE-BOOKING', 'client_company' => 'Client', 'agent_contact' => 'Agent', 'agent_email' => 'agent@example.com', 'agent_phone' => '123', 'client_country' => 'Kenya', 'start_date' => '2026-09-01', 'end_date' => '2026-09-05', 'route_parks' => 'Park']);
        $vehicle = Vehicle::create(['vehicle_no' => 'CASE1', 'plate_no' => 'T100CASE', 'make' => 'Toyota', 'model' => 'Test', 'year' => 2020, 'seats' => 7, 'chassis' => 'CASECHASSIS', 'assigned_driver_id' => $user->id]);
        $trip = SafariAllocation::create(['lead_id' => $lead->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]);
        return [$user, $trip, $driver];
    }

    private function createCase(SafariAllocation $trip): array
    {
        return $this->postJson('/api/performance-cases', [
            'booking' => 'safari:' . $trip->id, 'type' => 'Client Complaint', 'date' => '2026-09-03',
            'severity' => 'High', 'description' => 'Reported concern', 'caseReference' => 'FORGED',
        ])->assertCreated()->json('case');
    }

    public function test_booking_reference_generation_stage_persistence_and_conflicts(): void
    {
        [, $trip, $driver] = $this->setupBooking();
        $this->getJson('/api/performance-cases/bookings')->assertOk()
            ->assertJsonPath('bookings.0.reference', 'CASE-BOOKING')
            ->assertJsonPath('bookings.0.driver', $driver->name);
        $case = $this->createCase($trip);
        $another = $this->createCase($trip);
        $this->assertNotSame($case['reference'], $another['reference']);
        $this->assertStringStartsWith('SHER-INC-', $case['reference']);
        $this->assertNotSame('FORGED', $case['reference']);
        $this->assertSame($driver->name, $case['booking']['driver']);
        $this->assertDatabaseHas('incident_reports', ['id' => $case['id'], 'driver_user_id' => $driver->id, 'vehicle_id' => $trip->vehicle_id]);
        $path = '/api/performance-cases/' . $case['id'];
        $saved = $this->putJson($path . '/stages/M', [
            'revision' => 1, 'state' => 'Completed',
            'values' => ['required' => 'Yes', 'taken' => 'Yes', 'actionDate' => '2026-09-05', 'reviewDate' => '2026-09-20', 'action' => 'Coaching'],
        ])->assertOk()->assertJsonPath('case.stages.M.values.reviewDate', '2026-09-20')->json('case');
        $this->getJson($path)->assertOk()->assertJsonPath('case.stages.M.values.action', 'Coaching');
        $this->putJson($path . '/stages/M', ['revision' => 1, 'state' => 'Completed', 'values' => ['action' => 'Stale update']])->assertConflict();
        $this->putJson($path . '/stages/M', ['revision' => 2, 'state' => 'Completed', 'values' => ['reviewDate' => 'not a date']])->assertUnprocessable();
        $this->putJson($path . '/stages/O', ['revision' => $saved['revision'], 'state' => 'In progress', 'values' => ['actions' => [
            ['action' => 'Review procedure', 'status' => 'Closed', 'finding' => 'Procedure updated'],
        ]]])->assertOk()->assertJsonPath('case.stages.O.values.actions.0.finding', 'Procedure updated');
        $this->putJson($path . '/stages/O', ['revision' => 3, 'state' => 'Completed', 'values' => ['actions' => [['action' => 'Invalid', 'status' => 'Complete']]]])->assertUnprocessable();
        $this->putJson($path . '/stages/R', ['revision' => 3, 'state' => 'Completed', 'values' => ['status' => 'Closed']])->assertUnprocessable();
        $this->putJson($path . '/stages/R', ['revision' => 3, 'state' => 'Completed', 'values' => ['status' => 'Closed', 'closureDate' => '2026-09-10', 'closureNote' => 'Completed and reviewed']])
            ->assertOk()->assertJsonPath('case.status', 'Closed');
        $this->assertDatabaseHas('incident_reports', ['id' => $case['id'], 'status' => 'Closed', 'closing_remarks' => 'Completed and reviewed']);
        $this->postJson($path . '/feedback', ['revision' => 4, 'type' => 'Positive', 'rating' => 5, 'remarks' => 'Good response'])
            ->assertOk()->assertJsonPath('case.feedback.0.remarks', 'Good response');
        $this->getJson('/api/incident-reports/' . $case['id'])->assertOk()->assertJsonPath('incidentReport.status', 'Closed');
    }

    public function test_all_stages_save_empty_drafts_and_valid_rows(): void
    {
        [, $trip] = $this->setupBooking();
        $case = $this->createCase($trip);
        $schema = $this->getJson('/api/performance-cases')->assertOk()->json('schema');
        $revision = 1;
        foreach ($schema as $letter => $stage) {
            $values = [];
            foreach ($stage['fields'] as $field) {
                $values[$field['key']] = $field['default'] ?? (in_array($field['type'], ['rows', 'list', 'checklist']) ? [] : '');
            }
            if ($letter === 'A') {
                $values = array_merge($values, ['incidentDate' => $case['date'], 'severity' => $case['severity'], 'type' => $case['type']]);
            }
            if ($letter === 'C') {
                $values['summary'] = $case['description'];
            }
            $this->putJson('/api/performance-cases/' . $case['id'] . '/stages/' . $letter, [
                'revision' => $revision++, 'state' => 'In progress', 'values' => $values,
            ])->assertOk();
        }
        $this->assertSame(19, IncidentReport::findOrFail($case['id'])->performance_revision);
    }

    public function test_lease_booking_and_immutable_generated_fields(): void
    {
        [, $trip, $driver] = $this->setupBooking();
        $contract = LeaseContract::create(['client_name' => 'Lease Client', 'lease_type' => 'Long-Term Lease', 'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'status' => 'Active']);
        $lease = LeaseAllocation::create(['lease_contract_id' => $contract->id, 'vehicle_id' => $trip->vehicle_id, 'driver_id' => $driver->id, 'start_date' => '2026-09-01', 'end_date' => '2026-12-01']);
        $case = $this->createCase($trip);
        $this->putJson('/api/performance-cases/' . $case['id'] . '/stages/A', [
            'revision' => 1, 'booking' => 'lease:' . $lease->id, 'state' => 'Completed',
            'values' => ['caseReference' => 'FORGED', 'vehicle' => 'FORGED', 'driver' => 'FORGED'],
        ])->assertOk()->assertJsonPath('case.reference', $case['reference'])
            ->assertJsonPath('case.stages.A.values.driver', $driver->name)
            ->assertJsonPath('case.booking.client', 'Lease Client');
        $this->assertDatabaseHas('incident_reports', ['id' => $case['id'], 'lead_id' => null, 'safari_allocation_id' => null, 'lease_allocation_id' => $lease->id]);
    }

    public function test_attachments_are_private_scoped_validated_and_removable(): void
    {
        Storage::fake('local');
        [, $trip] = $this->setupBooking();
        $case = $this->createCase($trip);
        $another = $this->createCase($trip);
        $path = '/api/performance-cases/' . $case['id'];
        $uploaded = $this->post($path . '/attachments', [
            'stage' => 'E', 'files' => [UploadedFile::fake()->createWithContent('evidence.txt', 'Evidence details')],
        ], ['Accept' => 'application/json'])->assertCreated()->json('case.attachments.0');
        $this->assertArrayNotHasKey('path', $uploaded);
        $attachment = IncidentAttachment::findOrFail($uploaded['id']);
        Storage::disk('local')->assertExists($attachment->path);
        $this->get($path . '/attachments/' . $attachment->id)->assertOk()->assertDownload('evidence.txt');
        $this->get('/api/performance-cases/' . $another['id'] . '/attachments/' . $attachment->id)->assertNotFound();
        $this->deleteJson('/api/performance-cases/' . $another['id'] . '/attachments/' . $attachment->id)->assertNotFound();
        $this->post($path . '/attachments', ['files' => [UploadedFile::fake()->createWithContent('unsafe.php', '<?php echo "unsafe";')]], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post($path . '/attachments', ['files' => [UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')]], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->deleteJson($path . '/attachments/' . $attachment->id)->assertOk()->assertJsonCount(0, 'case.attachments');
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_existing_incidents_keep_actions_and_closure_details(): void
    {
        [, $trip, $driver] = $this->setupBooking();
        $report = IncidentReport::create([
            'incident_date' => '2026-09-03', 'vehicle_id' => $trip->vehicle_id,
            'lead_id' => $trip->lead_id, 'driver_name' => $driver->name,
            'report_type' => 'Accident', 'description' => 'Existing incident',
            'action_taken' => 'Existing recovery action', 'status' => 'Closed',
            'closing_remarks' => 'Existing closure note',
        ]);
        $this->getJson('/api/performance-cases/' . $report->id)->assertOk()
            ->assertJsonPath('case.stages.D.values.actions.0.action', 'Existing recovery action')
            ->assertJsonPath('case.stages.R.values.closureNote', 'Existing closure note')
            ->assertJsonPath('case.booking.reference', 'CASE-BOOKING')
            ->assertJsonPath('case.status', 'Closed');
    }

    public function test_read_only_and_unauthorized_access(): void
    {
        [$user, $trip] = $this->setupBooking();
        $case = $this->createCase($trip);
        $user->revokePermissionTo('vehicles.update');
        $this->getJson('/api/performance-cases')->assertOk();
        $this->postJson('/api/performance-cases', [])->assertForbidden();
        $this->putJson('/api/performance-cases/' . $case['id'] . '/stages/M', [])->assertForbidden();
        $this->postJson('/api/performance-cases/' . $case['id'] . '/attachments', [])->assertForbidden();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/performance-cases')->assertForbidden();
        $this->getJson('/api/performance-cases/bookings')->assertForbidden();
    }

    public function test_feedback_revisions_protect_stage_drafts(): void
    {
        [, $trip] = $this->setupBooking();
        $case = $this->createCase($trip);
        $path = '/api/performance-cases/' . $case['id'];
        $feedback = ['revision' => 1, 'type' => 'General', 'rating' => 4, 'remarks' => 'Follow-up'];
        $this->postJson($path . '/feedback', $feedback)->assertOk()->assertJsonPath('case.revision', 2);
        $this->postJson($path . '/feedback', $feedback)->assertConflict();
        $this->putJson($path . '/stages/M', ['revision' => 2, 'state' => 'In progress', 'values' => ['action' => 'Draft retained']])
            ->assertOk()->assertJsonPath('case.stages.M.values.action', 'Draft retained')->assertJsonCount(1, 'case.feedback');
    }

    public function test_core_fields_cannot_be_silently_cleared_and_type_is_editable(): void
    {
        [, $trip] = $this->setupBooking();
        $case = $this->createCase($trip);
        $path = '/api/performance-cases/' . $case['id'];
        foreach (['A' => ['incidentDate' => ''], 'C' => ['summary' => ''], 'R' => ['status' => '']] as $letter => $values) {
            $this->putJson($path . '/stages/' . $letter, ['revision' => 1, 'state' => 'In progress', 'values' => $values])->assertUnprocessable();
        }
        $this->putJson($path . '/stages/A', ['revision' => 1, 'state' => 'Completed', 'values' => ['type' => 'Routine']])
            ->assertOk()->assertJsonPath('case.type', 'Routine');
        $this->assertDatabaseHas('incident_reports', ['id' => $case['id'], 'report_type' => 'Routine']);
    }

    public function test_partial_stage_updates_preserve_fields_and_allow_explicit_clearing(): void
    {
        [, $trip] = $this->setupBooking();
        $case = $this->createCase($trip);
        $path = '/api/performance-cases/' . $case['id'] . '/stages/M';
        $this->putJson($path, ['revision' => 1, 'state' => 'In progress', 'values' => [
            'action' => 'Coaching', 'actionDate' => '2026-09-10', 'reviewDate' => '2026-09-20',
        ]])->assertOk();
        $this->putJson($path, ['revision' => 2, 'state' => 'Completed', 'values' => [
            'reviewDate' => '2026-09-25',
        ]])->assertOk()->assertJsonPath('case.stages.M.values.action', 'Coaching')
            ->assertJsonPath('case.stages.M.values.actionDate', '2026-09-10')
            ->assertJsonPath('case.stages.M.values.reviewDate', '2026-09-25');
        $this->putJson($path, ['revision' => 3, 'state' => 'In progress', 'values' => [
            'reviewDate' => null,
        ]])->assertOk()->assertJsonPath('case.stages.M.values.reviewDate', null)
            ->assertJsonPath('case.stages.M.values.action', 'Coaching');
    }

    public function test_dashboard_updates_invalidate_drafts_and_synchronize_case_fields(): void
    {
        [, $trip] = $this->setupBooking();
        $case = $this->createCase($trip);
        $path = '/api/performance-cases/' . $case['id'];
        $this->putJson($path . '/stages/D', ['revision' => 1, 'state' => 'Completed', 'values' => ['actions' => [
            ['action' => 'Arrange repair', 'status' => 'Closed', 'finding' => 'Repaired'],
        ]]])->assertOk();
        $this->assertDatabaseHas('incident_reports', ['id' => $case['id'], 'action_taken' => 'Arrange repair']);
        $this->putJson('/api/incident-reports/' . $case['id'], [
            'date' => '2026-09-05', 'description' => 'Updated via dashboard', 'actionTaken' => 'Replacement provided',
        ])->assertOk();
        $this->getJson($path)->assertOk()->assertJsonPath('case.stages.A.values.incidentDate', '2026-09-05')
            ->assertJsonPath('case.stages.C.values.summary', 'Updated via dashboard')
            ->assertJsonPath('case.stages.D.values.actions.0.action', 'Replacement provided');
        $this->putJson($path . '/stages/M', ['revision' => 2, 'state' => 'In progress', 'values' => ['action' => 'Stale']])->assertConflict();
    }
}
