<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\SafariAllocation;
use App\Models\Setting;
use App\Models\Vehicle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class InspectionController extends Controller
{
    private const INSPECTION_TYPES = [
        'pre_departure',
        'post_departure',
    ];

    /**
     * Get all inspections for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $inspections = Inspection::query()
            ->where('user_id', $request->user()->id)
            ->with('items', 'images', 'lead', 'vehicle', 'leaseAllocation.leaseContract')
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Inspections fetched successfully.',
            'inspections' => $inspections->map(fn(Inspection $inspection): array => $this->transformInspection($inspection))->values(),
        ]);
    }

    /**
     * Get single inspection
     */
    public function show(Request $request, Inspection $inspection): JsonResponse
    {
        if ($inspection->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to access this inspection.',
            ], 403);
        }

        $inspection->load('items', 'images', 'lead', 'vehicle');

        return response()->json([
            'message' => 'Inspection fetched successfully.',
            'inspection' => $this->transformInspection($inspection),
        ]);
    }

    /**
     * Submit an inspection with items and images
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(self::INSPECTION_TYPES)],
            'checklistType' => ['required', Rule::in(self::INSPECTION_TYPES)],
            'lead.id' => ['nullable', 'required_without:leaseAllocationId', 'exists:leads,id'],
            'leaseAllocationId' => ['nullable', 'required_without:lead.id', 'exists:lease_allocations,id'],
            'vehicle.id' => ['required', 'exists:vehicles,id'],
            'parking_location' => ['nullable', 'string', 'max:255'],
            'parkingLocation' => ['nullable', 'string', 'max:255'],
            'parked_location' => ['nullable', 'string', 'max:255'],
            'parkedLocation' => ['nullable', 'string', 'max:255'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'odometer_reading' => ['nullable', 'integer', 'min:0'],
            'odometer_out' => ['nullable', 'integer', 'min:0'],
            'odometer_in' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.checklist_id' => ['required', 'integer'],
            'items.*.checklist_title' => ['required', 'string'],
            'items.*.name' => ['required', 'string'],
            'items.*.text' => ['required', 'string'],
            'items.*.status' => ['required', Rule::in(['OK', 'NOK'])],
            'items.*.issue' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
        ]);

        $existingInspection = $this->findDuplicateInspection(
            leadId: isset($validated['lead']['id']) ? (int) $validated['lead']['id'] : null,
            leaseAllocationId: isset($validated['leaseAllocationId']) ? (int) $validated['leaseAllocationId'] : null,
            vehicleId: (int) $validated['vehicle']['id'],
            type: (string) $validated['type'],
        );

        if ($existingInspection !== null) {
            return response()->json([
                'message' => 'A ' . str_replace('_', ' ', $validated['type']) . ' inspection already exists for this lead and vehicle.',
                'inspectionId' => $existingInspection->id,
            ], 422);
        }

        if ((string) $validated['type'] === 'post_departure' && ! $this->hasPreDepartureInspection(
            leadId: isset($validated['lead']['id']) ? (int) $validated['lead']['id'] : null,
            leaseAllocationId: isset($validated['leaseAllocationId']) ? (int) $validated['leaseAllocationId'] : null,
            vehicleId: (int) $validated['vehicle']['id'],
        )) {
            return response()->json([
                'message' => 'Pre checklist must be completed before post checklist for this lead and vehicle.',
            ], 422);
        }

        $odometerPayload = $this->resolveOdometerColumns(
            validated: $validated,
            type: (string) $validated['type'],
        );

        $parkingLocation = $this->resolveParkingLocation($request, $validated);

        $inspection = DB::transaction(function () use ($request, $validated, $odometerPayload, $parkingLocation): Inspection {
            $inspection = Inspection::create([
                'user_id' => $request->user()->id,
                'lead_id' => $validated['lead']['id'] ?? null,
                'lease_allocation_id' => $validated['leaseAllocationId'] ?? null,
                'vehicle_id' => $validated['vehicle']['id'],
                'type' => $validated['type'],
                'odometer_out' => $odometerPayload['odometer_out'],
                'odometer_in' => $odometerPayload['odometer_in'],
                'parking_location' => $parkingLocation,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['items'] as $index => $item) {
                $inspection->items()->create([
                    'checklist_id' => $item['checklist_id'],
                    'checklist_title' => $item['checklist_title'],
                    'name' => $item['name'],
                    'text' => $item['text'],
                    'status' => $item['status'],
                    'issue' => $item['issue'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            if (! empty($validated['images'])) {
                foreach ($validated['images'] as $index => $imagePath) {
                    $inspection->images()->create([
                        'path' => $this->normalizeInspectionImageInput($imagePath),
                        'sort_order' => $index,
                    ]);
                }
            }

            $this->syncVehicleAfterPostDeparture($inspection, $request);

            return $inspection;
        });

        $inspection->load('items', 'images', 'lead', 'vehicle', 'leaseAllocation.leaseContract');

        return response()->json([
            'message' => 'Inspection submitted successfully.',
            'inspection' => $this->transformInspection($inspection),
        ], 201);
    }

    /**
     * Update an inspection
     */
    public function update(Request $request, Inspection $inspection): JsonResponse
    {
        if ($inspection->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to access this inspection.',
            ], 403);
        }

        $validated = $request->validate([
            'type' => ['sometimes', Rule::in(self::INSPECTION_TYPES)],
            'remarks' => ['nullable', 'string'],
            'parking_location' => ['nullable', 'string', 'max:255'],
            'parkingLocation' => ['nullable', 'string', 'max:255'],
            'parked_location' => ['nullable', 'string', 'max:255'],
            'parkedLocation' => ['nullable', 'string', 'max:255'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'odometer_reading' => ['nullable', 'integer', 'min:0'],
            'odometer_out' => ['nullable', 'integer', 'min:0'],
            'odometer_in' => ['nullable', 'integer', 'min:0'],
        ]);

        $targetType = (string) ($validated['type'] ?? $inspection->type);

        $existingInspection = $this->findDuplicateInspection(
            leadId: $inspection->lead_id !== null ? (int) $inspection->lead_id : null,
            leaseAllocationId: $inspection->lease_allocation_id !== null ? (int) $inspection->lease_allocation_id : null,
            vehicleId: (int) $inspection->vehicle_id,
            type: $targetType,
            ignoreInspectionId: (int) $inspection->id,
        );

        if ($existingInspection !== null) {
            return response()->json([
                'message' => 'A ' . str_replace('_', ' ', $targetType) . ' inspection already exists for this lead and vehicle.',
                'inspectionId' => $existingInspection->id,
            ], 422);
        }

        if ($targetType === 'post_departure' && $inspection->type !== 'post_departure' && ! $this->hasPreDepartureInspection(
            leadId: $inspection->lead_id !== null ? (int) $inspection->lead_id : null,
            leaseAllocationId: $inspection->lease_allocation_id !== null ? (int) $inspection->lease_allocation_id : null,
            vehicleId: (int) $inspection->vehicle_id,
            ignoreInspectionId: (int) $inspection->id,
        )) {
            return response()->json([
                'message' => 'Pre checklist must be completed before post checklist for this lead and vehicle.',
            ], 422);
        }

        $odometerPayload = $this->resolveOdometerColumns(
            validated: $validated,
            type: $targetType,
            inspection: $inspection,
        );

        $updatePayload = array_merge($validated, $odometerPayload);
        if ($request->hasAny(['parking_location', 'parkingLocation', 'parked_location', 'parkedLocation'])) {
            $updatePayload['parking_location'] = $this->resolveParkingLocation($request, $validated);
        }

        DB::transaction(function () use ($inspection, $updatePayload, $request): void {
            $inspection->update($updatePayload);
            $inspection->refresh();

            $this->syncVehicleAfterPostDeparture($inspection, $request);
        });

        $inspection->load('items', 'images', 'lead', 'vehicle');

        return response()->json([
            'message' => 'Inspection updated successfully.',
            'inspection' => $this->transformInspection($inspection),
        ]);
    }

    /**
     * Delete an inspection
     */
    public function destroy(Request $request, Inspection $inspection): JsonResponse
    {
        if ($inspection->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to access this inspection.',
            ], 403);
        }

        $inspection->delete();

        return response()->json([
            'message' => 'Inspection deleted successfully.',
        ]);
    }

    /**
     * Download inspection report as PDF for manager and driver signatures.
     */
    public function pdf(Request $request, Inspection $inspection): Response
    {
        if ($inspection->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to access this inspection.',
            ], 403);
        }

        $inspection->load('items', 'images', 'lead', 'vehicle');

        $company = [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
        ];

        $inspectionPayload = $this->transformInspection($inspection);

        $groupedItems = collect($inspectionPayload['items'])
            ->groupBy('checklistTitle')
            ->map(fn($items, $title): array => [
                'title' => (string) $title,
                'items' => array_values($items->all()),
            ])
            ->values()
            ->all();

        $images = collect($inspectionPayload['images'])
            ->map(function (array $image): ?array {
                $path = (string) ($image['path'] ?? '');

                if ($path === '') {
                    return null;
                }

                return [
                    'path' => $path,
                    'dataUri' => $this->resolveImageDataUri($path),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $pdf = Pdf::loadView('inspections.pdf', [
            'inspection' => $inspectionPayload,
            'groupedItems' => $groupedItems,
            'images' => $images,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'inspection-checklist-' . $inspection->id . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Upload an image file for an inspection.
     * POST /api/inspections/{inspection}/images
     */
    public function uploadImage(Request $request, Inspection $inspection): JsonResponse
    {
        if ($inspection->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to upload images for this inspection.',
            ], 403);
        }

        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $path = 'inspection-images/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        $image = $inspection->images()->create([
            'path' => $path,
            'sort_order' => $inspection->images()->max('sort_order') + 1,
        ]);

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'image' => [
                'id' => $image->id,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'sortOrder' => $image->sort_order,
            ],
        ], 201);
    }

    /**
     * Transform inspection for API response
     */
    private function transformInspection(Inspection $inspection): array
    {
        return [
            'id' => $inspection->id,
            'type' => $inspection->type,
            'remarks' => $inspection->remarks,
            'lead' => $inspection->lead ? [
                'id' => $inspection->lead->id,
                'clientCompany' => $inspection->lead->client_company,
                'bookingRef' => $inspection->lead->booking_ref ?? null,
            ] : [
                'id' => 'lease:' . $inspection->lease_allocation_id,
                'leaseAllocationId' => $inspection->lease_allocation_id,
                'clientCompany' => $inspection->leaseAllocation?->leaseContract?->client_name ?? 'Long-Term Lease',
                'bookingRef' => $inspection->leaseAllocation?->group_name
                    ?: $inspection->leaseAllocation?->leaseContract?->group_name
                    ?: 'Lease #' . $inspection->lease_allocation_id,
            ],
            'leaseAllocationId' => $inspection->lease_allocation_id,
            'vehicle' => [
                'id' => $inspection->vehicle->id,
                'make' => $inspection->vehicle->make,
                'model' => $inspection->vehicle->model,
                'plateNo' => $inspection->vehicle->plate_no,
            ],
            'odometerOut' => $inspection->odometer_out,
            'odometerIn' => $inspection->odometer_in,
            'odometer_out' => $inspection->odometer_out,
            'odometer_in' => $inspection->odometer_in,
            'parkingLocation' => $inspection->parking_location,
            'parking_location' => $inspection->parking_location,
            'items' => $inspection->items->map(fn($item): array => [
                'id' => $item->id,
                'checklistId' => $item->checklist_id,
                'checklistTitle' => $item->checklist_title,
                'name' => $item->name,
                'text' => $item->text,
                'status' => $item->status,
                'issue' => $item->issue,
                'sortOrder' => $item->sort_order,
            ])->values(),
            'images' => $inspection->images->map(fn($image): array => [
                'id' => $image->id,
                'path' => $image->path,
                'sortOrder' => $image->sort_order,
            ])->values(),
            'createdAt' => $inspection->created_at?->toIso8601String(),
            'updatedAt' => $inspection->updated_at?->toIso8601String(),
        ];
    }

    private function resolveLogoDataUri(): ?string
    {
        $logoPath = Setting::get('logo');

        if (! is_string($logoPath) || $logoPath === '' || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $contents = Storage::disk('public')->get($logoPath);
        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function resolveImageDataUri(string $path): ?string
    {
        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }

        $normalizedPath = $this->normalizeStoredInspectionImagePath($path);

        if ($normalizedPath !== null && Storage::disk('public')->exists($normalizedPath)) {
            $contents = Storage::disk('public')->get($normalizedPath);
            $extension = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'png' => 'image/png',
                default => 'application/octet-stream',
            };

            if (! str_starts_with($mime, 'image/')) {
                return null;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        }

        if (! str_starts_with($path, '/') || ! $this->isAllowedAbsoluteImagePath($path) || ! is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        if (! str_starts_with($mime, 'image/')) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function normalizeInspectionImageInput(string $imagePath): string
    {
        if (str_starts_with($imagePath, 'data:image/')) {
            return $this->storeBase64InspectionImage($imagePath) ?? $imagePath;
        }

        return $imagePath;
    }

    private function isAllowedAbsoluteImagePath(string $path): bool
    {
        $allowedPrefixes = [
            rtrim(base_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            rtrim(public_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            rtrim(storage_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function storeBase64InspectionImage(string $dataUri): ?string
    {
        if (! preg_match('/^data:(image\/[a-z0-9.+-]+);base64,(.+)$/i', $dataUri, $matches)) {
            return null;
        }

        $extension = match (strtolower($matches[1])) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'png',
        };

        $decoded = base64_decode($matches[2], true);

        if ($decoded === false) {
            return null;
        }

        $path = 'inspection-images/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, $decoded);

        return $path;
    }

    private function normalizeStoredInspectionImagePath(string $path): ?string
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, 'storage/')) {
            return substr($trimmed, 8);
        }

        if (str_starts_with($trimmed, '/storage/')) {
            return substr($trimmed, 9);
        }

        if (preg_match('#^https?://#i', $trimmed)) {
            $parsedPath = parse_url($trimmed, PHP_URL_PATH);

            if (! is_string($parsedPath)) {
                return null;
            }

            return $this->normalizeStoredInspectionImagePath($parsedPath);
        }

        if (str_starts_with($trimmed, '/')) {
            return null;
        }

        return $trimmed;
    }

    private function findDuplicateInspection(
        ?int $leadId,
        ?int $leaseAllocationId,
        int $vehicleId,
        string $type,
        ?int $ignoreInspectionId = null,
    ): ?Inspection {
        $query = Inspection::query()
            ->when($leadId !== null, fn($query) => $query->where('lead_id', $leadId))
            ->when($leaseAllocationId !== null, fn($query) => $query->where('lease_allocation_id', $leaseAllocationId))
            ->where('vehicle_id', $vehicleId)
            ->where('type', $type);

        if ($ignoreInspectionId !== null) {
            $query->where('id', '!=', $ignoreInspectionId);
        }

        return $query->first();
    }

    private function hasPreDepartureInspection(
        ?int $leadId,
        ?int $leaseAllocationId,
        int $vehicleId,
        ?int $ignoreInspectionId = null,
    ): bool {
        $query = Inspection::query()
            ->when($leadId !== null, fn($query) => $query->where('lead_id', $leadId))
            ->when($leaseAllocationId !== null, fn($query) => $query->where('lease_allocation_id', $leaseAllocationId))
            ->where('vehicle_id', $vehicleId)
            ->where('type', 'pre_departure');

        if ($ignoreInspectionId !== null) {
            $query->where('id', '!=', $ignoreInspectionId);
        }

        return $query->exists();
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function resolveParkingLocation(Request $request, array $validated): ?string
    {
        foreach (['parking_location', 'parkingLocation', 'parked_location', 'parkedLocation'] as $key) {
            if (array_key_exists($key, $validated)) {
                $value = trim((string) ($validated[$key] ?? ''));
                return $value === '' ? null : $value;
            }

            if ($request->exists($key)) {
                $value = trim((string) $request->input($key));
                return $value === '' ? null : $value;
            }
        }

        return null;
    }

    private function syncVehicleAfterPostDeparture(Inspection $inspection, Request $request): void
    {
        if ($inspection->type !== 'post_departure') {
            return;
        }

        $vehicle = Vehicle::query()->find($inspection->vehicle_id);
        if ($vehicle !== null) {
            $payload = [
                'status' => 'Available',
            ];

            $odometerReading = $this->extractPostDepartureOdometer($request);
            if ($odometerReading !== null) {
                $payload['mileage'] = $odometerReading;
            }

            $vehicle->update($payload);
        }

        $allocations = SafariAllocation::query()
            ->where('lead_id', $inspection->lead_id)
            ->where('vehicle_id', $inspection->vehicle_id)
            ->whereIn('status', ['Assigned', 'Pending', 'In Progress'])
            ->get();

        foreach ($allocations as $allocation) {
            $releaseNote = 'Auto-completed by post-departure inspection #' . $inspection->id . ' on ' . now()->toDateTimeString();
            $existingNotes = trim((string) ($allocation->notes ?? ''));

            $allocation->update([
                'status' => 'Completed',
                'notes' => $existingNotes === ''
                    ? $releaseNote
                    : $existingNotes . "\n" . $releaseNote,
            ]);
        }
    }

    private function extractPostDepartureOdometer(Request $request): ?int
    {
        foreach (['odometer_in', 'odometer_reading', 'odometer'] as $key) {
            $raw = $request->input($key);

            if ($raw === null) {
                continue;
            }

            if (is_string($raw)) {
                $raw = trim(str_replace([',', ' '], '', $raw));
            }

            if ($raw === '') {
                continue;
            }

            if (! is_numeric($raw)) {
                continue;
            }

            $value = (int) $raw;
            if ($value >= 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, int|null>
     */
    private function resolveOdometerColumns(
        array $validated,
        string $type,
        ?Inspection $inspection = null,
    ): array {
        $resolvedOut = array_key_exists('odometer_out', $validated)
            ? $validated['odometer_out']
            : $inspection?->odometer_out;
        $resolvedIn = array_key_exists('odometer_in', $validated)
            ? $validated['odometer_in']
            : $inspection?->odometer_in;

        $sharedReading = null;
        if (array_key_exists('odometer_reading', $validated)) {
            $sharedReading = $validated['odometer_reading'];
        } elseif (array_key_exists('odometer', $validated)) {
            $sharedReading = $validated['odometer'];
        }

        if ($sharedReading !== null) {
            if ($type === 'pre_departure') {
                $resolvedOut = $sharedReading;
            }

            if ($type === 'post_departure') {
                $resolvedIn = $sharedReading;
            }
        }

        return [
            'odometer_out' => is_int($resolvedOut) ? $resolvedOut : (is_numeric($resolvedOut) ? (int) $resolvedOut : null),
            'odometer_in' => is_int($resolvedIn) ? $resolvedIn : (is_numeric($resolvedIn) ? (int) $resolvedIn : null),
        ];
    }
}
