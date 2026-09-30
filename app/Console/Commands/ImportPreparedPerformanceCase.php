<?php

namespace App\Console\Commands;

use App\Models\{IncidentReport, Vehicle};
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportPreparedPerformanceCase extends Command
{
    protected $signature = 'performance:import-prepared-case {path : JSON containing the original A-R stages} {--vehicle= : Verified vehicle ID}';
    protected $description = 'Preserve a previously prepared A-R case in the incident database';

    public function handle(): int
    {
        $original = json_decode(file_get_contents($this->argument('path')), true, 512, JSON_THROW_ON_ERROR);
        $lookup = fn ($letter, $label) => collect($original[$letter]['fields'] ?? [])->first(fn ($field) => mb_strtolower($field['label']) === mb_strtolower($label))['value'] ?? null;
        $reference = $lookup('A', 'Case Reference');
        if (! $reference) {
            $this->error('The source must contain a case reference in Stage A.');
            return self::FAILURE;
        }
        if (IncidentReport::where('case_reference', $reference)->exists()) {
            $this->info('Prepared case is already saved; no changes made.');
            return self::SUCCESS;
        }
        $vehicle = Vehicle::findOrFail($this->option('vehicle'));
        $schema = json_decode(file_get_contents(resource_path('performance/stages.json')), true, 512, JSON_THROW_ON_ERROR);
        $plain = function ($value) use (&$plain): string {
            if (! is_array($value)) return (string) $value;
            $lines = [];
            foreach ($value as $key => $item) {
                $lines[] = (is_string($key) ? ucfirst($key) . ': ' : '- ') . $plain($item);
            }
            return implode("\n", $lines);
        };
        $stages = [];
        foreach ($schema as $letter => $stage) {
            $values = [];
            foreach ($stage['fields'] as $field) {
                $value = $lookup($letter, $field['label']);
                if ($value === null) $value = $field['default'] ?? (in_array($field['type'], ['list', 'rows', 'checklist']) ? [] : '');
                if ($field['type'] === 'date') $value = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? $value : '';
                if (in_array($field['type'], ['select', 'status']) && ! in_array($value, $field['options'])) $value = '';
                if (is_array($value) && ! in_array($field['type'], ['list', 'rows', 'checklist'])) $value = $plain($value);
                $values[$field['key']] = $value;
            }
            $stages[$letter] = [
                'state' => ($original[$letter]['status'] ?? '') === 'completed' ? 'Completed' : 'In progress',
                'values' => $values,
                'originalDetails' => implode("\n\n", array_map(fn ($field) => $field['label'] . "\n" . $plain($field['value']), $original[$letter]['fields'] ?? [])),
            ];
        }
        $reportedDate = Carbon::parse($lookup('A', 'Date Reported'))->toDateString();
        $stages['A']['values'] = array_merge($stages['A']['values'], [
            'dateReported' => $reportedDate, 'incidentDate' => '',
            'reportedBy' => $lookup('A', 'Reported By') ?? '',
            'assignedInvestigator' => $lookup('A', 'Assigned Investigator') ?? '',
            'severity' => $lookup('A', 'Overall Severity') ?? 'Medium',
            'safetyRisk' => $lookup('A', 'Safety Risk Identified') ?? '',
        ]);
        $stages['B']['values'] = ['classifications' => $lookup('B', 'Primary Classification') ?? [], 'operationalAreas' => $lookup('B', 'Related Operational Areas') ?? []];
        $summary = $lookup('C', 'Summary') ?? 'Prepared case imported from the original incident register.';
        $stages['C']['values'] = ['summary' => $summary, 'concerns' => $lookup('C', 'Concerns Raised') ?? []];
        $stages['D']['values']['actions'] = array_map(fn ($row) => [
            'action' => $row['action'] ?? '', 'status' => in_array($row['status'] ?? '', ['Complete', 'Closed']) ? 'Closed' : 'Open', 'finding' => $row['finding'] ?? '',
        ], $lookup('D', 'Action Register') ?? []);
        $stages['E']['values']['evidence'] = $lookup('E', 'Evidence Register') ?? [];
        foreach ($stages['E']['values']['evidence'] as &$row) {
            if (! in_array($row['status'] ?? '', ['Pending', 'Received', 'Reviewed', 'Unavailable'])) {
                $row['information'] = ($row['information'] ?? '') . "\nOriginal status: " . ($row['status'] ?? '');
                $row['status'] = 'Pending';
            }
        }
        unset($row);
        $stages['F']['values'] = ['programme' => $lookup('F', 'Planned Programme for the Day') ?? [], 'timeline' => array_map(fn ($row) => [
            'date' => '', 'time' => '', 'event' => ($row['time'] ?? '') . ' · ' . ($row['event'] ?? ''), 'evidence' => $row['evidence'] ?? '',
        ], $lookup('F', 'Breakdown Incident Timeline') ?? []), 'downtime' => $lookup('F', 'Approximate Vehicle Downtime') ?? ''];
        $groups = [];
        foreach ($original['G']['fields'] ?? [] as $field) {
            $key = explode(' · ', $field['label'])[0];
            $groups[$key][] = $field;
        }
        $stages['G']['values']['findings'] = array_map(fn ($fields) => [
            'concern' => $fields[0]['value'], 'account' => '', 'driverAccount' => '', 'evidence' => '', 'outcome' => '',
            'finding' => implode("\n\n", array_map(fn ($field) => $field['label'] . "\n" . $plain($field['value']), array_slice($fields, 1))),
            'impact' => '', 'action' => '',
        ], array_values($groups));
        foreach ($stages['I']['values']['reviews'] as &$row) {
            $row['finding'] = $lookup('I', $row['area'] . ' · Finding') ?? '';
            $originalStatus = $lookup('I', $row['area'] . ' · Status');
            if ($originalStatus) $row['finding'] .= "\nOriginal status: " . $originalStatus;
        }
        unset($row);
        foreach (['K' => ['key' => 'causes', 'columns' => ['area' => 'Area', 'cause' => 'Cause / Factor', 'finding' => 'Finding']],
                  'L' => ['key' => 'responsibilities', 'columns' => ['assessment' => 'Responsibility Assessment', 'details' => 'Details']]] as $letter => $config) {
            $rows = [];
            foreach ($original[$letter]['fields'] ?? [] as $field) {
                [$group, $label] = explode(' · ', $field['label'], 2);
                if ($letter === 'L') $rows[$group]['party'] = $group;
                foreach ($config['columns'] as $key => $expected) {
                    if ($label === $expected) $rows[$group][$key] = $field['value'];
                }
            }
            $stages[$letter]['values'][$config['key']] = array_values($rows);
        }
        $stages['N']['values']['impact'] = array_values(array_map(fn ($field) => $field['label'], array_filter($original['N']['fields'] ?? [], fn ($field) => is_string($field['value']) && str_starts_with($field['value'], 'Yes'))));
        $stages['O']['values']['actions'] = array_map(fn ($row) => [
            'action' => $row['action'] ?? '', 'status' => in_array($row['status'] ?? '', ['Complete', 'Closed']) ? 'Closed' : 'Open',
            'finding' => implode("\n", array_filter([
                isset($row['owner']) ? 'Owner: ' . $row['owner'] : null,
                isset($row['priority']) ? 'Priority: ' . $row['priority'] : null,
                ! empty(trim($row['deadline'] ?? '', '_ ')) ? 'Deadline: ' . $row['deadline'] : null,
                $row['finding'] ?? null,
            ])),
        ], $lookup('O', 'Corrective / Preventive Action Register') ?? []);
        $stages['R']['values']['approvals'] = array_map(fn ($value) => [
            'role' => explode(':', $value)[0], 'name' => '', 'approved' => str_contains($value, '☒'),
        ], $lookup('R', 'Management Approval') ?? []);
        $status = 'Open';
        foreach ($lookup('R', 'Case Status') ?? [] as $value) {
            if (str_starts_with($value, '☒')) $status = trim(mb_substr($value, 1));
        }
        $stages['R']['values']['status'] = $status;
        $driver = $lookup('A', 'Driver-Guide') ?? 'Unassigned';
        $report = DB::transaction(function () use ($reference, $vehicle, $reportedDate, $summary, $lookup, $stages, $status, $driver) {
            return IncidentReport::create([
                'case_reference' => $reference, 'vehicle_id' => $vehicle->id, 'driver_name' => $driver,
                'incident_date' => $reportedDate, 'report_type' => 'Review', 'description' => $summary,
                'status' => $status === 'Closed' ? 'Closed' : 'Open',
                'closing_remarks' => $stages['R']['values']['closureNote'] ?? '',
                'performance_revision' => 1,
                'performance_data' => [
                    'type' => 'Client Complaint', 'severity' => $lookup('A', 'Overall Severity') ?? 'Medium',
                    'caseStatus' => $status, 'currentStage' => 'G', 'stages' => $stages,
                    'booking' => [
                        'key' => null, 'reference' => '', 'client' => $lookup('A', 'Client / Tour Operator'),
                        'guestParty' => $lookup('A', 'Guest / Party'),
                        'vehicle' => trim($vehicle->vehicle_no . ' ' . $vehicle->plate_no), 'driver' => $driver,
                        'period' => $lookup('A', 'Safari Period'),
                    ],
                ],
            ]);
        });
        $this->info('Prepared case preserved: ' . $report->case_reference);
        return self::SUCCESS;
    }
}
