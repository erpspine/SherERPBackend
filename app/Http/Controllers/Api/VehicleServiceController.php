<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = VehicleService::query()
            ->with('vehicle')
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Vehicle services fetched successfully.',
            'vehicleServices' => $services->map(fn(VehicleService $service): array => $this->transformVehicleService($service))->values(),
        ]);
    }

    public function show(VehicleService $vehicleService): JsonResponse
    {
        $vehicleService->loadMissing('vehicle');

        return response()->json([
            'message' => 'Vehicle service fetched successfully.',
            'vehicleService' => $this->transformVehicleService($vehicleService),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicleId' => ['required', 'integer', 'exists:vehicles,id'],
            'serviceCenter' => ['nullable', 'string', 'max:255'],
            'serviceType' => ['nullable', 'string', 'max:255'],
            'serviceDate' => ['required_without:serviceDateOut', 'date'],
            'serviceDateOut' => ['nullable', 'date'],
            'serviceDateIn' => ['nullable', 'date'],
            'partsReplaced' => ['nullable', 'string'],
            'odometerOut' => ['nullable', 'integer', 'min:0'],
            'odometerIn' => ['nullable', 'integer', 'min:0'],
            'fuelOut' => ['nullable', 'integer', 'min:0', 'max:100'],
            'fuelIn' => ['nullable', 'integer', 'min:0', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['In Service', 'Returned', 'Cancelled'])],
        ]);

        $status = $validated['status'] ?? 'In Service';

        $payload = $this->mapRequestToDb($validated);
        if (! array_key_exists('service_date', $payload) && array_key_exists('service_date_out', $payload)) {
            $payload['service_date'] = $payload['service_date_out'];
        }
        $payload['status'] = $status;
        $payload['service_date_out'] = $payload['service_date'] ?? $payload['service_date_out'] ?? null;

        $service = VehicleService::create($payload);
        $service->loadMissing('vehicle');

        return response()->json([
            'message' => 'Vehicle service created successfully.',
            'vehicleService' => $this->transformVehicleService($service),
        ], 201);
    }

    public function update(Request $request, VehicleService $vehicleService): JsonResponse
    {
        $validated = $request->validate([
            'vehicleId' => ['sometimes', 'integer', 'exists:vehicles,id'],
            'serviceCenter' => ['sometimes', 'nullable', 'string', 'max:255'],
            'serviceType' => ['sometimes', 'nullable', 'string', 'max:255'],
            'serviceDate' => ['sometimes', 'date'],
            'serviceDateOut' => ['sometimes', 'nullable', 'date'],
            'serviceDateIn' => ['sometimes', 'nullable', 'date'],
            'partsReplaced' => ['sometimes', 'nullable', 'string'],
            'odometerOut' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'odometerIn' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'fuelOut' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'fuelIn' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in(['In Service', 'Returned', 'Cancelled'])],
        ]);

        $payload = $this->mapRequestToDb($validated);
        if (array_key_exists('service_date', $payload)) {
            $payload['service_date_out'] = $payload['service_date'];
        }

        $vehicleService->update($payload);
        $vehicleService->refresh();
        $vehicleService->loadMissing('vehicle');

        return response()->json([
            'message' => 'Vehicle service updated successfully.',
            'vehicleService' => $this->transformVehicleService($vehicleService),
        ]);
    }

    public function destroy(VehicleService $vehicleService): JsonResponse
    {
        $vehicleService->delete();

        return response()->json([
            'message' => 'Vehicle service deleted successfully.',
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function mapRequestToDb(array $validated): array
    {
        $map = [
            'vehicleId' => 'vehicle_id',
            'serviceCenter' => 'service_center',
            'serviceType' => 'service_type',
            'serviceDate' => 'service_date',
            'serviceDateOut' => 'service_date_out',
            'serviceDateIn' => 'service_date_in',
            'partsReplaced' => 'parts_replaced',
            'odometerOut' => 'odometer_out',
            'odometerIn' => 'odometer_in',
            'fuelOut' => 'fuel_out',
            'fuelIn' => 'fuel_in',
            'cost' => 'cost',
            'notes' => 'notes',
            'status' => 'status',
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
    private function transformVehicleService(VehicleService $service): array
    {
        return [
            'id' => $service->id,
            'vehicleId' => $service->vehicle_id,
            'vehicleNo' => $service->vehicle?->vehicle_no,
            'plateNo' => $service->vehicle?->plate_no,
            'serviceCenter' => $service->service_center,
            'serviceType' => $service->service_type,
            'serviceDate' => optional($service->service_date ?? $service->service_date_out)->format('Y-m-d'),
            'serviceDateOut' => optional($service->service_date_out)->format('Y-m-d'),
            'serviceDateIn' => optional($service->service_date_in)->format('Y-m-d'),
            'partsReplaced' => $service->parts_replaced,
            'odometerOut' => $service->odometer_out,
            'odometerIn' => $service->odometer_in,
            'fuelOut' => $service->fuel_out,
            'fuelIn' => $service->fuel_in,
            'cost' => $service->cost,
            'notes' => $service->notes,
            'status' => $service->status,
            'createdAt' => $service->created_at?->toISOString(),
            'updatedAt' => $service->updated_at?->toISOString(),
        ];
    }
}
