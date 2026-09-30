<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentReport;
use App\Models\LeaseAllocation;
use App\Models\Setting;
use App\Models\Vehicle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = $this->filteredReportQuery($request)->get();

        return response()->json([
            'message' => 'Incident reports fetched successfully.',
            'incidentReports' => $reports->map(fn(IncidentReport $report): array => $this->transformIncidentReport($report))->values(),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $rows = $this->filteredReportQuery($request)
            ->get()
            ->map(fn(IncidentReport $report): array => $this->transformIncidentReport($report))
            ->values();

        $filename = 'performance-dashboard-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Date',
                'Vehicle',
                'Plate No',
                'Driver',
                'Booking Type',
                'Booking',
                'Client',
                'Report Type',
                'Description',
                'Action Taken',
                'Status',
                'Closing Remarks',
                'Photos Count',
            ]);

            foreach ($rows as $row) {
                $vehicle = is_array($row['vehicle'] ?? null) ? $row['vehicle'] : [];
                $driver = is_array($row['driver'] ?? null) ? $row['driver'] : [];
                $safari = is_array($row['safari'] ?? null) ? $row['safari'] : null;
                $lease = is_array($row['leaseAllocation'] ?? null) ? $row['leaseAllocation'] : null;
                $leaseContract = is_array($lease['contract'] ?? null) ? $lease['contract'] : [];

                $bookingType = $lease !== null ? 'Long Term Lease' : 'Short Term Booking';
                $booking = $lease !== null
                    ? trim(implode(' - ', array_filter([
                        'Lease #' . ($lease['id'] ?? ''),
                        $lease['groupName'] ?? null,
                        $leaseContract['leaseType'] ?? null,
                    ])))
                    : trim(implode(' - ', array_filter([
                        $safari['bookingRef'] ?? null,
                        $safari['groupName'] ?? null,
                    ])));
                $client = $lease !== null
                    ? (string) ($leaseContract['clientName'] ?? '')
                    : (string) ($safari['clientCompany'] ?? '');

                fputcsv($output, [
                    (string) ($row['date'] ?? ''),
                    (string) ($vehicle['vehicleNo'] ?? ''),
                    (string) ($vehicle['plateNo'] ?? ''),
                    (string) ($driver['name'] ?? ''),
                    $bookingType,
                    $booking,
                    $client,
                    (string) ($row['reportType'] ?? ''),
                    (string) ($row['description'] ?? ''),
                    (string) ($row['actionTaken'] ?? ''),
                    (string) ($row['status'] ?? ''),
                    (string) ($row['closingRemarks'] ?? ''),
                    (string) count(is_array($row['photos'] ?? null) ? $row['photos'] : []),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $rows = $this->filteredReportQuery($request)
            ->get()
            ->map(fn(IncidentReport $report): array => $this->transformIncidentReport($report, true))
            ->values();

        $company = [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
        ];

        $filters = [
            'dateFrom' => $request->input('dateFrom'),
            'dateTo' => $request->input('dateTo'),
            'status' => $request->input('status'),
            'reportType' => $request->input('reportType'),
            'search' => $request->input('search'),
        ];

        $pdf = Pdf::loadView('incident-reports.pdf', [
            'rows' => $rows,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri(),
            'filters' => $filters,
            'generatedAt' => now()->format('d M Y H:i'),
            'stats' => [
                'total' => $rows->count(),
                'open' => $rows->where('status', 'Open')->count(),
                'closed' => $rows->where('status', 'Closed')->count(),
                'accidents' => $rows->where('reportType', 'Accident')->count(),
            ],
        ])->setPaper('a4', 'landscape');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $pageText = 'Page {PAGE_NUM} of {PAGE_COUNT}';
        $fontSize = 8;
        $x = $canvas->get_width() - $fontMetrics->getTextWidth($pageText, $font, $fontSize) - 28;
        $y = $canvas->get_height() - 22;
        $canvas->page_text($x, $y, $pageText, $font, $fontSize, [0.42, 0.45, 0.50]);

        $filename = 'performance-dashboard-' . now()->format('Ymd-His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function show(IncidentReport $incidentReport): JsonResponse
    {
        $incidentReport->loadMissing([
            'vehicle.assignedDriver:id,name,email,phone',
            'lead:id,booking_ref,client_company,group_name,start_date,end_date',
            'leaseAllocation.leaseContract:id,client_name,group_name,lease_type,start_date,end_date',
            'leaseAllocation.vehicle:id,vehicle_no,plate_no,make,model',
            'leaseAllocation.driver:id,name,email,phone',
        ]);

        return response()->json([
            'message' => 'Incident report fetched successfully.',
            'incidentReport' => $this->transformIncidentReport($incidentReport),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateIncidentReport($request);
        $validated['photos'] = $this->storeUploadedPhotos($request);

        $report = IncidentReport::create(array_merge(
            $this->mapRequestToDb($validated),
            $this->resolveDriverSnapshotPayload($validated),
        ));
        $report->loadMissing([
            'vehicle.assignedDriver:id,name,email,phone',
            'lead:id,booking_ref,client_company,group_name,start_date,end_date',
            'leaseAllocation.leaseContract:id,client_name,group_name,lease_type,start_date,end_date',
            'leaseAllocation.vehicle:id,vehicle_no,plate_no,make,model',
            'leaseAllocation.driver:id,name,email,phone',
        ]);

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

        DB::transaction(function () use ($incidentReport, $validated): void {
            $report = IncidentReport::lockForUpdate()->findOrFail($incidentReport->id);
            $report->fill(array_merge(
                $this->mapRequestToDb($validated),
                $this->resolveDriverSnapshotPayload($validated, $report),
            ));
            $data = $report->performance_data ?? [];
            if ($report->isDirty(['vehicle_id', 'lead_id', 'lease_allocation_id'])) {
                unset($data['booking']);
                $report->safari_allocation_id = null;
            }
            if ($report->isDirty('report_type')) {
                $data['type'] = $report->report_type;
            }
            if ($report->isDirty('status')) {
                $data['caseStatus'] = $report->status;
                $data['stages']['R']['values']['status'] = $report->status;
            }
            if ($report->isDirty('action_taken')) {
                $data['stages']['D']['values']['actions'] = $report->action_taken ? [[
                    'action' => $report->action_taken, 'status' => 'Open', 'finding' => '',
                ]] : [];
            }
            $report->performance_data = $data;
            $report->performance_revision++;
            $report->save();
        });
        $incidentReport->refresh();
        $incidentReport->loadMissing([
            'vehicle.assignedDriver:id,name,email,phone',
            'lead:id,booking_ref,client_company,group_name,start_date,end_date',
            'leaseAllocation.leaseContract:id,client_name,group_name,lease_type,start_date,end_date',
            'leaseAllocation.vehicle:id,vehicle_no,plate_no,make,model',
            'leaseAllocation.driver:id,name,email,phone',
        ]);

        return response()->json([
            'message' => 'Incident report updated successfully.',
            'incidentReport' => $this->transformIncidentReport($incidentReport),
        ]);
    }

    public function destroy(IncidentReport $incidentReport): JsonResponse
    {
        foreach ($incidentReport->attachments as $attachment) {
            Storage::disk('local')->delete($attachment->path);
        }
        foreach ($incidentReport->photos ?: [] as $photo) {
            Storage::disk('public')->delete($photo);
        }

        $incidentReport->delete();

        return response()->json([
            'message' => 'Incident report deleted successfully.',
        ]);
    }

    private function filteredReportQuery(Request $request)
    {
        $query = IncidentReport::query()
            ->with([
                'vehicle.assignedDriver:id,name,email,phone',
                'lead:id,booking_ref,client_company,group_name,start_date,end_date',
                'leaseAllocation.leaseContract:id,client_name,group_name,lease_type,start_date,end_date',
                'leaseAllocation.vehicle:id,vehicle_no,plate_no,make,model',
                'leaseAllocation.driver:id,name,email,phone',
            ]);

        $dateFrom = trim((string) $request->input('dateFrom', ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $query->whereDate('incident_date', '>=', $dateFrom);
        }

        $dateTo = trim((string) $request->input('dateTo', ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $query->whereDate('incident_date', '<=', $dateTo);
        }

        $status = trim((string) $request->input('status', ''));
        if ($status !== '' && in_array($status, ['Open', 'Closed'], true)) {
            $query->where('status', $status);
        }

        $reportType = trim((string) $request->input('reportType', ''));
        if ($reportType !== '' && in_array($reportType, ['Accident', 'Review', 'Routine'], true)) {
            $query->where('report_type', $reportType);
        }

        if ($request->filled('vehicleId')) {
            $query->where('vehicle_id', (int) $request->input('vehicleId'));
        }

        if ($request->filled('driverId')) {
            $driverId = (int) $request->input('driverId');
            $query->where(function ($q) use ($driverId): void {
                $q->where('driver_user_id', $driverId)
                    ->orWhereHas('vehicle', fn($vehicleQuery) => $vehicleQuery->where('assigned_driver_id', $driverId))
                    ->orWhereHas('leaseAllocation', fn($leaseQuery) => $leaseQuery->where('driver_id', $driverId));
            });
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('description', 'like', '%' . $search . '%')
                    ->orWhere('action_taken', 'like', '%' . $search . '%')
                    ->orWhere('closing_remarks', 'like', '%' . $search . '%')
                    ->orWhere('driver_name', 'like', '%' . $search . '%')
                    ->orWhere('driver_email', 'like', '%' . $search . '%')
                    ->orWhere('driver_phone', 'like', '%' . $search . '%')
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search): void {
                        $vehicleQuery
                            ->where('vehicle_no', 'like', '%' . $search . '%')
                            ->orWhere('plate_no', 'like', '%' . $search . '%')
                            ->orWhereHas('assignedDriver', fn($driverQuery) => $driverQuery->where('name', 'like', '%' . $search . '%'));
                    })
                    ->orWhereHas('lead', function ($leadQuery) use ($search): void {
                        $leadQuery
                            ->where('booking_ref', 'like', '%' . $search . '%')
                            ->orWhere('client_company', 'like', '%' . $search . '%')
                            ->orWhere('group_name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('leaseAllocation', function ($leaseQuery) use ($search): void {
                        $leaseQuery
                            ->where('group_name', 'like', '%' . $search . '%')
                            ->orWhereHas('driver', fn($driverQuery) => $driverQuery->where('name', 'like', '%' . $search . '%'))
                            ->orWhereHas('leaseContract', function ($contractQuery) use ($search): void {
                                $contractQuery
                                    ->where('client_name', 'like', '%' . $search . '%')
                                    ->orWhere('group_name', 'like', '%' . $search . '%')
                                    ->orWhere('lease_type', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        return $query
            ->latest('incident_date')
            ->latest('id');
    }

    private function resolveLogoDataUri(): ?string
    {
        $logoPath = Setting::get('logo');

        if (! is_string($logoPath) || $logoPath === '' || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $contents = Storage::disk('public')->get($logoPath);
        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function resolveStoredFileDataUri(string $path): ?string
    {
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function resolveDriverSnapshotPayload(array $validated, ?IncidentReport $report = null): array
    {
        if (! $this->shouldRefreshDriverSnapshot($validated, $report)) {
            return [];
        }

        $driver = null;
        $leaseAllocationId = array_key_exists('leaseAllocationId', $validated)
            ? $validated['leaseAllocationId']
            : $report?->lease_allocation_id;

        if ($leaseAllocationId) {
            $driver = LeaseAllocation::query()
                ->with('driver:id,name,email,phone')
                ->find((int) $leaseAllocationId)
                ?->driver;
        }

        $vehicleId = array_key_exists('vehicleId', $validated)
            ? $validated['vehicleId']
            : $report?->vehicle_id;

        if ($driver === null && $vehicleId) {
            $driver = Vehicle::query()
                ->with('assignedDriver:id,name,email,phone')
                ->find((int) $vehicleId)
                ?->assignedDriver;
        }

        return [
            'driver_user_id' => $driver?->id,
            'driver_name' => $driver?->name,
            'driver_email' => $driver?->email,
            'driver_phone' => $driver?->phone,
        ];
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function shouldRefreshDriverSnapshot(array $validated, ?IncidentReport $report = null): bool
    {
        if ($report === null || trim((string) $report->driver_name) === '') {
            return true;
        }

        if (array_key_exists('vehicleId', $validated) && (int) $validated['vehicleId'] !== (int) $report->vehicle_id) {
            return true;
        }

        if (array_key_exists('leaseAllocationId', $validated)) {
            $nextLeaseId = $validated['leaseAllocationId'] ? (int) $validated['leaseAllocationId'] : null;
            return $nextLeaseId !== ($report->lease_allocation_id ? (int) $report->lease_allocation_id : null);
        }

        if (array_key_exists('safariId', $validated) && $report->lease_allocation_id !== null) {
            return true;
        }

        return false;
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
            'leaseAllocationId' => ['nullable', 'integer', 'exists:lease_allocations,id'],
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

        if (! empty($validated['safariId']) && ! empty($validated['leaseAllocationId'])) {
            $request->validate([
                'leaseAllocationId' => ['prohibited'],
            ], [
                'leaseAllocationId.prohibited' => 'Select either a short-term booking or a long-term lease allocation, not both.',
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
            'leaseAllocationId' => 'lease_allocation_id',
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

        if (array_key_exists('safariId', $validated)) {
            $payload['lease_allocation_id'] = null;
        }

        if (array_key_exists('leaseAllocationId', $validated)) {
            $payload['lead_id'] = null;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformDriverSnapshot(IncidentReport $report): ?array
    {
        if (trim((string) $report->driver_name) !== '') {
            return [
                'id' => $report->driver_user_id,
                'name' => $report->driver_name,
                'email' => $report->driver_email,
                'phone' => $report->driver_phone,
            ];
        }

        $driver = $report->leaseAllocation?->driver ?: $report->vehicle?->assignedDriver;

        return $driver ? [
            'id' => $driver->id,
            'name' => $driver->name,
            'email' => $driver->email,
            'phone' => $driver->phone,
        ] : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformIncidentReport(IncidentReport $report, bool $includePhotoDataUris = false): array
    {
        $photos = $report->photos ?: [];
        $driver = $this->transformDriverSnapshot($report);

        return [
            'id' => $report->id,
            'date' => optional($report->incident_date)->format('Y-m-d'),
            'vehicleId' => $report->vehicle_id,
            'driverId' => $driver['id'] ?? null,
            'driverName' => $driver['name'] ?? null,
            'driverEmail' => $driver['email'] ?? null,
            'driverPhone' => $driver['phone'] ?? null,
            'safariId' => $report->lead_id,
            'leaseAllocationId' => $report->lease_allocation_id,
            'reportType' => $report->report_type,
            'description' => $report->description,
            'actionTaken' => $report->action_taken,
            'photos' => array_map(function (string $path) use ($includePhotoDataUris): array {
                $photo = [
                    'path' => $path,
                    'url' => Storage::url($path),
                ];

                if ($includePhotoDataUris) {
                    $photo['dataUri'] = $this->resolveStoredFileDataUri($path);
                }

                return $photo;
            }, $photos),
            'status' => $report->status,
            'closingRemarks' => $report->closing_remarks,
            'vehicle' => $report->vehicle ? [
                'id' => $report->vehicle->id,
                'vehicleNo' => $report->vehicle->vehicle_no,
                'plateNo' => $report->vehicle->plate_no,
                'make' => $report->vehicle->make,
                'model' => $report->vehicle->model,
                'assignedDriverId' => $report->vehicle->assigned_driver_id,
                'assignedDriver' => $driver ?: ($report->vehicle->assignedDriver ? [
                    'id' => $report->vehicle->assignedDriver->id,
                    'name' => $report->vehicle->assignedDriver->name,
                    'email' => $report->vehicle->assignedDriver->email,
                    'phone' => $report->vehicle->assignedDriver->phone,
                ] : null),
            ] : null,
            'driver' => $driver,
            'safari' => $report->lead ? [
                'id' => $report->lead->id,
                'bookingRef' => $report->lead->booking_ref,
                'clientCompany' => $report->lead->client_company,
                'groupName' => $report->lead->group_name,
                'startDate' => optional($report->lead->start_date)->format('Y-m-d'),
                'endDate' => optional($report->lead->end_date)->format('Y-m-d'),
            ] : null,
            'leaseAllocation' => $report->leaseAllocation ? [
                'id' => $report->leaseAllocation->id,
                'assignmentType' => 'long_term_lease',
                'groupName' => $report->leaseAllocation->group_name,
                'startDate' => optional($report->leaseAllocation->start_date)->format('Y-m-d'),
                'endDate' => optional($report->leaseAllocation->end_date)->format('Y-m-d'),
                'status' => $report->leaseAllocation->status,
                'contract' => $report->leaseAllocation->leaseContract ? [
                    'id' => $report->leaseAllocation->leaseContract->id,
                    'clientName' => $report->leaseAllocation->leaseContract->client_name,
                    'groupName' => $report->leaseAllocation->leaseContract->group_name,
                    'leaseType' => $report->leaseAllocation->leaseContract->lease_type,
                    'startDate' => optional($report->leaseAllocation->leaseContract->start_date)->format('Y-m-d'),
                    'endDate' => optional($report->leaseAllocation->leaseContract->end_date)->format('Y-m-d'),
                ] : null,
                'vehicle' => $report->leaseAllocation->vehicle ? [
                    'id' => $report->leaseAllocation->vehicle->id,
                    'vehicleNo' => $report->leaseAllocation->vehicle->vehicle_no,
                    'plateNo' => $report->leaseAllocation->vehicle->plate_no,
                    'make' => $report->leaseAllocation->vehicle->make,
                    'model' => $report->leaseAllocation->vehicle->model,
                ] : null,
                'driver' => $driver ?: ($report->leaseAllocation->driver ? [
                    'id' => $report->leaseAllocation->driver->id,
                    'name' => $report->leaseAllocation->driver->name,
                    'email' => $report->leaseAllocation->driver->email,
                    'phone' => $report->leaseAllocation->driver->phone,
                ] : null),
            ] : null,
            'createdAt' => $report->created_at?->toISOString(),
            'updatedAt' => $report->updated_at?->toISOString(),
        ];
    }
}
