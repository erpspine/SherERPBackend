<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\Lead;
use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\SafariAllocation;
use App\Models\Setting;
use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ProformaInvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $proformaInvoices = ProformaInvoice::query()->with(['lineItems', 'lead', 'quotation'])->latest('id')->get();

        return response()->json([
            'message' => 'Proforma invoices fetched successfully.',
            'proformaInvoices' => $proformaInvoices
                ->map(fn(ProformaInvoice $proformaInvoice): array => $this->transformProformaInvoice($proformaInvoice))
                ->values(),
        ]);
    }

    public function show(ProformaInvoice $proformaInvoice): JsonResponse
    {
        $proformaInvoice->load(['lineItems', 'lead', 'quotation']);

        return response()->json([
            'message' => 'Proforma invoice fetched successfully.',
            'proformaInvoice' => $this->transformProformaInvoice($proformaInvoice),
        ]);
    }

    public function convertFromQuotation(Request $request, Quotation $quotation): JsonResponse
    {
        $senderId = $request->user()?->id;
        [$proformaInvoice, $created, $allocationSummary] = DB::transaction(function () use ($quotation, $senderId): array {
            $quotation->load(['lineItems', 'lead']);

            $proformaInvoice = ProformaInvoice::query()->where('quotation_id', $quotation->id)->first();
            $created = false;

            if ($proformaInvoice === null) {
                $proformaInvoice = new ProformaInvoice([
                    'proforma_number' => $this->generateProformaNumber(),
                    'quotation_id' => $quotation->id,
                    'status' => 'Converted',
                ]);
                $created = true;
            }

            if (empty($proformaInvoice->proforma_number)) {
                $proformaInvoice->proforma_number = $this->generateProformaNumber();
            }

            $proformaInvoice->fill([
                'lead_id' => $quotation->lead_id,
                'client' => $quotation->client,
                'attention' => $quotation->attention,
                'quote_date' => $quotation->quote_date,
                'notes' => $quotation->notes,
                'day_sections' => $quotation->day_sections,
                'subtotal' => $quotation->subtotal,
                'tax' => $quotation->tax,
                'total' => $quotation->total,
            ]);
            $proformaInvoice->save();

            $proformaInvoice->lineItems()->delete();

            foreach ($quotation->lineItems as $lineItem) {
                $proformaInvoice->lineItems()->create([
                    'day_title' => $lineItem->day_title,
                    'day_description' => $lineItem->day_description,
                    'item' => $lineItem->item,
                    'description' => $lineItem->description,
                    'unit' => $lineItem->unit,
                    'qty' => $lineItem->qty,
                    'rate' => $lineItem->rate,
                    'total' => $lineItem->total,
                ]);
            }

            $quotation->update([
                'status' => 'Converted',
            ]);

            if (!empty($quotation->lead_id)) {
                Lead::query()->whereKey($quotation->lead_id)->update([
                    'booking_status' => 'PI Sent',
                    'pi_sent_by' => $senderId,
                    'pi_sent_at' => now(),
                ]);
            }

            $allocationSummary = [
                'mode' => 'later',
                'allocationType' => 'ranges',
                'vehiclesRequested' => 0,
                'allocationsCreated' => 0,
                'jobCardsCreated' => 0,
                'extraAllocationsCreated' => 0,
            ];

            return [$proformaInvoice->fresh(['lineItems', 'lead', 'quotation']), $created, $allocationSummary];
        });

        return response()->json([
            'message' => $created
                ? 'Quotation converted to PI successfully.'
                : 'Proforma invoice regenerated from quotation successfully.',
            'proformaInvoice' => $this->transformProformaInvoice($proformaInvoice),
            'allocationSummary' => $allocationSummary,
        ], $created ? 201 : 200);
    }

    public function confirm(Request $request, ProformaInvoice $proformaInvoice): JsonResponse
    {
        $proformaInvoice->load(['lead', 'quotation', 'lineItems']);

        DB::transaction(function () use ($proformaInvoice): void {
            if (($proformaInvoice->status ?? '') !== 'Confirmed') {
                $proformaInvoice->status = 'Confirmed';
                $proformaInvoice->save();
            }

            if ($proformaInvoice->lead_id) {
                Lead::query()->whereKey($proformaInvoice->lead_id)->update([
                    'booking_status' => 'Confirmed',
                ]);
            }
        });

        return response()->json([
            'message' => 'Proforma invoice confirmed successfully.',
            'proformaInvoice' => $this->transformProformaInvoice($proformaInvoice->fresh(['lineItems', 'lead', 'quotation'])),
        ]);
    }

    public function allocateVehicles(Request $request, ProformaInvoice $proformaInvoice): JsonResponse
    {
        $validated = $request->validate([
            'allocationRanges' => ['required', 'array', 'min:1'],
            'allocationRanges.*.startDate' => ['required_with:allocationRanges', 'date_format:Y-m-d'],
            'allocationRanges.*.endDate' => ['required_with:allocationRanges', 'date_format:Y-m-d'],
            'allocationRanges.*.vehicleIds' => ['required_with:allocationRanges', 'array', 'min:1'],
            'allocationRanges.*.vehicleIds.*' => ['integer', 'exists:vehicles,id'],
        ]);

        $proformaInvoice->load(['quotation', 'lead', 'lineItems']);

        if (!in_array($proformaInvoice->status ?? '', ['Confirmed', 'Partially Allocated'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Proforma invoice must be confirmed before vehicles can be allocated.'],
            ]);
        }

        $quotation = $proformaInvoice->quotation;
        if ($quotation === null) {
            throw ValidationException::withMessages([
                'quotation' => ['PI allocation requires a linked quotation.'],
            ]);
        }

        $allocationRanges = array_values(array_map(
            static function (array $item): array {
                $rangeVehicleIds = array_values(array_unique(array_map(
                    static fn(mixed $value): int => (int) $value,
                    Arr::wrap($item['vehicleIds'] ?? [])
                )));

                return [
                    'startDate' => (string) ($item['startDate'] ?? ''),
                    'endDate' => (string) ($item['endDate'] ?? ''),
                    'vehicleIds' => $rangeVehicleIds,
                ];
            },
            Arr::wrap($validated['allocationRanges'] ?? [])
        ));

        $allocationSummary = DB::transaction(function () use ($quotation, $proformaInvoice, $allocationRanges): array {
            $summary = $this->createOperationalRecordsFromVehicleAllocation(
                $quotation,
                $proformaInvoice,
                $allocationRanges,
            );

            // Create/refresh the lead-level JobCard using the allocation window.
            $jobCardSummary = ['jobCardsCreated' => 0, 'jobCardId' => 0];
            if ($proformaInvoice->lead_id) {
                $startDates = array_filter(array_column($allocationRanges, 'startDate'));
                $endDates = array_filter(array_column($allocationRanges, 'endDate'));
                if ($startDates && $endDates) {
                    $jobCardSummary = JobCard::ensureForLead(
                        (int) $proformaInvoice->lead_id,
                        min($startDates),
                        max($endDates),
                    );
                }
            }

            $statusSummary = $this->syncProformaAllocationStatus($proformaInvoice);

            return array_merge($summary, $statusSummary, $jobCardSummary);
        });

        return response()->json([
            'message' => 'Vehicle allocation saved successfully.',
            'allocationSummary' => $allocationSummary,
            'proformaInvoice' => $this->transformProformaInvoice($proformaInvoice->fresh(['lineItems', 'lead', 'quotation'])),
        ]);
    }

    /**
     * @return array{requestedVehicles: int, allocatedVehicles: int, allocationProgress: string}
     */
    private function syncProformaAllocationStatus(ProformaInvoice $proformaInvoice): array
    {
        $proformaInvoice->loadMissing('lead');

        $requestedVehicles = max(0, (int) ($proformaInvoice->lead?->no_of_vehicles ?? 0));
        $allocatedVehicles = SafariAllocation::query()
            ->where('proforma_invoice_id', $proformaInvoice->id)
            ->distinct('vehicle_id')
            ->count('vehicle_id');

        $nextStatus = $proformaInvoice->status;
        $allocationProgress = 'none';

        if ($allocatedVehicles > 0) {
            if ($requestedVehicles > 0 && $allocatedVehicles >= $requestedVehicles) {
                $nextStatus = 'Allocated';
                $allocationProgress = 'full';
            } else {
                $nextStatus = 'Partially Allocated';
                $allocationProgress = 'partial';
            }
        } elseif (($nextStatus ?? '') === 'Allocated' || ($nextStatus ?? '') === 'Partially Allocated') {
            $nextStatus = 'Confirmed';
        }

        if ($nextStatus !== $proformaInvoice->status) {
            $proformaInvoice->status = $nextStatus;
            $proformaInvoice->save();
        }

        return [
            'requestedVehicles' => $requestedVehicles,
            'allocatedVehicles' => (int) $allocatedVehicles,
            'allocationProgress' => $allocationProgress,
        ];
    }

    /**
     * @param array<int, array{startDate: string, endDate: string, vehicleIds: array<int, int>}> $allocationRanges
     * @return array{mode: string, allocationType: string, vehiclesRequested: int, allocationsCreated: int, extraAllocationsCreated: int}
     */
    private function createOperationalRecordsFromVehicleAllocation(
        Quotation $quotation,
        ProformaInvoice $proformaInvoice,
        array $allocationRanges,
    ): array {
        $lead = $quotation->lead;

        if ($lead === null) {
            throw ValidationException::withMessages([
                'allocationMode' => ['Vehicle allocation requires a quotation linked to a lead.'],
            ]);
        }

        if ($lead->start_date === null || $lead->end_date === null) {
            throw ValidationException::withMessages([
                'allocationMode' => ['Vehicle allocation requires lead start and end dates.'],
            ]);
        }

        if ($allocationRanges === []) {
            throw ValidationException::withMessages([
                'allocationRanges' => ['At least one allocation range is required for Allocate Now mode.'],
            ]);
        }

        foreach ($allocationRanges as $range) {
            $startDate = (string) ($range['startDate'] ?? '');
            $endDate = (string) ($range['endDate'] ?? '');

            if ($startDate === '' || $endDate === '') {
                throw ValidationException::withMessages([
                    'allocationRanges' => ['Each allocation range must include a start and end date.'],
                ]);
            }

            if ($startDate > $endDate) {
                throw ValidationException::withMessages([
                    'allocationRanges' => ['Allocation range end date must be on or after start date.'],
                ]);
            }

            if ($startDate < $lead->start_date->toDateString() || $endDate > $lead->end_date->toDateString()) {
                throw ValidationException::withMessages([
                    'allocationRanges' => ['Allocation ranges must be within the safari lead date range.'],
                ]);
            }
        }

        $allVehicleIds = collect($allocationRanges)
            ->flatMap(fn(array $range): array => Arr::wrap($range['vehicleIds'] ?? []))
            ->filter()
            ->map(static fn(mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        $vehicles = Vehicle::query()
            ->with('assignedDriver:id,name,email')
            ->whereIn('id', $allVehicleIds)
            ->get()
            ->keyBy('id');

        $allocationsCreated = 0;
        $extraAllocationsCreated = 0;

        foreach ($allocationRanges as $range) {
            $rangeStartDate = (string) $range['startDate'];
            $rangeEndDate = (string) $range['endDate'];
            $rangeVehicleIds = array_values(array_unique(array_map(
                static fn(mixed $value): int => (int) $value,
                Arr::wrap($range['vehicleIds'] ?? [])
            )));

            if ($rangeVehicleIds === []) {
                continue;
            }

            foreach ($rangeVehicleIds as $vehicleId) {
                /** @var Vehicle|null $vehicle */
                $vehicle = $vehicles->get($vehicleId);
                if ($vehicle === null) {
                    continue;
                }

                $allocation = SafariAllocation::query()->firstOrNew([
                    'lead_id' => $lead->id,
                    'proforma_invoice_id' => $proformaInvoice->id,
                    'vehicle_id' => $vehicle->id,
                    'start_date' => $rangeStartDate,
                    'end_date' => $rangeEndDate,
                ]);

                if (! $allocation->exists) {
                    $allocationsCreated++;
                    if ($rangeStartDate !== $lead->start_date->toDateString() || $rangeEndDate !== $lead->end_date->toDateString()) {
                        $extraAllocationsCreated++;
                    }
                }

                $allocation->fill([
                    'driver_id' => $vehicle->assigned_driver_id,
                    'status' => 'Assigned',
                    'notes' => $allocation->notes ?: 'Auto-created from quotation to PI conversion.',
                ]);
                $allocation->save();
            }
        }

        return [
            'mode' => 'now',
            'allocationType' => 'ranges',
            'vehiclesRequested' => count($allVehicleIds),
            'allocationsCreated' => $allocationsCreated,
            'extraAllocationsCreated' => $extraAllocationsCreated,
        ];
    }

    private function generateProformaNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $prefix = 'PI-' . $year . '-' . $month . '-';

        $last = ProformaInvoice::query()
            ->where('proforma_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('proforma_number')
            ->value('proforma_number');

        $seq = $last ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function pdf(ProformaInvoice $proformaInvoice): Response
    {
        $proformaInvoice->load(['lineItems', 'lead', 'quotation']);

        $company = [
            'name'                    => Setting::get('company_name', config('app.name')),
            'email'                   => Setting::get('company_email'),
            'phone'                   => Setting::get('company_phone'),
            'address'                 => Setting::get('company_address'),
            'tax_registration_number' => Setting::get('tax_registration_number'),
            'currency'                => Setting::get('default_currency', 'TZS'),
            'vat'                     => Setting::get('default_vat', '0'),
        ];

        $pdf = Pdf::loadView('proforma-invoices.pdf', [
            'proformaInvoice' => $this->transformProformaInvoice($proformaInvoice),
            'company'         => $company,
            'logoDataUri'     => $this->resolveLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');

        $pageText = 'Page {PAGE_NUM} of {PAGE_COUNT}';
        $fontSize = 9;
        $x = $canvas->get_width() - $fontMetrics->getTextWidth($pageText, $font, $fontSize) - 28;
        $y = $canvas->get_height() - 24;
        $canvas->page_text($x, $y, $pageText, $font, $fontSize, [0.42, 0.45, 0.50]);

        $filename = 'proforma-invoice-' . ($proformaInvoice->proforma_number ?: $proformaInvoice->id) . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
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

    /**
     * @return array<string, mixed>
     */
    private function transformProformaInvoice(ProformaInvoice $proformaInvoice): array
    {
        $lineItems = $proformaInvoice->lineItems->map(fn($item): array => [
            'id' => $item->id,
            'dayTitle' => $item->day_title,
            'dayDescription' => $item->day_description,
            'item' => $item->item,
            'description' => $item->description,
            'unit' => $item->unit,
            'qty' => (float) $item->qty,
            'rate' => (float) $item->rate,
            'total' => (float) $item->total,
        ])->values();

        $serviceSummary = $this->extractServiceSummary([
            'lineItems' => $lineItems->all(),
            'daySections' => $proformaInvoice->day_sections ?? [],
        ], $proformaInvoice->lead?->route_parks);

        $quotationNumber = $proformaInvoice->quotation?->quotation_number
            ?: ($proformaInvoice->quotation?->quote_date
                ? 'QT-' . $proformaInvoice->quotation->quote_date->format('Y-m') . '-' . str_pad((string) $proformaInvoice->quotation->id, 3, '0', STR_PAD_LEFT)
                : null);

        return [
            'id' => $proformaInvoice->id,
            'proformaNumber' => $proformaInvoice->proforma_number,
            'piNo' => $proformaInvoice->proforma_number,
            'quotationId' => $proformaInvoice->quotation_id,
            'quotationNumber' => $quotationNumber,
            'leadId' => $proformaInvoice->lead_id,
            'client' => $proformaInvoice->client,
            'attention' => $proformaInvoice->attention,
            'groupName' => $proformaInvoice->quotation?->group_name,
            'leadStartDate' => optional($proformaInvoice->lead?->start_date)->format('Y-m-d'),
            'leadEndDate' => optional($proformaInvoice->lead?->end_date)->format('Y-m-d'),
            'leadRouteParks' => $proformaInvoice->lead?->route_parks,
            'quoteDate' => optional($proformaInvoice->quote_date)->format('d/m/Y'),
            'notes' => $proformaInvoice->notes,
            'serviceSummary' => $serviceSummary,
            'daySections' => $proformaInvoice->day_sections ?? [],
            'lineItems' => $lineItems,
            'subtotal' => (float) $proformaInvoice->subtotal,
            'tax' => (float) $proformaInvoice->tax,
            'total' => (float) $proformaInvoice->total,
            'status' => $proformaInvoice->status,
            'createdAt' => $proformaInvoice->created_at?->toISOString(),
            'updatedAt' => $proformaInvoice->updated_at?->toISOString(),
        ];
    }

    /**
     * @param array<string, mixed> $invoice
     */
    private function extractServiceSummary(array $invoice, ?string $leadRoute = null): string
    {
        $normalizedLeadRoute = trim((string) $leadRoute);

        if ($normalizedLeadRoute !== '') {
            return $normalizedLeadRoute;
        }

        $daySections = $invoice['daySections'] ?? [];

        if (is_array($daySections) && $daySections !== []) {
            $routesFromSections = collect($daySections)
                ->map(fn($section) => is_array($section) ? trim((string) ($section['dayDescription'] ?? '')) : '')
                ->filter()
                ->unique()
                ->take(3)
                ->values();

            if ($routesFromSections->isNotEmpty()) {
                return $routesFromSections->implode(' | ');
            }
        }

        $lineItems = $invoice['lineItems'] ?? [];
        if (! is_array($lineItems) || $lineItems === []) {
            return '-';
        }

        $items = collect($lineItems)
            ->map(fn($item) => is_array($item)
                ? trim((string) (($item['dayDescription'] ?? $item['description'] ?? '')))
                : '')
            ->filter()
            ->unique()
            ->take(3)
            ->values();

        return $items->isEmpty() ? '-' : $items->implode(' | ');
    }
}
