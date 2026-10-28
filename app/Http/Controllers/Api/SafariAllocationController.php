<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\Lead;
use App\Models\ProformaInvoice;
use App\Models\SafariAllocation;
use App\Models\Setting;
use App\Models\Vehicle;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SafariAllocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SafariAllocation::class);

        $compact = $request->boolean('compact');
        $query = SafariAllocation::query()
            ->when(
                $compact,
                fn($query) => $query->with(['lead', 'vehicle']),
                fn($query) => $query
                    ->with(['lead.quotations', 'proformaInvoice', 'vehicle', 'driver'])
                    ->withCount('odometerLogs')
                    ->withMax('odometerLogs', 'recorded_at'),
            )
            ->latest('id');

        if ($request->user()?->hasRole('Driver')) {
            $query->where('driver_id', $request->user()->id);
        }

        $allocations = $query->get();

        return response()->json([
            'message' => 'Safari allocations fetched successfully.',
            'allocations' => $allocations->map(
                fn(SafariAllocation $allocation): array => $compact
                    ? $this->transformCompact($allocation)
                    : $this->transform($allocation)
            )->values(),
        ]);
    }

    public function show(SafariAllocation $safariAllocation): JsonResponse
    {
        $this->authorize('view', $safariAllocation);

        $safariAllocation->load(['lead.quotations', 'proformaInvoice', 'vehicle', 'driver']);
        $safariAllocation->loadCount('odometerLogs');
        $safariAllocation->loadMax('odometerLogs', 'recorded_at');

        return response()->json([
            'message' => 'Safari allocation fetched successfully.',
            'allocation' => $this->transform($safariAllocation),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', SafariAllocation::class);

        $validated = $request->validate([
            'leadId' => ['required', 'integer', 'exists:leads,id'],
            'proformaInvoiceId' => ['nullable', 'integer', 'exists:proforma_invoices,id'],
            'vehicleId' => ['required', 'integer', 'exists:vehicles,id'],
            'driverId' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn($query) => $query->where('status', 'Active'))],
            'vehicleLeaseType' => ['sometimes', Rule::in(['Short-Term Lease', 'Long-Term Lease', 'Subcontract'])],
            'startDate' => ['sometimes', 'date_format:Y-m-d'],
            'endDate' => ['sometimes', 'date_format:Y-m-d'],
            'status' => ['sometimes', Rule::in(['Assigned', 'Pending', 'In Progress', 'Completed', 'Cancelled'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $lead = Lead::query()->findOrFail($validated['leadId']);
        [$startDate, $endDate] = $this->resolveAllocationDates(
            $validated,
            optional($lead->start_date)?->format('Y-m-d'),
            optional($lead->end_date)?->format('Y-m-d'),
            optional($lead->start_date)?->format('Y-m-d'),
            optional($lead->end_date)?->format('Y-m-d')
        );

        $resolvedLeaseType = $validated['vehicleLeaseType'] ?? 'Short-Term Lease';
        $this->validateVehicleLeaseTypeMatch((int) $validated['vehicleId'], $resolvedLeaseType);

        $allocation = SafariAllocation::create([
            'lead_id' => $validated['leadId'],
            'proforma_invoice_id' => $validated['proformaInvoiceId'] ?? null,
            'vehicle_id' => $validated['vehicleId'],
            'driver_id' => $validated['driverId'] ?? null,
            'vehicle_lease_type' => $resolvedLeaseType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'] ?? 'Assigned',
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($startDate && $endDate) {
            JobCard::ensureForLead(
                (int) $validated['leadId'],
                $startDate,
                $endDate,
            );
        }

        $this->syncProformaStatusesForAllocation($allocation);

        $allocation->load(['lead', 'proformaInvoice', 'vehicle', 'driver']);

        return response()->json([
            'message' => 'Safari allocation created successfully.',
            'allocation' => $this->transform($allocation),
        ], 201);
    }

    public function update(Request $request, SafariAllocation $safariAllocation): JsonResponse
    {
        $this->authorize('update', $safariAllocation);

        $validated = $request->validate([
            'leadId' => ['sometimes', 'integer', 'exists:leads,id'],
            'proformaInvoiceId' => ['sometimes', 'nullable', 'integer', 'exists:proforma_invoices,id'],
            'vehicleId' => ['sometimes', 'integer', 'exists:vehicles,id'],
            'driverId' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where(fn($query) => $query->where('status', 'Active'))],
            'vehicleLeaseType' => ['sometimes', Rule::in(['Short-Term Lease', 'Long-Term Lease', 'Subcontract'])],
            'startDate' => ['sometimes', 'date_format:Y-m-d'],
            'endDate' => ['sometimes', 'date_format:Y-m-d'],
            'status' => ['sometimes', Rule::in(['Assigned', 'Pending', 'In Progress', 'Completed', 'Cancelled'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $nextLeadId = (int) ($validated['leadId'] ?? $safariAllocation->lead_id);
        $lead = Lead::query()->findOrFail($nextLeadId);

        [$startDate, $endDate] = $this->resolveAllocationDates(
            $validated,
            optional($safariAllocation->start_date)?->format('Y-m-d') ?: optional($lead->start_date)?->format('Y-m-d'),
            optional($safariAllocation->end_date)?->format('Y-m-d') ?: optional($lead->end_date)?->format('Y-m-d'),
            optional($lead->start_date)?->format('Y-m-d'),
            optional($lead->end_date)?->format('Y-m-d')
        );

        $nextVehicleId = (int) ($validated['vehicleId'] ?? $safariAllocation->vehicle_id);
        $nextLeaseType = $validated['vehicleLeaseType'] ?? $safariAllocation->vehicle_lease_type ?? 'Short-Term Lease';
        $this->validateVehicleLeaseTypeMatch($nextVehicleId, $nextLeaseType);
        $previousLeadId = (int) $safariAllocation->lead_id;
        $previousProformaInvoiceId = $safariAllocation->proforma_invoice_id !== null
            ? (int) $safariAllocation->proforma_invoice_id
            : null;

        $safariAllocation->update([
            'lead_id' => $nextLeadId,
            'proforma_invoice_id' => array_key_exists('proformaInvoiceId', $validated) ? $validated['proformaInvoiceId'] : $safariAllocation->proforma_invoice_id,
            'vehicle_id' => $nextVehicleId,
            'driver_id' => array_key_exists('driverId', $validated) ? $validated['driverId'] : $safariAllocation->driver_id,
            'vehicle_lease_type' => $nextLeaseType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'] ?? $safariAllocation->status,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $safariAllocation->notes,
        ]);

        $this->syncProformaStatusesForAllocation($safariAllocation, $previousLeadId, $previousProformaInvoiceId);

        $safariAllocation->load(['lead', 'proformaInvoice', 'vehicle', 'driver']);

        return response()->json([
            'message' => 'Safari allocation updated successfully.',
            'allocation' => $this->transform($safariAllocation),
        ]);
    }

    public function destroy(SafariAllocation $safariAllocation): JsonResponse
    {
        $this->authorize('delete', $safariAllocation);

        $leadId = (int) $safariAllocation->lead_id;
        $proformaInvoiceId = $safariAllocation->proforma_invoice_id !== null
            ? (int) $safariAllocation->proforma_invoice_id
            : null;

        $safariAllocation->delete();

        $this->syncProformaStatusesByRefs([$leadId], $proformaInvoiceId !== null ? [$proformaInvoiceId] : []);

        return response()->json([
            'message' => 'Safari allocation deleted successfully.',
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', SafariAllocation::class);

        $dateFrom        = trim((string) $request->input('dateFrom', ''));
        $dateTo          = trim((string) $request->input('dateTo', ''));
        $clipOverlapDates = $request->boolean('clipOverlapDates');

        $rows = $this->filteredExportQuery($request)
            ->get()
            ->map(fn(SafariAllocation $a): array => $this->transform($a))
            ->map(fn(array $row): array => $this->buildExportRow($row, $dateFrom, $dateTo, $clipOverlapDates))
            ->values();

        $filename = 'safari-allocations-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($out, ['Booking Ref', 'Client', 'Group Name', 'PI No', 'Route / Parks', 'Vehicle No', 'Driver', 'Start Date', 'End Date', 'Utilized Days', 'Status']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    (string) ($row['lead']['bookingRef'] ?? '-'),
                    (string) ($row['lead']['clientCompany'] ?? '-'),
                    (string) ($row['lead']['groupName'] ?? ''),
                    (string) ($row['proformaInvoice']['piNo'] ?? '-'),
                    (string) ($row['lead']['routeParks'] ?? '-'),
                    (string) ($row['vehicle']['vehicleNo'] ?? '-'),
                    (string) ($row['driver']['name'] ?? ''),
                    $this->formatExportDate((string) ($row['exportStartDate'] ?? $row['startDate'] ?? '')),
                    $this->formatExportDate((string) ($row['exportEndDate'] ?? $row['endDate'] ?? '')),
                    (string) ($row['utilizedDays'] ?? 0),
                    (string) ($row['status'] ?? ''),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorize('viewAny', SafariAllocation::class);

        // Dompdf needs more than the default 128 MB to render the full allocation table.
        ini_set('memory_limit', '384M');

        $dateFrom        = trim((string) $request->input('dateFrom', ''));
        $dateTo          = trim((string) $request->input('dateTo', ''));
        $clipOverlapDates = $request->boolean('clipOverlapDates');

        $rows = $this->filteredExportQuery($request)
            ->get()
            ->map(function (SafariAllocation $allocation): array {
                $row = $this->transform($allocation);
                if ($allocation->driver) {
                    $row['driver']['driverLicense'] = $allocation->driver->driver_license;
                    $row['driver']['guideLicense'] = $allocation->driver->tour_guide_license;
                }

                return $row;
            })
            ->map(fn(array $row): array => $this->buildExportRow($row, $dateFrom, $dateTo, $clipOverlapDates))
            ->values();

        $company = [
            'name'    => Setting::get('company_name', config('app.name')),
            'email'   => Setting::get('company_email'),
            'phone'   => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
        ];

        $filters = [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
            'status'   => $request->input('status'),
            'vehicle' => ($vehicle = $request->filled('vehicleId') ? Vehicle::find($request->input('vehicleId')) : null)
                ? trim(($vehicle->vehicle_no ?? '') . ' / ' . ($vehicle->plate_no ?? ''))
                : 'All',
            'search'   => $request->input('search'),
            'clipOverlapDates' => $clipOverlapDates,
        ];

        $pdf = Pdf::loadView('safari-allocations.pdf', [
            'rows'         => $rows,
            'company'      => $company,
            'logoDataUri'  => $this->resolveLogoDataUri(),
            'filters'      => $filters,
            'generatedAt'  => now()->format('d M Y H:i'),
            'stats'        => [
                'total'      => $rows->count(),
                'assigned'   => $rows->where('status', 'Assigned')->count(),
                'inProgress' => $rows->where('status', 'In Progress')->count(),
                'completed'  => $rows->where('status', 'Completed')->count(),
            ],
        ])->setPaper('a4', 'landscape');

        $dompdf      = $pdf->getDomPDF();
        $dompdf->render();
        $canvas      = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font        = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $pageText    = 'Page {PAGE_NUM} of {PAGE_COUNT}';
        $fontSize    = 8;
        $x           = $canvas->get_width() - $fontMetrics->getTextWidth($pageText, $font, $fontSize) - 28;
        $y           = $canvas->get_height() - 22;
        $canvas->page_text($x, $y, $pageText, $font, $fontSize, [0.42, 0.45, 0.50]);

        $filename = 'safari-allocations-report-' . now()->format('Ymd-His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function resolveLogoDataUri(): ?string
    {
        $logoPath = Setting::get('logo');
        if (! is_string($logoPath) || $logoPath === '' || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }
        $contents  = Storage::disk('public')->get($logoPath);
        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime      = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'image/png',
        };
        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function formatExportDate(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }
        try {
            return Carbon::parse($value)->format('d F Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    /** @return array<string, mixed> */
    private function buildExportRow(array $row, string $dateFrom = '', string $dateTo = '', bool $clipOverlapDates = false): array
    {
        ['startDate' => $exportStartDate, 'endDate' => $exportEndDate] = $this->resolveExportDateRange(
            (string) ($row['startDate'] ?? ''),
            (string) ($row['endDate'] ?? ''),
            $dateFrom,
            $dateTo,
            $clipOverlapDates,
        );

        $row['exportStartDate'] = $exportStartDate;
        $row['exportEndDate']   = $exportEndDate;
        $row['utilizedDays']    = $this->countUtilizedDays(
            (string) ($row['startDate'] ?? ''),
            (string) ($row['endDate'] ?? ''),
            $dateFrom,
            $dateTo,
        );
        return $row;
    }

    /** @return array{startDate: string, endDate: string} */
    private function resolveExportDateRange(
        string $startDate,
        string $endDate,
        string $dateFrom = '',
        string $dateTo = '',
        bool $clipOverlapDates = false,
    ): array {
        $resolvedEnd = trim($endDate) !== '' ? $endDate : $startDate;
        if (! $clipOverlapDates) {
            return ['startDate' => $startDate, 'endDate' => $resolvedEnd];
        }
        try {
            $aStart       = Carbon::parse($startDate)->startOfDay();
            $aEnd         = Carbon::parse($resolvedEnd)->startOfDay();
            $displayStart = $aStart->copy();
            $displayEnd   = $aEnd->copy();
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                $wStart = Carbon::parse($dateFrom)->startOfDay();
                if ($aStart->lt($wStart) && $aEnd->gte($wStart)) {
                    $displayStart = $wStart;
                }
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                $wEnd = Carbon::parse($dateTo)->startOfDay();
                if ($aEnd->gt($wEnd) && $aStart->lte($wEnd)) {
                    $displayEnd = $wEnd;
                }
            }
            if ($displayEnd->lt($displayStart)) {
                $displayEnd = $displayStart->copy();
            }
            return ['startDate' => $displayStart->toDateString(), 'endDate' => $displayEnd->toDateString()];
        } catch (\Throwable) {
            return ['startDate' => $startDate, 'endDate' => $resolvedEnd];
        }
    }

    private function countUtilizedDays(string $startDate, string $endDate, string $dateFrom = '', string $dateTo = ''): int
    {
        if (trim($startDate) === '') {
            return 0;
        }
        try {
            $aStart      = Carbon::parse($startDate)->startOfDay();
            $aEnd        = trim($endDate) !== '' ? Carbon::parse($endDate)->startOfDay() : $aStart->copy();
            $windowStart = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? Carbon::parse($dateFrom)->startOfDay() : $aStart->copy();
            $windowEnd   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? Carbon::parse($dateTo)->startOfDay() : $aEnd->copy();
            $start       = $aStart->greaterThan($windowStart) ? $aStart : $windowStart;
            $end         = $aEnd->lessThan($windowEnd) ? $aEnd : $windowEnd;
            return $end->lessThan($start) ? 0 : ((int) $start->diffInDays($end)) + 1;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function filteredExportQuery(Request $request)
    {
        $query = SafariAllocation::query()
            ->with(['lead', 'proformaInvoice', 'vehicle', 'driver'])
            ->latest('id');

        if ($request->user()?->hasRole('Driver')) {
            $query->where('driver_id', $request->user()->id);
        }

        $dateFrom = trim((string) $request->input('dateFrom', ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $query->whereDate('end_date', '>=', $dateFrom);
        }

        $dateTo = trim((string) $request->input('dateTo', ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $query->whereDate('start_date', '<=', $dateTo);
        }

        $status = trim((string) $request->input('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($request->filled('vehicleId')) {
            $query->where('vehicle_id', (int) $request->input('vehicleId'));
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->whereHas('lead', fn($lq) => $lq
                    ->where('booking_ref', 'like', '%' . $search . '%')
                    ->orWhere('client_company', 'like', '%' . $search . '%')
                    ->orWhere('group_name', 'like', '%' . $search . '%')
                    ->orWhere('route_parks', 'like', '%' . $search . '%'))
                    ->orWhereHas('vehicle', fn($vq) => $vq
                        ->where('vehicle_no', 'like', '%' . $search . '%')
                        ->orWhere('plate_no', 'like', '%' . $search . '%'))
                    ->orWhereHas('driver', fn($dq) => $dq
                        ->where('name', 'like', '%' . $search . '%'));
            });
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(SafariAllocation $allocation): array
    {
        $resolvedStartDate = optional($allocation->start_date)->format('Y-m-d')
            ?: optional($allocation->lead?->start_date)->format('Y-m-d');
        $resolvedEndDate = optional($allocation->end_date)->format('Y-m-d')
            ?: optional($allocation->lead?->end_date)->format('Y-m-d');
        $latestQuotation = $allocation->lead?->quotations?->sortByDesc(function ($quotation) {
            return $quotation->quote_date?->timestamp ?: $quotation->created_at?->timestamp ?: 0;
        })->first();
        $itinerary = $latestQuotation?->day_sections ?? [];

        return [
            'id' => $allocation->id,
            'leadId' => $allocation->lead_id,
            'proformaInvoiceId' => $allocation->proforma_invoice_id,
            'vehicleId' => $allocation->vehicle_id,
            'driverId' => $allocation->driver_id,
            'vehicleLeaseType' => $allocation->vehicle_lease_type ?: 'Short-Term Lease',
            'startDate' => $resolvedStartDate,
            'endDate' => $resolvedEndDate,
            'status' => $allocation->status,
            'notes' => $allocation->notes,
            'createdAt' => $allocation->created_at?->toIso8601String(),
            'updatedAt' => $allocation->updated_at?->toIso8601String(),
            'lead' => $allocation->lead ? [
                'id' => $allocation->lead->id,
                'bookingRef' => $allocation->lead->booking_ref,
                'clientCompany' => $allocation->lead->client_company,
                'groupName' => $allocation->lead->group_name ?: $latestQuotation?->group_name,
                'agentContact' => $allocation->lead->agent_contact,
                'startDate' => optional($allocation->lead->start_date)->format('Y-m-d'),
                'endDate' => optional($allocation->lead->end_date)->format('Y-m-d'),
                'routeParks' => $allocation->lead->route_parks,
                'daySections' => $itinerary,
                'itinerary' => $itinerary,
            ] : null,
            'proformaInvoice' => $allocation->proformaInvoice ? [
                'id' => $allocation->proformaInvoice->id,
                'piNo' => $allocation->proformaInvoice->proforma_number
                    ?: 'PI-' . optional($allocation->proformaInvoice->created_at)->format('Y-m') . '-' . str_pad((string) $allocation->proformaInvoice->id, 3, '0', STR_PAD_LEFT),
            ] : null,
            'vehicle' => $allocation->vehicle ? [
                'id' => $allocation->vehicle->id,
                'vehicleNo' => $allocation->vehicle->vehicle_no,
                'plateNo' => $allocation->vehicle->plate_no,
                'make' => $allocation->vehicle->make,
                'model' => $allocation->vehicle->model,
            ] : null,
            'driver' => $allocation->driver ? [
                'id' => $allocation->driver->id,
                'name' => $allocation->driver->name,
                'phone' => $allocation->driver->phone,
            ] : null,
            // Number of odometer log entries already recorded for this trip.
            // Surfaces in the driver app's trip list as "X odometer entries
            // recorded" so drivers can see progress at a glance.
            'odometer_log_count' => (int) ($allocation->odometer_logs_count ?? 0),
            'odometerLogCount' => (int) ($allocation->odometer_logs_count ?? 0),
            'latestOdometerLogAt' => $allocation->odometer_logs_max_recorded_at,
            'latest_odometer_log_at' => $allocation->odometer_logs_max_recorded_at,
        ];
    }

    /** @return array<string, mixed> */
    private function transformCompact(SafariAllocation $allocation): array
    {
        return [
            'id' => $allocation->id,
            'leadId' => $allocation->lead_id,
            'vehicleId' => $allocation->vehicle_id,
            'status' => $allocation->status,
            'lead' => $allocation->lead ? [
                'id' => $allocation->lead->id,
                'bookingRef' => $allocation->lead->booking_ref,
                'clientCompany' => $allocation->lead->client_company,
                'groupName' => $allocation->lead->group_name,
            ] : null,
            'vehicle' => $allocation->vehicle ? [
                'id' => $allocation->vehicle->id,
                'vehicleNo' => $allocation->vehicle->vehicle_no,
                'plateNo' => $allocation->vehicle->plate_no,
                'make' => $allocation->vehicle->make,
                'model' => $allocation->vehicle->model,
            ] : null,
        ];
    }

    private function syncProformaStatusesForAllocation(
        SafariAllocation $allocation,
        ?int $previousLeadId = null,
        ?int $previousProformaInvoiceId = null
    ): void {
        $leadIds = [(int) $allocation->lead_id];
        if ($previousLeadId !== null && $previousLeadId > 0) {
            $leadIds[] = $previousLeadId;
        }

        $proformaInvoiceIds = [];
        if ($allocation->proforma_invoice_id !== null) {
            $proformaInvoiceIds[] = (int) $allocation->proforma_invoice_id;
        }
        if ($previousProformaInvoiceId !== null && $previousProformaInvoiceId > 0) {
            $proformaInvoiceIds[] = $previousProformaInvoiceId;
        }

        $this->syncProformaStatusesByRefs($leadIds, $proformaInvoiceIds);
    }

    /**
     * @param array<int, int> $leadIds
     * @param array<int, int> $proformaInvoiceIds
     */
    private function syncProformaStatusesByRefs(array $leadIds, array $proformaInvoiceIds): void
    {
        $leadIds = array_values(array_unique(array_filter(array_map('intval', $leadIds), static fn(int $id): bool => $id > 0)));
        $proformaInvoiceIds = array_values(array_unique(array_filter(array_map('intval', $proformaInvoiceIds), static fn(int $id): bool => $id > 0)));

        if ($leadIds === [] && $proformaInvoiceIds === []) {
            return;
        }

        $proformas = ProformaInvoice::query()
            ->when($leadIds !== [], fn($query) => $query->whereIn('lead_id', $leadIds))
            ->when(
                $proformaInvoiceIds !== [],
                fn($query) => $query->orWhereIn('id', $proformaInvoiceIds)
            )
            ->with('lead')
            ->get();

        foreach ($proformas as $proformaInvoice) {
            $this->syncSingleProformaStatus($proformaInvoice);
        }
    }

    private function syncSingleProformaStatus(ProformaInvoice $proformaInvoice): void
    {
        if (in_array($proformaInvoice->status, ['Deposit', 'Paid'], true)) {
            return;
        }

        $requestedVehicles = max(0, (int) ($proformaInvoice->lead?->no_of_vehicles ?? 0));

        $allocatedVehicles = SafariAllocation::query()
            ->where('lead_id', $proformaInvoice->lead_id)
            ->whereNotNull('vehicle_id')
            ->where(function ($query) use ($proformaInvoice): void {
                $query->whereNull('proforma_invoice_id')
                    ->orWhere('proforma_invoice_id', $proformaInvoice->id);
            })
            ->distinct('vehicle_id')
            ->count('vehicle_id');

        $nextStatus = $proformaInvoice->status;

        if ($allocatedVehicles > 0) {
            if ($requestedVehicles > 0 && $allocatedVehicles >= $requestedVehicles) {
                $nextStatus = 'Allocated';
            } else {
                $nextStatus = 'Partially Allocated';
            }
        } elseif (($nextStatus ?? '') === 'Allocated' || ($nextStatus ?? '') === 'Partially Allocated') {
            $nextStatus = 'Confirmed';
        }

        if ($nextStatus !== $proformaInvoice->status) {
            $proformaInvoice->status = $nextStatus;
            $proformaInvoice->save();
        }
    }

    private function validateVehicleLeaseTypeMatch(int $vehicleId, string $vehicleLeaseType): void
    {
        $vehicle = Vehicle::query()->find($vehicleId);
        if ($vehicle === null) {
            return;
        }

        $isSubcontractedVehicle = strcasecmp((string) ($vehicle->ownership_type ?? 'Internal'), 'Subcontracted') === 0;
        if ($vehicleLeaseType === 'Subcontract' && ! $isSubcontractedVehicle) {
            throw ValidationException::withMessages([
                'vehicleLeaseType' => ['Subcontract lease type requires a subcontracted vehicle.'],
            ]);
        }

        if ($vehicleLeaseType !== 'Subcontract' && $isSubcontractedVehicle) {
            throw ValidationException::withMessages([
                'vehicleLeaseType' => ['Subcontracted vehicles can only be assigned with lease type Subcontract.'],
            ]);
        }
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{0: string, 1: string}
     */
    private function resolveAllocationDates(
        array $validated,
        ?string $defaultStartDate,
        ?string $defaultEndDate,
        ?string $leadStartDate,
        ?string $leadEndDate
    ): array {
        $startDate = $validated['startDate'] ?? $defaultStartDate;
        $endDate = $validated['endDate'] ?? $defaultEndDate;

        if (!$startDate || !$endDate) {
            throw ValidationException::withMessages([
                'startDate' => ['Allocation start and end dates are required.'],
            ]);
        }

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            throw ValidationException::withMessages([
                'endDate' => ['Allocation end date must be on or after start date.'],
            ]);
        }

        if ($leadStartDate && Carbon::parse($startDate)->lt(Carbon::parse($leadStartDate))) {
            throw ValidationException::withMessages([
                'startDate' => ['Allocation start date must be within safari dates.'],
            ]);
        }

        if ($leadEndDate && Carbon::parse($endDate)->gt(Carbon::parse($leadEndDate))) {
            throw ValidationException::withMessages([
                'endDate' => ['Allocation end date must be within safari dates.'],
            ]);
        }

        return [
            Carbon::parse($startDate)->toDateString(),
            Carbon::parse($endDate)->toDateString(),
        ];
    }
}
