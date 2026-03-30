<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index(): JsonResponse
    {
        $vehicles = Vehicle::query()->latest('id')->get();

        return response()->json([
            'message' => 'Vehicles fetched successfully.',
            'vehicles' => $vehicles->map(fn(Vehicle $vehicle): array => $this->transformVehicle($vehicle))->values(),
        ]);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json([
            'message' => 'Vehicle fetched successfully.',
            'vehicle' => $this->transformVehicle($vehicle),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicleNo' => ['required', 'string', 'max:50', 'unique:vehicles,vehicle_no'],
            'plateNo' => ['required', 'string', 'max:50', 'unique:vehicles,plate_no'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:120'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'seats' => ['required', 'integer', 'min:1', 'max:100'],
            'chassis' => ['required', 'string', 'max:100', 'unique:vehicles,chassis'],
            'status' => ['sometimes', Rule::in(['Available', 'On Lease', 'Maintenance', 'Retired'])],
        ]);

        $vehicle = Vehicle::create($this->mapRequestToDb($validated));

        return response()->json([
            'message' => 'Vehicle created successfully.',
            'vehicle' => $this->transformVehicle($vehicle),
        ], 201);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'vehicleNo' => ['sometimes', 'string', 'max:50', Rule::unique('vehicles', 'vehicle_no')->ignore($vehicle->id)],
            'plateNo' => ['sometimes', 'string', 'max:50', Rule::unique('vehicles', 'plate_no')->ignore($vehicle->id)],
            'make' => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:120'],
            'year' => ['sometimes', 'integer', 'min:1900', 'max:2100'],
            'seats' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'chassis' => ['sometimes', 'string', 'max:100', Rule::unique('vehicles', 'chassis')->ignore($vehicle->id)],
            'status' => ['sometimes', Rule::in(['Available', 'On Lease', 'Maintenance', 'Retired'])],
        ]);

        $vehicle->update($this->mapRequestToDb($validated));
        $vehicle->refresh();

        return response()->json([
            'message' => 'Vehicle updated successfully.',
            'vehicle' => $this->transformVehicle($vehicle),
        ]);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json([
            'message' => 'Vehicle deleted successfully.',
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function mapRequestToDb(array $validated): array
    {
        $map = [
            'vehicleNo' => 'vehicle_no',
            'plateNo' => 'plate_no',
            'make' => 'make',
            'model' => 'model',
            'year' => 'year',
            'seats' => 'seats',
            'chassis' => 'chassis',
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
    private function transformVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'vehicleNo' => $vehicle->vehicle_no,
            'plateNo' => $vehicle->plate_no,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'seats' => $vehicle->seats,
            'chassis' => $vehicle->chassis,
            'status' => $vehicle->status,
            'createdAt' => $vehicle->created_at?->toISOString(),
            'updatedAt' => $vehicle->updated_at?->toISOString(),
        ];
    }
}
