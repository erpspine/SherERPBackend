<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SafariAllocation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SafariAllocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SafariAllocation::class);

        $query = SafariAllocation::query()
            ->with(['lead', 'proformaInvoice', 'vehicle', 'driver'])
            ->latest('id');

        if ($request->user()?->hasRole('Driver')) {
            $query->where('driver_id', $request->user()->id);
        }

        $allocations = $query->get();

        return response()->json([
            'message' => 'Safari allocations fetched successfully.',
            'allocations' => $allocations->map(fn(SafariAllocation $allocation): array => $this->transform($allocation))->values(),
        ]);
    }

    public function show(SafariAllocation $safariAllocation): JsonResponse
    {
        $this->authorize('view', $safariAllocation);

        $safariAllocation->load(['lead', 'proformaInvoice', 'vehicle', 'driver']);

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
            'driverId' => ['nullable', 'integer', 'exists:users,id'],
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

        $this->ensureVehicleAvailability(
            (int) $validated['vehicleId'],
            $startDate,
            $endDate
        );

        $this->ensureLeadDateRangeAvailability(
            (int) $validated['leadId'],
            $startDate,
            $endDate
        );

        $allocation = SafariAllocation::create([
            'lead_id' => $validated['leadId'],
            'proforma_invoice_id' => $validated['proformaInvoiceId'] ?? null,
            'vehicle_id' => $validated['vehicleId'],
            'driver_id' => $validated['driverId'] ?? null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'] ?? 'Assigned',
            'notes' => $validated['notes'] ?? null,
        ]);

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
            'driverId' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
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
        $this->ensureVehicleAvailability(
            $nextVehicleId,
            $startDate,
            $endDate,
            $safariAllocation->id
        );

        $this->ensureLeadDateRangeAvailability(
            $nextLeadId,
            $startDate,
            $endDate,
            $safariAllocation->id
        );

        $safariAllocation->update([
            'lead_id' => $nextLeadId,
            'proforma_invoice_id' => array_key_exists('proformaInvoiceId', $validated) ? $validated['proformaInvoiceId'] : $safariAllocation->proforma_invoice_id,
            'vehicle_id' => $nextVehicleId,
            'driver_id' => array_key_exists('driverId', $validated) ? $validated['driverId'] : $safariAllocation->driver_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'] ?? $safariAllocation->status,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $safariAllocation->notes,
        ]);

        $safariAllocation->load(['lead', 'proformaInvoice', 'vehicle', 'driver']);

        return response()->json([
            'message' => 'Safari allocation updated successfully.',
            'allocation' => $this->transform($safariAllocation),
        ]);
    }

    public function destroy(SafariAllocation $safariAllocation): JsonResponse
    {
        $this->authorize('delete', $safariAllocation);

        $safariAllocation->delete();

        return response()->json([
            'message' => 'Safari allocation deleted successfully.',
        ]);
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

        return [
            'id' => $allocation->id,
            'leadId' => $allocation->lead_id,
            'proformaInvoiceId' => $allocation->proforma_invoice_id,
            'vehicleId' => $allocation->vehicle_id,
            'driverId' => $allocation->driver_id,
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
                'agentContact' => $allocation->lead->agent_contact,
                'startDate' => optional($allocation->lead->start_date)->format('Y-m-d'),
                'endDate' => optional($allocation->lead->end_date)->format('Y-m-d'),
                'routeParks' => $allocation->lead->route_parks,
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
            ] : null,
        ];
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

    private function ensureVehicleAvailability(int $vehicleId, string $startDate, string $endDate, ?int $ignoreAllocationId = null): void
    {
        $query = SafariAllocation::query()
            ->where('vehicle_id', $vehicleId)
            ->whereNotIn('status', ['Completed', 'Cancelled'])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        if ($ignoreAllocationId) {
            $query->whereKeyNot($ignoreAllocationId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'vehicleId' => ['This vehicle is already allocated on the selected date range.'],
            ]);
        }
    }

    private function ensureLeadDateRangeAvailability(int $leadId, string $startDate, string $endDate, ?int $ignoreAllocationId = null): void
    {
        $query = SafariAllocation::query()
            ->where('lead_id', $leadId)
            ->whereNotIn('status', ['Completed', 'Cancelled'])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->where(function ($innerQuery) use ($startDate, $endDate) {
                $innerQuery
                    ->whereDate('start_date', '!=', $startDate)
                    ->orWhereDate('end_date', '!=', $endDate);
            });

        if ($ignoreAllocationId) {
            $query->whereKeyNot($ignoreAllocationId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'startDate' => ['Date ranges for this safari cannot overlap.'],
            ]);
        }
    }
}
