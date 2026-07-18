<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class IncidentReportController extends Controller
{
    public function index(): JsonResponse
    {
        $reports = IncidentReport::query()
            ->with(['vehicle:id,vehicle_no,plate_no,make,model', 'lead:id,booking_ref,client_company,group_name,start_date,end_date'])
            ->latest('incident_date')
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Incident reports fetched successfully.',
            'incidentReports' => $reports->map(fn(IncidentReport $report): array => $this->transformIncidentReport($report))->values(),
        ]);
    }

    public function show(IncidentReport $incidentReport): JsonResponse
    {
        $incidentReport->loadMissing(['vehicle:id,vehicle_no,plate_no,make,model', 'lead:id,booking_ref,client_company,group_name,start_date,end_date']);

        return response()->json([
            'message' => 'Incident report fetched successfully.',
            'incidentReport' => $this->transformIncidentReport($incidentReport),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateIncidentReport($request);
        $validated['photos'] = $this->storeUploadedPhotos($request);

        $report = IncidentReport::create($this->mapRequestToDb($validated));
        $report->loadMissing(['vehicle:id,vehicle_no,plate_no,make,model', 'lead:id,booking_ref,client_company,group_name,start_date,end_date']);

        return response()->json([
            'message' => 'Incident report created successfully.',
            'incidentReport' => $this->transformIncidentReport($report),
        ], 201);
    }

    public function update(Request $request, IncidentReport $incidentReport): JsonResponse
    {
        $validated = $this->validateIncidentReport($request, true, $incidentReport);
        $existingPhotos = $incidentReport->photos ?: [];
        $removePhotos = array_values(array_filter((array) $request->input('removePhotos', [])));

        if ($removePhotos !== []) {
            $existingPhotos = array_values(array_filter(
                $existingPhotos,
                fn(string $photo): bool => ! in_array($photo, $removePhotos, true),
            ));
            foreach ($removePhotos as $photo) {
                Storage::disk('public')->delete($photo);
            }
        }

        $uploadedPhotos = $this->storeUploadedPhotos($request);
        if ($uploadedPhotos !== [] || $removePhotos !== []) {
            $validated['photos'] = array_values(array_merge($existingPhotos, $uploadedPhotos));
        }

        $incidentReport->update($this->mapRequestToDb($validated));
        $incidentReport->refresh();
        $incidentReport->loadMissing(['vehicle:id,vehicle_no,plate_no,make,model', 'lead:id,booking_ref,client_company,group_name,start_date,end_date']);

        return response()->json([
            'message' => 'Incident report updated successfully.',
            'incidentReport' => $this->transformIncidentReport($incidentReport),
        ]);
    }

    public function destroy(IncidentReport $incidentReport): JsonResponse
    {
        foreach ($incidentReport->photos ?: [] as $photo) {
            Storage::disk('public')->delete($photo);
        }

        $incidentReport->delete();

        return response()->json([
            'message' => 'Incident report deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateIncidentReport(Request $request, bool $partial = false, ?IncidentReport $report = null): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $validated = $request->validate([
            'date' => [$required, 'date'],
            'vehicleId' => [$required, 'integer', 'exists:vehicles,id'],
            'safariId' => ['nullable', 'integer', 'exists:leads,id'],
            'reportType' => [$required, Rule::in(['Accident', 'Review', 'Routine'])],
            'description' => [$required, 'string'],
            'actionTaken' => ['nullable', 'string'],
            'status' => [$required, Rule::in(['Open', 'Closed'])],
            'closingRemarks' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:5120'],
            'removePhotos' => ['nullable', 'array'],
            'removePhotos.*' => ['string'],
        ]);

        $status = $validated['status'] ?? $report?->status;
        $closingRemarks = $validated['closingRemarks'] ?? $report?->closing_remarks;
        if ($status === 'Closed' && trim((string) $closingRemarks) === '') {
            $request->validate([
                'closingRemarks' => ['required', 'string'],
            ]);
        }

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    private function storeUploadedPhotos(Request $request): array
    {
        if (! $request->hasFile('photos')) {
            return [];
        }

        $paths = [];
        foreach ((array) $request->file('photos') as $photo) {
            if ($photo && $photo->isValid()) {
                $paths[] = $photo->store('incident-reports', 'public');
            }
        }

        return $paths;
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function mapRequestToDb(array $validated): array
    {
        $map = [
            'date' => 'incident_date',
            'vehicleId' => 'vehicle_id',
            'safariId' => 'lead_id',
            'reportType' => 'report_type',
            'description' => 'description',
            'actionTaken' => 'action_taken',
            'photos' => 'photos',
            'status' => 'status',
            'closingRemarks' => 'closing_remarks',
        ];

        $payload = [];
        foreach ($validated as $key => $value) {
            if (isset($map[$key])) {
                $payload[$map[$key]] = $value;
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformIncidentReport(IncidentReport $report): array
    {
        $photos = $report->photos ?: [];

        return [
            'id' => $report->id,
            'date' => optional($report->incident_date)->format('Y-m-d'),
            'vehicleId' => $report->vehicle_id,
            'safariId' => $report->lead_id,
            'reportType' => $report->report_type,
            'description' => $report->description,
            'actionTaken' => $report->action_taken,
            'photos' => array_map(fn(string $path): array => [
                'path' => $path,
                'url' => Storage::url($path),
            ], $photos),
            'status' => $report->status,
            'closingRemarks' => $report->closing_remarks,
            'vehicle' => $report->vehicle ? [
                'id' => $report->vehicle->id,
                'vehicleNo' => $report->vehicle->vehicle_no,
                'plateNo' => $report->vehicle->plate_no,
                'make' => $report->vehicle->make,
                'model' => $report->vehicle->model,
            ] : null,
            'safari' => $report->lead ? [
                'id' => $report->lead->id,
                'bookingRef' => $report->lead->booking_ref,
                'clientCompany' => $report->lead->client_company,
                'groupName' => $report->lead->group_name,
                'startDate' => optional($report->lead->start_date)->format('Y-m-d'),
                'endDate' => optional($report->lead->end_date)->format('Y-m-d'),
            ] : null,
            'createdAt' => $report->created_at?->toISOString(),
            'updatedAt' => $report->updated_at?->toISOString(),
        ];
    }
}
