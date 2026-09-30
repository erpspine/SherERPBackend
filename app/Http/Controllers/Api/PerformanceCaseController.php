<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{IncidentAttachment, IncidentReport, LeaseAllocation, SafariAllocation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PerformanceCaseController extends Controller
{
    private const TYPES = ['Client Complaint', 'Accident', 'Breakdown', 'Compliance', 'Positive Feedback', 'Review', 'Routine'];
    private const RELATIONS = ['vehicle.assignedDriver', 'lead', 'leaseAllocation.leaseContract', 'leaseAllocation.driver', 'attachments'];

    private function schema(): array
    {
        return json_decode(file_get_contents(resource_path('performance/stages.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function index()
    {
        return response()->json([
            'schema' => $this->schema(),
            'cases' => IncidentReport::with(self::RELATIONS)->latest('id')->get()->map(fn ($report) => $this->present($report)),
        ]);
    }

    public function bookings()
    {
        $safaris = SafariAllocation::with(['lead', 'vehicle', 'driver'])->whereNotNull('vehicle_id')->latest('id')->get();
        $leases = LeaseAllocation::with(['leaseContract', 'vehicle', 'driver'])->whereNotNull('vehicle_id')->latest('id')->get();
        return response()->json(['bookings' => $safaris->map(fn ($row) => $this->booking($row, 'safari'))
            ->concat($leases->map(fn ($row) => $this->booking($row, 'lease')))->values()]);
    }

    private function booking($row, string $type): array
    {
        return [
            'key' => $type . ':' . $row->id,
            'reference' => $type === 'safari' ? $row->lead?->booking_ref : 'LEASE-' . $row->lease_contract_id,
            'client' => $type === 'safari' ? $row->lead?->client_company : $row->leaseContract?->client_name,
            'guestParty' => $type === 'safari' ? $row->lead?->group_name : $row->group_name,
            'startDate' => $row->start_date?->format('Y-m-d') ?? ($type === 'safari' ? $row->lead?->start_date?->format('Y-m-d') : null),
            'endDate' => $row->end_date?->format('Y-m-d') ?? ($type === 'safari' ? $row->lead?->end_date?->format('Y-m-d') : null),
            'vehicle' => trim(($row->vehicle?->vehicle_no ?? '') . ' ' . ($row->vehicle?->plate_no ?? '')),
            'driver' => $row->driver?->name ?? 'Unassigned',
        ];
    }

    private function allocation(string $key)
    {
        [$type, $id] = explode(':', $key);
        return $type === 'safari'
            ? SafariAllocation::with(['lead', 'vehicle', 'driver'])->findOrFail($id)
            : LeaseAllocation::with(['leaseContract', 'vehicle', 'driver'])->findOrFail($id);
    }

    private function bindBooking(IncidentReport $report, string $key): void
    {
        $row = $this->allocation($key);
        if (! $row->vehicle) {
            throw ValidationException::withMessages(['booking' => 'This booking has no assigned vehicle.']);
        }
        $type = str_starts_with($key, 'safari:') ? 'safari' : 'lease';
        $report->vehicle_id = $row->vehicle_id;
        $report->lead_id = $type === 'safari' ? $row->lead_id : null;
        $report->safari_allocation_id = $type === 'safari' ? $row->id : null;
        $report->lease_allocation_id = $type === 'lease' ? $row->id : null;
        $report->driver_user_id = $row->driver_id;
        $report->driver_name = $row->driver?->name;
        $report->driver_email = $row->driver?->email;
        $report->driver_phone = $row->driver?->phone;
        $data = $report->performance_data ?? [];
        $data['booking'] = $this->booking($row, $type);
        $report->performance_data = $data;
    }

    public function store(Request $request)
    {
        $input = $request->validate([
            'booking' => ['required', 'regex:/^(safari|lease):[1-9][0-9]*$/'],
            'type' => ['required', Rule::in(self::TYPES)],
            'date' => ['required', 'date_format:Y-m-d'],
            'severity' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'description' => ['required', 'string', 'max:20000'],
            'reportedBy' => ['nullable', 'string', 'max:255'],
        ]);
        $report = DB::transaction(function () use ($input, $request) {
            $report = new IncidentReport([
                'incident_date' => $input['date'],
                'report_type' => in_array($input['type'], ['Accident', 'Routine']) ? $input['type'] : 'Review',
                'description' => $input['description'],
                'status' => 'Open',
                'performance_revision' => 1,
                'performance_data' => [
                    'type' => $input['type'], 'severity' => $input['severity'], 'currentStage' => 'A',
                    'stages' => [
                        'A' => ['state' => 'In progress', 'values' => [
                            'incidentDate' => $input['date'], 'dateReported' => now()->toDateString(),
                            'reportedBy' => $input['reportedBy'] ?? $request->user()->name,
                            'severity' => $input['severity'],
                        ]],
                        'C' => ['state' => 'In progress', 'values' => ['summary' => $input['description']]],
                    ],
                ],
            ]);
            $this->bindBooking($report, $input['booking']);
            $report->save();
            // The database ID makes references unique even for simultaneous submissions.
            $report->case_reference = 'SHER-INC-' . now()->format('Y') . '-' . str_pad((string) $report->id, 6, '0', STR_PAD_LEFT);
            $report->save();
            return $report;
        });
        return response()->json(['case' => $this->present($report->load(self::RELATIONS))], 201);
    }

    public function show(IncidentReport $incidentReport)
    {
        return response()->json(['case' => $this->present($incidentReport->load(self::RELATIONS))]);
    }

    private function rules(array $fields, string $prefix = 'values.'): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $key = $prefix . $field['key'];
            $rules[$key] = match ($field['type']) {
                'readonly' => ['nullable', 'string', 'max:1000'],
                'date' => ['nullable', 'date_format:Y-m-d'],
                'time' => ['nullable', 'date_format:H:i'],
                'checkbox' => ['boolean'],
                'select', 'status' => ['nullable', Rule::in($field['options'])],
                'checklist', 'list', 'rows' => ['nullable', 'array', 'max:100'],
                default => ['nullable', 'string', 'max:20000'],
            };
            if (in_array($field['type'], ['checklist', 'list'])) {
                $rules[$key . '.*'] = ['nullable', 'string', 'max:20000'];
            }
            if ($field['type'] === 'rows') {
                $rules[$key . '.*'] = ['array:' . implode(',', array_column($field['columns'], 'key'))];
                $rules = array_merge($rules, $this->rules($field['columns'], $key . '.*.'));
            }
        }
        return $rules;
    }

    public function saveStage(Request $request, IncidentReport $incidentReport, string $letter)
    {
        $schema = $this->schema();
        abort_unless(isset($schema[$letter]), 404);
        $rules = array_merge([
            'revision' => ['required', 'integer', 'min:0'],
            'state' => ['required', Rule::in(['In progress', 'Completed', 'Not applicable'])],
            'values' => ['required', 'array:' . implode(',', array_column($schema[$letter]['fields'], 'key'))],
            'booking' => ['sometimes', 'required', 'regex:/^(safari|lease):[1-9][0-9]*$/'],
        ], $this->rules($schema[$letter]['fields']));
        // Omitted draft fields are allowed; explicitly clearing core case data is not.
        foreach (match ($letter) {
            'A' => ['incidentDate', 'severity', 'type'],
            'C' => ['summary'],
            'R' => ['status'],
            default => [],
        } as $key) {
            $rules['values.' . $key] = array_merge(['sometimes', 'required'], array_diff($rules['values.' . $key], ['nullable']));
        }
        $input = $request->validate($rules);
        $report = DB::transaction(function () use ($incidentReport, $input, $letter, $request) {
            $report = IncidentReport::lockForUpdate()->findOrFail($incidentReport->id);
            abort_if($report->performance_revision !== $input['revision'], 409, 'This case has changed. Reload the case before saving again.');
            if ($letter === 'A' && isset($input['booking'])) {
                $this->bindBooking($report, $input['booking']);
            }
            $data = $report->performance_data ?? [];
            $values = array_merge($data['stages'][$letter]['values'] ?? [], $input['values']);
            foreach ($this->schema()[$letter]['fields'] as $field) {
                if (in_array($field['type'], ['list', 'checklist']) && isset($values[$field['key']])) {
                    $values[$field['key']] = array_values(array_filter($values[$field['key']], fn ($value) => is_string($value) && trim($value) !== ''));
                }
            }
            if ($letter === 'A') {
                foreach (['caseReference', 'bookingReference', 'client', 'guestParty', 'safariPeriod', 'vehicle', 'driver'] as $key) {
                    unset($values[$key]);
                }
                if (! empty($values['incidentDate'])) {
                    $report->incident_date = $values['incidentDate'];
                }
                $data['severity'] = $values['severity'] ?? $data['severity'] ?? 'Medium';
                if (isset($values['type'])) {
                    $data['type'] = $values['type'];
                    $report->report_type = in_array($values['type'], ['Accident', 'Routine']) ? $values['type'] : 'Review';
                }
            }
            if ($letter === 'C' && ! empty($values['summary'])) {
                $report->description = $values['summary'];
            }
            if ($letter === 'D' && array_key_exists('actions', $values)) {
                $report->action_taken = collect($values['actions'] ?? [])->map(fn ($row) => trim($row['action'] ?? ''))->filter()->implode("\n");
            }
            if ($letter === 'R') {
                $values = array_merge($data['stages']['R']['values'] ?? [], $values);
                $status = $values['status'] ?? ($report->status === 'Closed' ? 'Closed' : ($data['caseStatus'] ?? 'Open'));
                if ($status === 'Closed' && (empty($values['closureDate']) || empty(trim($values['closureNote'] ?? '')))) {
                    throw ValidationException::withMessages(['values.closureDate' => 'A final closure date and closure note are required to close the case.']);
                }
                $report->status = $status === 'Closed' ? 'Closed' : 'Open';
                $report->closing_remarks = $values['closureNote'] ?? null;
                $data['caseStatus'] = $status;
            }
            $data['stages'][$letter] = [
                ...($data['stages'][$letter] ?? []),
                'values' => $values, 'state' => $input['state'],
                'updatedAt' => now()->toISOString(), 'updatedBy' => $request->user()->name,
            ];
            $data['currentStage'] = $letter;
            $report->performance_data = $data;
            $report->performance_revision++;
            $report->save();
            return $report;
        });
        return response()->json(['case' => $this->present($report->load(self::RELATIONS))]);
    }

    public function feedback(Request $request, IncidentReport $incidentReport)
    {
        $input = $request->validate([
            'revision' => ['required', 'integer', 'min:0'],
            'type' => ['required', Rule::in(['Positive', 'Concern', 'General'])],
            'rating' => ['required', 'integer', 'between:1,5'],
            'remarks' => ['required', 'string', 'max:10000'],
        ]);
        $report = DB::transaction(function () use ($incidentReport, $input, $request) {
            $report = IncidentReport::lockForUpdate()->findOrFail($incidentReport->id);
            abort_if($report->performance_revision !== $input['revision'], 409, 'This case has changed. Reload the case before saving again.');
            unset($input['revision']);
            $data = $report->performance_data ?? [];
            $data['feedback'][] = $input + ['by' => $request->user()->name, 'date' => now()->toISOString()];
            $report->performance_data = $data;
            $report->performance_revision++;
            $report->save();
            return $report;
        });
        return response()->json(['case' => $this->present($report->load(self::RELATIONS))]);
    }

    public function upload(Request $request, IncidentReport $incidentReport)
    {
        $input = $request->validate([
            'stage' => ['nullable', Rule::in(range('A', 'R'))],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,txt,csv,doc,docx,xls,xlsx', 'max:10240'],
        ]);
        $paths = [];
        try {
            DB::transaction(function () use ($request, $incidentReport, $input, &$paths) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('incident-attachments/' . $incidentReport->id, 'local');
                    if ($path === false) {
                        throw new \RuntimeException('Unable to store the attachment.');
                    }
                    $paths[] = $path;
                    IncidentAttachment::create([
                        'incident_report_id' => $incidentReport->id, 'stage' => $input['stage'] ?? null,
                        'name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 240),
                        'path' => $path, 'mime' => $file->getMimeType(), 'size' => $file->getSize(),
                        'uploaded_by' => $request->user()->id,
                    ]);
                }
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($paths);
            throw $error;
        }
        return response()->json(['case' => $this->present($incidentReport->fresh(self::RELATIONS))], 201);
    }

    public function download(IncidentReport $incidentReport, IncidentAttachment $attachment)
    {
        abort_unless($attachment->incident_report_id === $incidentReport->id, 404);
        return Storage::disk('local')->download($attachment->path, $attachment->name, [
            'Content-Type' => $attachment->mime, 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function removeAttachment(IncidentReport $incidentReport, IncidentAttachment $attachment)
    {
        abort_unless($attachment->incident_report_id === $incidentReport->id, 404);
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();
        return response()->json(['case' => $this->present($incidentReport->load(self::RELATIONS))]);
    }

    private function present(IncidentReport $report): array
    {
        $data = $report->performance_data ?? [];
        $reference = $report->case_reference ?? 'SHER-INC-' . $report->created_at->format('Y') . '-' . str_pad((string) $report->id, 6, '0', STR_PAD_LEFT);
        $booking = $data['booking'] ?? [
            'key' => $report->lease_allocation_id ? 'lease:' . $report->lease_allocation_id : null,
            'reference' => $report->lead?->booking_ref ?? ($report->lease_allocation_id ? 'LEASE-' . $report->leaseAllocation?->lease_contract_id : ''),
            'client' => $report->lead?->client_company ?? $report->leaseAllocation?->leaseContract?->client_name,
            'guestParty' => $report->lead?->group_name ?? $report->leaseAllocation?->group_name,
            'vehicle' => trim(($report->vehicle?->vehicle_no ?? '') . ' ' . ($report->vehicle?->plate_no ?? '')),
            'driver' => $report->driver_name ?: ($report->leaseAllocation?->driver?->name ?? $report->vehicle?->assignedDriver?->name ?? 'Unassigned'),
            'startDate' => $report->lead?->start_date?->format('Y-m-d') ?? $report->leaseAllocation?->start_date?->format('Y-m-d'),
            'endDate' => $report->lead?->end_date?->format('Y-m-d') ?? $report->leaseAllocation?->end_date?->format('Y-m-d'),
        ];
        $stages = $data['stages'] ?? [];
        $stages['A']['values'] = array_merge([
            'incidentDate' => $report->incident_date?->format('Y-m-d'),
            'dateReported' => $report->created_at->format('Y-m-d'),
            'severity' => $data['severity'] ?? 'Medium',
        ], $stages['A']['values'] ?? [], [
            'incidentDate' => $report->incident_date?->format('Y-m-d'),
            'type' => $data['type'] ?? $report->report_type,
            'severity' => $data['severity'] ?? 'Medium',
            'caseReference' => $reference, 'bookingReference' => $booking['reference'] ?? '',
            'client' => $booking['client'] ?? '', 'guestParty' => $booking['guestParty'] ?? '',
            'vehicle' => $booking['vehicle'] ?? '', 'driver' => $booking['driver'] ?? '',
            'safariPeriod' => $booking['period'] ?? implode(' to ', array_filter([$booking['startDate'] ?? null, $booking['endDate'] ?? null])),
        ]);
        $stages['C']['values']['summary'] = $report->description;
        if (! isset($stages['D']) && $report->action_taken) {
            $stages['D'] = ['state' => 'In progress', 'values' => ['actions' => [[
                'action' => $report->action_taken, 'status' => $report->status === 'Closed' ? 'Closed' : 'Open', 'finding' => '',
            ]]]];
        }
        $stages['R']['values']['status'] = $report->status === 'Closed' ? 'Closed' : ($data['caseStatus'] ?? 'Open');
        $stages['R']['values']['closureNote'] = $report->closing_remarks ?? '';
        return [
            'id' => $report->id, 'reference' => $reference, 'revision' => $report->performance_revision,
            'type' => $data['type'] ?? $report->report_type, 'severity' => $data['severity'] ?? 'Medium',
            'status' => $stages['R']['values']['status'], 'date' => $report->incident_date?->format('Y-m-d'),
            'description' => $report->description, 'booking' => $booking,
            'currentStage' => $data['currentStage'] ?? 'A', 'stages' => $stages,
            'feedback' => $data['feedback'] ?? [],
            'attachments' => $report->attachments,
            'photos' => array_map(fn ($path) => ['name' => basename($path), 'url' => Storage::disk('public')->url($path)], $report->photos ?? []),
        ];
    }
}
