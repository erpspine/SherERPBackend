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
            'serviceDateOut' => ['required', 'date'],
            'serviceDateIn' => ['nullable', 'date'],
            'odometerOut' => ['required', 'integer', 'min:0'],
            'odometerIn' => ['nullable', 'integer', 'min:0'],
            'fuelOut' => ['required', 'integer', 'min:0', 'max:100'],
            'fuelIn' => ['nullable', 'integer', 'min:0', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['In Service', 'Returned'])],
        ]);

        $status = $validated['status'] ?? 'In Service';

        if ($status === 'Returned') {
            $request->validate([
                'serviceDateIn' => ['required', 'date', 'after_or_equal:serviceDateOut'],
                'odometerIn' => ['required', 'integer', 'min:0'],
                'fuelIn' => ['required', 'integer', 'min:0', 'max:100'],
            ]);
        }

        if (isset($validated['serviceDateIn']) && $validated['serviceDateIn'] < $validated['serviceDateOut']) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['serviceDateIn' => ['The serviceDateIn must be a date after or equal to serviceDateOut.']],
            ], 422);
        }

        $payload = $this->mapRequestToDb($validated);
        $payload['status'] = $status;

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
            'serviceDateOut' => ['sometimes', 'date'],
            'serviceDateIn' => ['sometimes', 'nullable', 'date'],
            'odometerOut' => ['sometimes', 'integer', 'min:0'],
            'odometerIn' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'fuelOut' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'fuelIn' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in(['In Service', 'Returned'])],
        ]);

        $status = $validated['status'] ?? $vehicleService->status;

        $serviceDateOut = $validated['serviceDateOut'] ?? optional($vehicleService->service_date_out)->format('Y-m-d');
        $serviceDateIn = $validated['serviceDateIn'] ?? optional($vehicleService->service_date_in)->format('Y-m-d');

        if ($serviceDateIn && $serviceDateOut && $serviceDateIn < $serviceDateOut) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['serviceDateIn' => ['The serviceDateIn must be a date after or equal to serviceDateOut.']],
            ], 422);
        }

        if ($status === 'Returned') {
            $odometerIn = $validated['odometerIn'] ?? $vehicleService->odometer_in;
            $fuelIn = $validated['fuelIn'] ?? $vehicleService->fuel_in;

            $errors = [];
            if (!$serviceDateIn) {
                $errors['serviceDateIn'] = ['The serviceDateIn field is required when status is Returned.'];
            }
            if ($odometerIn === null) {
                $errors['odometerIn'] = ['The odometerIn field is required when status is Returned.'];
            }
            if ($fuelIn === null) {
                $errors['fuelIn'] = ['The fuelIn field is required when status is Returned.'];
            }

            if (!empty($errors)) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $errors,
                ], 422);
            }
        }

        $vehicleService->update($this->mapRequestToDb($validated));
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
            'serviceDateOut' => 'service_date_out',
            'serviceDateIn' => 'service_date_in',
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
            'serviceDateOut' => optional($service->service_date_out)->format('Y-m-d'),
            'serviceDateIn' => optional($service->service_date_in)->format('Y-m-d'),
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
