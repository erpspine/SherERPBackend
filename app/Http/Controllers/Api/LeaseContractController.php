<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\LeaseContract;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaseContractController extends Controller
{
    private const LEASE_TYPES = ['Daily Lease', 'Short-Term Lease', 'Long-Term Lease'];

    private const STATUSES = ['Active', 'Completed', 'Cancelled'];

    private const ONE_MONTH_DAYS = 30;

    private const ONE_YEAR_DAYS = 365;

    public function index(): JsonResponse
    {
        $this->autoCompleteExpired();

        $contracts = LeaseContract::query()
            ->with('vehicles')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'Lease contracts fetched successfully.',
            'contracts' => $contracts->map(fn(LeaseContract $contract): array => $this->transform($contract))->values(),
        ]);
    }

    public function show(LeaseContract $leaseContract): JsonResponse
    {
        $leaseContract->load('vehicles');

        return response()->json([
            'message' => 'Lease contract fetched successfully.',
            'contract' => $this->transform($leaseContract),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $contract = DB::transaction(function () use ($validated): LeaseContract {
            $duration = $this->calcDays($validated['startDate'], $validated['endDate']);
            $leaseType = $this->resolveLeaseType($duration);
            $vehicleIds = $validated['vehicleIds'] ?? [];

            $contract = LeaseContract::create([
                'client_name' => $validated['clientName'],
                'lease_type' => $leaseType,
                'start_date' => $validated['startDate'],
                'end_date' => $validated['endDate'],
                'duration_days' => $duration,
                'monthly_rate' => $validated['monthlyRate'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'Active',
            ]);

            $contract->vehicles()->sync($vehicleIds);

            if ($vehicleIds !== []) {
                Vehicle::query()->whereIn('id', $vehicleIds)->update([
                    'status' => 'On Lease',
                    'lease_type' => $leaseType,
                    'lease_start_date' => $validated['startDate'],
                    'lease_end_date' => $validated['endDate'],
                    'lease_client_name' => $validated['clientName'],
                    'lease_monthly_rate' => $validated['monthlyRate'] ?? null,
                    'lease_notes' => $validated['notes'] ?? null,
                ]);
            }

            return $contract;
        });

        $contract->load('vehicles');

        JobCard::ensureForLeaseContract($contract);

        return response()->json([
            'message' => 'Lease contract created successfully.',
            'contract' => $this->transform($contract),
        ], 201);
    }

    public function update(Request $request, LeaseContract $leaseContract): JsonResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($validated, $leaseContract): void {
            $duration = $this->calcDays($validated['startDate'], $validated['endDate']);
            $leaseType = $this->resolveLeaseType($duration);
            $vehicleIds = $validated['vehicleIds'] ?? [];

            $oldVehicleIds = $leaseContract->vehicles()->pluck('vehicles.id')->all();

            $leaseContract->update([
                'client_name' => $validated['clientName'],
                'lease_type' => $leaseType,
                'start_date' => $validated['startDate'],
                'end_date' => $validated['endDate'],
                'duration_days' => $duration,
                'monthly_rate' => $validated['monthlyRate'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? $leaseContract->status,
            ]);

            $leaseContract->vehicles()->sync($vehicleIds);

            $removed = array_values(array_diff($oldVehicleIds, $vehicleIds));
            if ($removed !== []) {
                Vehicle::query()->whereIn('id', $removed)->update([
                    'status' => 'Available',
                    'lease_type' => null,
                    'lease_start_date' => null,
                    'lease_end_date' => null,
                    'lease_client_name' => null,
                    'lease_monthly_rate' => null,
                    'lease_notes' => null,
                ]);
            }

            if ($leaseContract->status === 'Active' && $vehicleIds !== []) {
                Vehicle::query()->whereIn('id', $vehicleIds)->update([
                    'status' => 'On Lease',
                    'lease_type' => $leaseType,
                    'lease_start_date' => $validated['startDate'],
                    'lease_end_date' => $validated['endDate'],
                    'lease_client_name' => $validated['clientName'],
                    'lease_monthly_rate' => $validated['monthlyRate'] ?? null,
                    'lease_notes' => $validated['notes'] ?? null,
                ]);
            }
        });

        $leaseContract->load('vehicles');

        if ($leaseContract->status === 'Active') {
            JobCard::ensureForLeaseContract($leaseContract);
        }

        return response()->json([
            'message' => 'Lease contract updated successfully.',
            'contract' => $this->transform($leaseContract),
        ]);
    }

    public function destroy(LeaseContract $leaseContract): JsonResponse
    {
        DB::transaction(function () use ($leaseContract): void {
            $vehicleIds = $leaseContract->vehicles()->pluck('vehicles.id')->all();

            if ($vehicleIds !== []) {
                Vehicle::query()->whereIn('id', $vehicleIds)->update([
                    'status' => 'Available',
                    'lease_type' => null,
                    'lease_start_date' => null,
                    'lease_end_date' => null,
                    'lease_client_name' => null,
                    'lease_monthly_rate' => null,
                    'lease_notes' => null,
                ]);
            }

            $leaseContract->vehicles()->detach();
            $leaseContract->delete();
        });

        return response()->json([
            'message' => 'Lease contract deleted successfully.',
        ]);
    }

    /**
     * Auto-mark contracts whose end_date has passed as Completed and revert vehicle status.
     */
    private function autoCompleteExpired(): void
    {
        $today = Carbon::today()->toDateString();

        $expired = LeaseContract::query()
            ->where('status', 'Active')
            ->whereDate('end_date', '<', $today)
            ->with('vehicles:id')
            ->get();

        if ($expired->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($expired): void {
            foreach ($expired as $contract) {
                $contract->update(['status' => 'Completed']);
                $vehicleIds = $contract->vehicles->pluck('id')->all();
                if ($vehicleIds === []) {
                    continue;
                }
                Vehicle::query()->whereIn('id', $vehicleIds)->update([
                    'status' => 'Available',
                    'lease_type' => null,
                    'lease_start_date' => null,
                    'lease_end_date' => null,
                    'lease_client_name' => null,
                    'lease_monthly_rate' => null,
                    'lease_notes' => null,
                ]);
            }
        });
    }

    /**
     * @return array{clientName: string, startDate: string, endDate: string, vehicleIds: array<int, int>, monthlyRate: ?string, notes: ?string, status: ?string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'clientName' => ['required', 'string', 'max:150'],
            'startDate' => ['required', 'date_format:Y-m-d'],
            'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'vehicleIds' => ['nullable', 'array'],
            'vehicleIds.*' => ['integer', 'exists:vehicles,id'],
            'monthlyRate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ]);
    }

    private function calcDays(string $start, string $end): int
    {
        return max(0, Carbon::parse($end)->diffInDays(Carbon::parse($start)));
    }

    private function resolveLeaseType(int $days): string
    {
        if ($days < self::ONE_MONTH_DAYS) {
            return 'Daily Lease';
        }
        if ($days > self::ONE_YEAR_DAYS) {
            return 'Long-Term Lease';
        }
        return 'Short-Term Lease';
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(LeaseContract $contract): array
    {
        return [
            'id' => $contract->id,
            'clientName' => $contract->client_name,
            'leaseType' => $contract->lease_type,
            'startDate' => optional($contract->start_date)->toDateString(),
            'endDate' => optional($contract->end_date)->toDateString(),
            'durationDays' => $contract->duration_days,
            'monthlyRate' => $contract->monthly_rate,
            'notes' => $contract->notes,
            'status' => $contract->status,
            'vehicleIds' => $contract->vehicles->pluck('id')->map(fn($id): int => (int) $id)->all(),
            'vehicles' => $contract->vehicles->map(fn(Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicle_no,
                'plateNo' => $vehicle->plate_no,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'status' => $vehicle->status,
            ])->values(),
            'createdAt' => optional($contract->created_at)->toISOString(),
            'updatedAt' => optional($contract->updated_at)->toISOString(),
        ];
    }
}
