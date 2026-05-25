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
use Illuminate\Validation\Rule;
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
        $validated = $request->validate([
            'allocationMode' => ['nullable', 'string', Rule::in(['now', 'later'])],
            'allocationRanges' => ['nullable', 'array'],
            'allocationRanges.*.startDate' => ['required_with:allocationRanges', 'date_format:Y-m-d'],
            'allocationRanges.*.endDate' => ['required_with:allocationRanges', 'date_format:Y-m-d'],
            'allocationRanges.*.vehicleIds' => ['required_with:allocationRanges', 'array'],
            'allocationRanges.*.vehicleIds.*' => ['integer', 'exists:vehicles,id'],
            'allocationType' => ['nullable', 'string', Rule::in(['full', 'full-plus-single'])],
            'vehicleIds' => ['nullable', 'array'],
            'vehicleIds.*' => ['integer', 'exists:vehicles,id'],
            'extraDayAllocations' => ['nullable', 'array'],
            'extraDayAllocations.*.date' => ['required_with:extraDayAllocations', 'date_format:Y-m-d'],
            'extraDayAllocations.*.vehicleIds' => ['nullable', 'array'],
            'extraDayAllocations.*.vehicleIds.*' => ['integer', 'exists:vehicles,id'],
        ]);

        $allocationMode = $validated['allocationMode'] ?? 'later';
        $allocationType = $validated['allocationType'] ?? 'full';
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
        $vehicleIds = array_values(array_unique(array_map(
            static fn(mixed $value): int => (int) $value,
            Arr::wrap($validated['vehicleIds'] ?? [])
        )));
        $extraDayAllocations = array_values(array_map(
            static function (array $item): array {
                $extraVehicleIds = array_values(array_unique(array_map(
                    static fn(mixed $value): int => (int) $value,
                    Arr::wrap($item['vehicleIds'] ?? [])
                )));

                return [
                    'date' => (string) ($item['date'] ?? ''),
                    'vehicleIds' => $extraVehicleIds,
                ];
            },
            Arr::wrap($validated['extraDayAllocations'] ?? [])
        ));

        [$proformaInvoice, $created, $allocationSummary] = DB::transaction(function () use ($quotation, $senderId, $allocationMode, $allocationType, $allocationRanges, $vehicleIds, $extraDayAllocations): array {
            $quotation->load(['lineItems', 'lead']);

            $proformaInvoice = ProformaInvoice::query()->where('quotation_id', $quotation->id)->first();
            $created = false;

            if ($proformaInvoice === null) {
                $proformaInvoice = new ProformaInvoice([
                    'proforma_number' => $this->generateProformaNumber(),
                    'quotation_id' => $quotation->id,
                    'status' => 'Sent',
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
                'mode' => $allocationMode,
                'allocationType' => $allocationType,
                'vehiclesRequested' => count($vehicleIds),
                'allocationsCreated' => 0,
                'jobCardsCreated' => 0,
                'extraAllocationsCreated' => 0,
            ];

            if ($allocationMode === 'now' && ($allocationRanges !== [] || $vehicleIds !== [])) {
                if ($allocationRanges === []) {
                    $allocationRanges = [
                        [
                            'startDate' => optional($quotation->lead?->start_date)->toDateString() ?? '',
                            'endDate' => optional($quotation->lead?->end_date)->toDateString() ?? '',
                            'vehicleIds' => $vehicleIds,
                        ],
                    ];

                    if ($allocationType === 'full-plus-single') {
                        foreach ($extraDayAllocations as $extraDayAllocation) {
                            $date = (string) ($extraDayAllocation['date'] ?? '');
                            $rangeVehicleIds = Arr::wrap($extraDayAllocation['vehicleIds'] ?? []);
                            if ($date === '' || $rangeVehicleIds === []) {
                                continue;
                            }

                            $allocationRanges[] = [
                                'startDate' => $date,
                                'endDate' => $date,
                                'vehicleIds' => array_values(array_unique(array_map(
                                    static fn(mixed $value): int => (int) $value,
                                    $rangeVehicleIds
                                ))),
                            ];
                        }
                    }
                }

                $allocationSummary = $this->createOperationalRecordsFromVehicleAllocation(
                    $quotation,
                    $proformaInvoice,
                    $allocationRanges,
                );
            }

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

    /**
     * @param array<int, array{startDate: string, endDate: string, vehicleIds: array<int, int>}> $allocationRanges
     * @return array{mode: string, allocationType: string, vehiclesRequested: int, allocationsCreated: int, jobCardsCreated: int, extraAllocationsCreated: int}
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
        $jobCardsCreated = 0;

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

                $jobCard = JobCard::query()->firstOrNew([
                    'lead_id' => $lead->id,
                    'vehicle_id' => $vehicle->id,
                    'type' => 'Safari',
                ]);

                if (! $jobCard->exists) {
                    $jobCardsCreated++;
                }

                $jobCard->fill([
                    'status' => $jobCard->status ?: 'Open',
                    'booking_reference_no' => $lead->booking_ref,
                    'tour_operator_client_name' => $lead->client_company,
                    'contact_person' => $lead->agent_contact,
                    'contact_number' => $lead->agent_phone,
                    'contact_email' => $lead->agent_email,
                    'adults' => $lead->pax_adults ?? 0,
                    'children' => $lead->pax_children ?? 0,
                    'nationality' => $lead->client_country,
                    'safari_start_date' => $lead->start_date,
                    'safari_end_date' => $lead->end_date,
                    'number_of_days' => $lead->start_date->diffInDays($lead->end_date) + 1,
                    'route_summary' => $lead->route_parks,
                    'additional_details' => $lead->special_requirements,
                    'pickup_location' => $jobCard->pickup_location,
                    'dropoff_location' => $jobCard->dropoff_location,
                ]);
                $jobCard->save();

                if ($jobCard->job_card_no === null || $jobCard->job_card_no === '') {
                    $jobCard->forceFill([
                        'job_card_no' => 'JC-' . now()->format('Y') . '-' . str_pad((string) $jobCard->id, 4, '0', STR_PAD_LEFT),
                    ])->save();
                }
            }
        }

        return [
            'mode' => 'now',
            'allocationType' => 'ranges',
            'vehiclesRequested' => count($allVehicleIds),
            'allocationsCreated' => $allocationsCreated,
            'jobCardsCreated' => $jobCardsCreated,
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
