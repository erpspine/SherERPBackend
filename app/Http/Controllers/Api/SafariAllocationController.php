<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SafariAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'driverId' => ['required', 'integer', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['Assigned', 'Pending', 'In Progress', 'Completed', 'Cancelled'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $allocation = SafariAllocation::create([
            'lead_id' => $validated['leadId'],
            'proforma_invoice_id' => $validated['proformaInvoiceId'] ?? null,
            'vehicle_id' => $validated['vehicleId'],
            'driver_id' => $validated['driverId'],
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
            'driverId' => ['sometimes', 'integer', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['Assigned', 'Pending', 'In Progress', 'Completed', 'Cancelled'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $safariAllocation->update([
            'lead_id' => $validated['leadId'] ?? $safariAllocation->lead_id,
            'proforma_invoice_id' => array_key_exists('proformaInvoiceId', $validated) ? $validated['proformaInvoiceId'] : $safariAllocation->proforma_invoice_id,
            'vehicle_id' => $validated['vehicleId'] ?? $safariAllocation->vehicle_id,
            'driver_id' => $validated['driverId'] ?? $safariAllocation->driver_id,
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
        return [
            'id' => $allocation->id,
            'leadId' => $allocation->lead_id,
            'proformaInvoiceId' => $allocation->proforma_invoice_id,
            'vehicleId' => $allocation->vehicle_id,
            'driverId' => $allocation->driver_id,
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
                'piNo' => 'PI-' . now()->format('Y') . '-' . str_pad((string) $allocation->proformaInvoice->id, 4, '0', STR_PAD_LEFT),
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
}
