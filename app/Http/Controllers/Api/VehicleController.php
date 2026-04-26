<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $this->normalizeMileageInput($request);

        $validated = $request->validate([
            'vehicleNo' => ['required', 'string', 'max:50', 'unique:vehicles,vehicle_no'],
            'plateNo' => ['required', 'string', 'max:50', 'unique:vehicles,plate_no'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:120'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'seats' => ['required', 'integer', 'min:1', 'max:100'],
            'mileage' => ['required', 'integer', 'min:0'],
            'chassis' => ['required', 'string', 'max:100', 'unique:vehicles,chassis'],
            'specs' => ['nullable', 'string'],
            'photo' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value instanceof UploadedFile) {
                    return;
                }

                if (is_string($value) && str_starts_with($value, 'data:image/')) {
                    return;
                }

                $fail('The ' . $attribute . ' field must be an uploaded image file or a base64 image data URI.');
            }],
            'vehiclePhoto' => ['nullable', 'file', 'image', 'max:5120'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'status' => ['sometimes', Rule::in(['Available', 'On Lease', 'Maintenance', 'Retired'])],
        ]);

        $uploadedPhoto = $this->resolvePhotoUpload($request);
        if ($uploadedPhoto !== null) {
            $validated['photo'] = $uploadedPhoto->store('vehicles', 'public');
        } elseif (is_string($request->input('photo')) && str_starts_with($request->input('photo'), 'data:image/')) {
            $storedPhoto = $this->storeBase64VehiclePhoto($request->input('photo'));
            if ($storedPhoto !== null) {
                $validated['photo'] = $storedPhoto;
            }
        }

        $vehicle = Vehicle::create($this->mapRequestToDb($validated));

        return response()->json([
            'message' => 'Vehicle created successfully.',
            'vehicle' => $this->transformVehicle($vehicle),
        ], 201);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->normalizeMileageInput($request);



        $validated = $request->validate([
            'vehicleNo' => ['sometimes', 'string', 'max:50', Rule::unique('vehicles', 'vehicle_no')->ignore($vehicle->id)],
            'plateNo' => ['sometimes', 'string', 'max:50', Rule::unique('vehicles', 'plate_no')->ignore($vehicle->id)],
            'make' => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:120'],
            'year' => ['sometimes', 'integer', 'min:1900', 'max:2100'],
            'seats' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'mileage' => ['sometimes', 'integer', 'min:0'],
            'chassis' => ['sometimes', 'string', 'max:100', Rule::unique('vehicles', 'chassis')->ignore($vehicle->id)],
            'specs' => ['nullable', 'string'],
            'photo' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value instanceof UploadedFile) {
                    return;
                }

                if (is_string($value) && str_starts_with($value, 'data:image/')) {
                    return;
                }

                $fail('The ' . $attribute . ' field must be an uploaded image file or a base64 image data URI.');
            }],
            'vehiclePhoto' => ['nullable', 'file', 'image', 'max:5120'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'status' => ['sometimes', Rule::in(['Available', 'On Lease', 'Maintenance', 'Retired'])],
        ]);

        $uploadedPhoto = $this->resolvePhotoUpload($request);
        if ($uploadedPhoto !== null) {
            if (is_string($vehicle->photo) && $vehicle->photo !== '' && Storage::disk('public')->exists($vehicle->photo)) {
                Storage::disk('public')->delete($vehicle->photo);
            }
            $validated['photo'] = $uploadedPhoto->store('vehicles', 'public');
        } elseif (is_string($request->input('photo')) && str_starts_with($request->input('photo'), 'data:image/')) {
            $storedPhoto = $this->storeBase64VehiclePhoto($request->input('photo'));
            if ($storedPhoto !== null) {
                if (is_string($vehicle->photo) && $vehicle->photo !== '' && Storage::disk('public')->exists($vehicle->photo)) {
                    Storage::disk('public')->delete($vehicle->photo);
                }
                $validated['photo'] = $storedPhoto;
            }
        }

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
            'mileage' => 'mileage',
            'chassis' => 'chassis',
            'specs' => 'specs',
            'photo' => 'photo',
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

    private function normalizeMileageInput(Request $request): void
    {
        $rawMileage = $request->input('mileage');

        if ($rawMileage === null) {
            $rawMileage = $request->input('milage', $request->input('currentMileage', $request->input('odometer')));
        }

        if ($rawMileage === null) {
            return;
        }

        if (is_string($rawMileage)) {
            $rawMileage = trim(str_replace([',', ' '], '', $rawMileage));
        }

        if ($rawMileage === '') {
            return;
        }

        $request->merge([
            'mileage' => $rawMileage,
        ]);
    }

    private function resolvePhotoUpload(Request $request): ?UploadedFile
    {
        foreach (['photo', 'vehiclePhoto', 'image'] as $key) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                if ($file instanceof UploadedFile) {
                    return $file;
                }
            }
        }

        return null;
    }

    private function storeBase64VehiclePhoto(string $dataUri): ?string
    {
        if (! preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/', $dataUri, $matches)) {
            return null;
        }

        $base64 = substr($dataUri, strpos($dataUri, ',') + 1);
        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            return null;
        }

        $extension = strtolower($matches[1]);
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $filename = 'vehicles/' . uniqid('vehicle_', true) . '.' . $extension;
        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'vehicle_no' => $vehicle->vehicle_no,
            'car_no' => $vehicle->vehicle_no,
            'plate_no' => $vehicle->plate_no,
            'vehicleNo' => $vehicle->vehicle_no,
            'plateNo' => $vehicle->plate_no,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'seats' => $vehicle->seats,
            'mileage' => $vehicle->mileage,
            'chassis' => $vehicle->chassis,
            'specs' => $vehicle->specs,
            'photo' => $vehicle->photo,
            'photoUrl' => is_string($vehicle->photo) && $vehicle->photo !== ''
                ? Storage::url($vehicle->photo)
                : null,
            'status' => $vehicle->status,
            'createdAt' => $vehicle->created_at?->toISOString(),
            'updatedAt' => $vehicle->updated_at?->toISOString(),
        ];
    }
}
