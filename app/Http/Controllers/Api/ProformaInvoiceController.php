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
        $proformaInvoices = ProformaInvoice::query()->with('lineItems')->latest('id')->get();

        return response()->json([
            'message' => 'Proforma invoices fetched successfully.',
            'proformaInvoices' => $proformaInvoices
                ->map(fn(ProformaInvoice $proformaInvoice): array => $this->transformProformaInvoice($proformaInvoice))
                ->values(),
        ]);
    }

    public function show(ProformaInvoice $proformaInvoice): JsonResponse
    {
        $proformaInvoice->load('lineItems');

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
            'vehicleIds' => ['nullable', 'array'],
            'vehicleIds.*' => ['integer', 'exists:vehicles,id'],
        ]);

        $allocationMode = $validated['allocationMode'] ?? 'later';
        $vehicleIds = array_values(array_unique(array_map(
            static fn(mixed $value): int => (int) $value,
            Arr::wrap($validated['vehicleIds'] ?? [])
        )));

        [$proformaInvoice, $created, $allocationSummary] = DB::transaction(function () use ($quotation, $senderId, $allocationMode, $vehicleIds): array {
            $quotation->load(['lineItems', 'lead']);

            $proformaInvoice = ProformaInvoice::query()->where('quotation_id', $quotation->id)->first();
            $created = false;

            if ($proformaInvoice === null) {
                $proformaInvoice = new ProformaInvoice([
                    'quotation_id' => $quotation->id,
                    'status' => 'Sent',
                ]);
                $created = true;
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
                'vehiclesRequested' => count($vehicleIds),
                'allocationsCreated' => 0,
                'jobCardsCreated' => 0,
            ];

            if ($allocationMode === 'now' && $vehicleIds !== []) {
                $allocationSummary = $this->createOperationalRecordsFromVehicleAllocation(
                    $quotation,
                    $proformaInvoice,
                    $vehicleIds,
                );
            }

            return [$proformaInvoice->fresh('lineItems'), $created, $allocationSummary];
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
     * @param array<int, int> $vehicleIds
     * @return array{mode: string, vehiclesRequested: int, allocationsCreated: int, jobCardsCreated: int}
     */
    private function createOperationalRecordsFromVehicleAllocation(
        Quotation $quotation,
        ProformaInvoice $proformaInvoice,
        array $vehicleIds,
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

        $vehicles = Vehicle::query()
            ->with('assignedDriver:id,name,email')
            ->whereIn('id', $vehicleIds)
            ->get()
            ->keyBy('id');

        $missingDriverVehicles = collect($vehicleIds)
            ->map(fn(int $vehicleId): ?Vehicle => $vehicles->get($vehicleId))
            ->filter(fn(?Vehicle $vehicle): bool => $vehicle !== null && $vehicle->assigned_driver_id === null)
            ->values();

        if ($missingDriverVehicles->isNotEmpty()) {
            $labels = $missingDriverVehicles
                ->map(fn(Vehicle $vehicle): string => trim(($vehicle->vehicle_no ?? 'Vehicle ' . $vehicle->id) . ' ' . ($vehicle->plate_no ? '(' . $vehicle->plate_no . ')' : '')))
                ->implode(', ');

            throw ValidationException::withMessages([
                'vehicleIds' => ['These vehicles do not have an assigned driver and cannot be allocated now: ' . $labels],
            ]);
        }

        $blockedVehicleIds = SafariAllocation::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('status', '!=', 'Cancelled')
            ->where(function ($query) use ($proformaInvoice): void {
                $query->whereNull('proforma_invoice_id')
                    ->orWhere('proforma_invoice_id', '!=', $proformaInvoice->id);
            })
            ->whereHas('lead', function ($query) use ($lead): void {
                $query->whereDate('start_date', '<=', $lead->end_date)
                    ->whereDate('end_date', '>=', $lead->start_date);
            })
            ->pluck('vehicle_id')
            ->unique()
            ->values();

        if ($blockedVehicleIds->isNotEmpty()) {
            $labels = $blockedVehicleIds
                ->map(fn(int $vehicleId): string => $this->vehicleLabel($vehicles->get($vehicleId), $vehicleId))
                ->implode(', ');

            throw ValidationException::withMessages([
                'vehicleIds' => ['These vehicles are not available for the selected safari dates: ' . $labels],
            ]);
        }

        $allocationsCreated = 0;
        $jobCardsCreated = 0;

        foreach ($vehicleIds as $vehicleId) {
            /** @var Vehicle|null $vehicle */
            $vehicle = $vehicles->get($vehicleId);
            if ($vehicle === null) {
                continue;
            }

            $allocation = SafariAllocation::query()->firstOrNew([
                'lead_id' => $lead->id,
                'proforma_invoice_id' => $proformaInvoice->id,
                'vehicle_id' => $vehicle->id,
            ]);

            if (! $allocation->exists) {
                $allocationsCreated++;
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

        return [
            'mode' => 'now',
            'vehiclesRequested' => count($vehicleIds),
            'allocationsCreated' => $allocationsCreated,
            'jobCardsCreated' => $jobCardsCreated,
        ];
    }

    private function vehicleLabel(?Vehicle $vehicle, int $vehicleId): string
    {
        if ($vehicle === null) {
            return 'Vehicle ' . $vehicleId;
        }

        $label = $vehicle->vehicle_no ?: 'Vehicle ' . $vehicleId;

        return $vehicle->plate_no
            ? $label . ' (' . $vehicle->plate_no . ')'
            : $label;
    }

    public function pdf(ProformaInvoice $proformaInvoice): Response
    {
        $proformaInvoice->load('lineItems');

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

        $filename = 'proforma-invoice-' . $proformaInvoice->id . '.pdf';

        return $pdf->download($filename);
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
        return [
            'id' => $proformaInvoice->id,
            'quotationId' => $proformaInvoice->quotation_id,
            'leadId' => $proformaInvoice->lead_id,
            'client' => $proformaInvoice->client,
            'attention' => $proformaInvoice->attention,
            'quoteDate' => optional($proformaInvoice->quote_date)->format('Y-m-d'),
            'notes' => $proformaInvoice->notes,
            'daySections' => $proformaInvoice->day_sections ?? [],
            'lineItems' => $proformaInvoice->lineItems->map(fn($item): array => [
                'id' => $item->id,
                'dayTitle' => $item->day_title,
                'dayDescription' => $item->day_description,
                'item' => $item->item,
                'description' => $item->description,
                'unit' => $item->unit,
                'qty' => (float) $item->qty,
                'rate' => (float) $item->rate,
                'total' => (float) $item->total,
            ])->values(),
            'subtotal' => (float) $proformaInvoice->subtotal,
            'tax' => (float) $proformaInvoice->tax,
            'total' => (float) $proformaInvoice->total,
            'status' => $proformaInvoice->status,
            'createdAt' => $proformaInvoice->created_at?->toISOString(),
            'updatedAt' => $proformaInvoice->updated_at?->toISOString(),
        ];
    }
}
