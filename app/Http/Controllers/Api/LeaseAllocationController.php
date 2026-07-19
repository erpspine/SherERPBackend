<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaseAllocation;
use App\Models\LeaseContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaseAllocationController extends Controller
{
    private const STATUSES = ['Scheduled', 'In Progress', 'Completed', 'Cancelled'];

    public function index(Request $request): JsonResponse
    {
        $allocations = $this->filteredQuery($request)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'Lease allocations fetched successfully.',
            'allocations' => $allocations->map(fn(LeaseAllocation $a): array => $this->transform($a))->values(),
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $allocations = LeaseAllocation::query()
            ->with(['leaseContract', 'vehicle', 'driver:id,name,email'])
            ->withCount('odometerLogs')
            ->withMax('odometerLogs', 'recorded_at')
            ->where('driver_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'Driver lease allocations fetched successfully.',
            'allocations' => $allocations
                ->map(fn(LeaseAllocation $allocation): array => $this->transform($allocation))
                ->values(),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $rows = $this->filteredQuery($request)
            ->orderByDesc('id')
            ->get()
            ->map(fn(LeaseAllocation $allocation): array => $this->transform($allocation))
            ->values();

        $filename = 'lease-allocations-report.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            // UTF-8 BOM for better Excel compatibility.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Client',
                'Lease Type',
                'Group Name',
                'Vehicle No',
                'Plate No',
                'Driver',
                'Start Date',
                'End Date',
                'Status',
                'Itinerary',
                'Fuel Notes',
                'Notes',
            ]);

            foreach ($rows as $row) {
                $itineraryItems = collect($row['itineraryItems'] ?? [])
                    ->map(function ($item): string {
                        if (! is_array($item)) {
                            return '';
                        }

                        $date = trim((string) ($item['date'] ?? ''));
                        $details = trim((string) ($item['details'] ?? ''));

                        return trim(($date !== '' ? ($date . ': ') : '') . $details);
                    })
                    ->filter(fn(string $line): bool => $line !== '')
                    ->implode(' | ');

                fputcsv($output, [
                    (string) ($row['contract']['clientName'] ?? '-'),
                    (string) ($row['contract']['leaseType'] ?? '-'),
                    (string) ($row['groupName'] ?? ''),
                    (string) ($row['vehicle']['vehicleNo'] ?? '-'),
                    (string) ($row['vehicle']['plateNo'] ?? '-'),
                    (string) ($row['driver']['name'] ?? ''),
                    (string) ($row['startDate'] ?? ''),
                    (string) ($row['endDate'] ?? ''),
                    (string) ($row['status'] ?? ''),
                    (string) ($row['itinerary'] ?? ($itineraryItems !== '' ? $itineraryItems : '')),
                    (string) ($row['fuelNotes'] ?? ''),
                    (string) ($row['notes'] ?? ''),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(LeaseAllocation $leaseAllocation): JsonResponse
    {
        $leaseAllocation->load(['leaseContract', 'vehicle', 'driver:id,name,email']);

        return response()->json([
            'message' => 'Lease allocation fetched successfully.',
            'allocation' => $this->transform($leaseAllocation),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $allocation = LeaseAllocation::create([
            'lease_contract_id' => $validated['leaseContractId'],
            'group_name' => $validated['groupName'] ?? null,
            'vehicle_id' => $validated['vehicleId'],
            'driver_id' => $validated['driverId'] ?? null,
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
            'itinerary' => $validated['itinerary'] ?? null,
            'itinerary_items' => $this->normalizeItineraryItems($validated['itineraryItems'] ?? null),
            'fuel_notes' => $validated['fuelNotes'] ?? null,
            'status' => $validated['status'] ?? 'Scheduled',
            'notes' => $validated['notes'] ?? null,
        ]);

        $allocation->load(['leaseContract', 'vehicle', 'driver:id,name,email']);

        return response()->json([
            'message' => 'Lease allocation created successfully.',
            'allocation' => $this->transform($allocation),
        ], 201);
    }

    public function update(Request $request, LeaseAllocation $leaseAllocation): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $leaseAllocation->update([
            'lease_contract_id' => $validated['leaseContractId'],
            'group_name' => $validated['groupName'] ?? null,
            'vehicle_id' => $validated['vehicleId'],
            'driver_id' => $validated['driverId'] ?? null,
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
            'itinerary' => $validated['itinerary'] ?? null,
            'itinerary_items' => $this->normalizeItineraryItems($validated['itineraryItems'] ?? null),
            'fuel_notes' => $validated['fuelNotes'] ?? null,
            'status' => $validated['status'] ?? $leaseAllocation->status,
            'notes' => $validated['notes'] ?? null,
        ]);

        $leaseAllocation->load(['leaseContract', 'vehicle', 'driver:id,name,email']);

        return response()->json([
            'message' => 'Lease allocation updated successfully.',
            'allocation' => $this->transform($leaseAllocation),
        ]);
    }

    public function destroy(LeaseAllocation $leaseAllocation): JsonResponse
    {
        $leaseAllocation->delete();

        return response()->json([
            'message' => 'Lease allocation deleted successfully.',
        ]);
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'leaseContractId' => ['required', 'integer', 'exists:lease_contracts,id'],
            'groupName' => ['nullable', 'string', 'max:150'],
            'vehicleId' => ['required', 'integer', 'exists:vehicles,id'],
            'driverId' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn($query) => $query->where('status', 'Active'))],
            'startDate' => ['required', 'date_format:Y-m-d'],
            'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'itinerary' => ['nullable', 'string', 'max:5000'],
            'itineraryItems' => ['nullable', 'array'],
            'itineraryItems.*.date' => ['nullable', 'string', 'max:100'],
            'itineraryItems.*.details' => ['nullable', 'string', 'max:2000'],
            'fuelNotes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Ensure the chosen vehicle belongs to the chosen lease contract.
        $contract = LeaseContract::query()
            ->with('vehicles:id')
            ->find($validated['leaseContractId']);
        if ($contract === null) {
            throw ValidationException::withMessages([
                'leaseContractId' => 'Lease contract not found.',
            ]);
        }
        $contractVehicleIds = $contract->vehicles->pluck('id')->all();
        if (! in_array((int) $validated['vehicleId'], array_map('intval', $contractVehicleIds), true)) {
            throw ValidationException::withMessages([
                'vehicleId' => 'Selected vehicle is not part of this lease contract.',
            ]);
        }

        // Ensure allocation date range lies inside the contract period.
        $contractStart = optional($contract->start_date)->format('Y-m-d');
        $contractEnd = optional($contract->end_date)->format('Y-m-d');
        if ($contractStart && $validated['startDate'] < $contractStart) {
            throw ValidationException::withMessages([
                'startDate' => 'Start date is before the lease contract start (' . $contractStart . ').',
            ]);
        }
        if ($contractEnd && $validated['endDate'] > $contractEnd) {
            throw ValidationException::withMessages([
                'endDate' => 'End date is after the lease contract end (' . $contractEnd . ').',
            ]);
        }

        return $validated;
    }

    private function filteredQuery(Request $request)
    {
        $query = LeaseAllocation::query()
            ->with(['leaseContract', 'vehicle', 'driver:id,name,email'])
            ->withCount('odometerLogs')
            ->withMax('odometerLogs', 'recorded_at');

        if ($request->filled('leaseContractId')) {
            $query->where('lease_contract_id', (int) $request->input('leaseContractId'));
        }

        if ($request->filled('vehicleId')) {
            $query->where('vehicle_id', (int) $request->input('vehicleId'));
        }

        $status = trim((string) $request->input('status', ''));
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $dateFrom = trim((string) $request->input('dateFrom', ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $query->whereDate('end_date', '>=', $dateFrom);
        }

        $dateTo = trim((string) $request->input('dateTo', ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $query->whereDate('start_date', '<=', $dateTo);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('group_name', 'like', '%' . $search . '%')
                    ->orWhere('itinerary', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('leaseContract', function ($contractQuery) use ($search): void {
                        $contractQuery
                            ->where('client_name', 'like', '%' . $search . '%')
                            ->orWhere('lease_type', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search): void {
                        $vehicleQuery
                            ->where('vehicle_no', 'like', '%' . $search . '%')
                            ->orWhere('plate_no', 'like', '%' . $search . '%')
                            ->orWhere('make', 'like', '%' . $search . '%')
                            ->orWhere('model', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('driver', function ($driverQuery) use ($search): void {
                        $driverQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query;
    }

    private function transform(LeaseAllocation $allocation): array
    {
        return [
            'id' => $allocation->id,
            'assignmentType' => 'long_term_lease',
            'odometer_log_count' => (int) ($allocation->odometer_logs_count ?? 0),
            'odometerLogCount' => (int) ($allocation->odometer_logs_count ?? 0),
            'latestOdometerLogAt' => $allocation->odometer_logs_max_recorded_at,
            'latest_odometer_log_at' => $allocation->odometer_logs_max_recorded_at,
            'leaseContractId' => $allocation->lease_contract_id,
            'groupName' => $allocation->group_name,
            'contract' => $allocation->leaseContract ? [
                'id' => $allocation->leaseContract->id,
                'clientName' => $allocation->leaseContract->client_name,
                'groupName' => $allocation->leaseContract->group_name,
                'leaseType' => $allocation->leaseContract->lease_type,
                'startDate' => optional($allocation->leaseContract->start_date)->format('Y-m-d'),
                'endDate' => optional($allocation->leaseContract->end_date)->format('Y-m-d'),
            ] : null,
            'vehicleId' => $allocation->vehicle_id,
            'vehicle' => $allocation->vehicle ? [
                'id' => $allocation->vehicle->id,
                'vehicleNo' => $allocation->vehicle->vehicle_no,
                'plateNo' => $allocation->vehicle->plate_no,
                'make' => $allocation->vehicle->make,
                'model' => $allocation->vehicle->model,
            ] : null,
            'driverId' => $allocation->driver_id,
            'driver' => $allocation->driver ? [
                'id' => $allocation->driver->id,
                'name' => $allocation->driver->name,
                'email' => $allocation->driver->email,
            ] : null,
            'startDate' => optional($allocation->start_date)->format('Y-m-d'),
            'endDate' => optional($allocation->end_date)->format('Y-m-d'),
            'itinerary' => $allocation->itinerary,
            'itineraryItems' => $this->normalizeItineraryItems($allocation->itinerary_items) ?? [],
            'fuelNotes' => $allocation->fuel_notes,
            'status' => $allocation->status,
            'notes' => $allocation->notes,
            'createdAt' => optional($allocation->created_at)->toIso8601String(),
            'updatedAt' => optional($allocation->updated_at)->toIso8601String(),
        ];
    }

    private function normalizeItineraryItems($items): ?array
    {
        if (! is_array($items)) {
            return null;
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $date = isset($item['date']) ? trim((string) $item['date']) : '';
            $details = isset($item['details']) ? trim((string) $item['details']) : '';
            if ($date === '' && $details === '') {
                continue;
            }
            $normalized[] = [
                'date' => $date,
                'details' => $details,
            ];
        }

        return empty($normalized) ? null : $normalized;
    }
}
