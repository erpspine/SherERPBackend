<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OdometerLog;
use App\Models\SafariAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class OdometerLogController extends Controller
{
    private const ENTRY_TYPES = ['Start', 'Stop', 'End', 'Fuel'];

    /**
     * GET /api/trips/{safariAllocation}/odometer-logs
     *
     * Drivers only see logs for trips they own (enforced by
     * SafariAllocationPolicy::view).
     */
    public function indexForTrip(Request $request, SafariAllocation $safariAllocation): JsonResponse
    {
        $this->authorize('view', $safariAllocation);

        $logs = $safariAllocation->odometerLogs()
            ->with('user:id,name')
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Odometer logs fetched successfully.',
            'logs' => $logs->map(fn(OdometerLog $log): array => $this->transform($log))->values(),
        ]);
    }

    /**
     * POST /api/trips/{safariAllocation}/odometer-logs
     *
     * Idempotent: if `client_id` matches an existing row the existing log is
     * returned with HTTP 200 so the offline outbox can safely retry without
     * creating duplicates.
     */
    public function storeForTrip(Request $request, SafariAllocation $safariAllocation): JsonResponse
    {
        // Driver must own the trip and have permission to create logs.
        $this->authorize('view', $safariAllocation);
        $this->authorize('create', OdometerLog::class);

        $validated = $request->validate([
            'client_id' => ['nullable', 'string', 'max:64'],
            'entry_type' => ['required', Rule::in(self::ENTRY_TYPES)],
            'location' => ['required', 'string', 'max:255'],
            'odometer_reading' => ['required', 'integer', 'min:0'],
            'liters' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'station' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'recorded_at' => ['nullable', 'date'],
            'photo' => ['nullable', 'file', 'image', 'max:10240'],
        ]);

        $clientId = $validated['client_id'] ?? null;

        // Idempotency – if the device already pushed this row, just echo it
        // back so the sync worker can mark its outbox entry synced.
        if ($clientId !== null && $clientId !== '') {
            $existing = OdometerLog::where('client_id', $clientId)->first();
            if ($existing !== null) {
                return response()->json([
                    'message' => 'Odometer log already recorded.',
                    'log' => $this->transform($existing->loadMissing('user:id,name')),
                ], 200);
            }
        }

        $photoPath = $this->storePhoto($request);

        try {
            $log = OdometerLog::create([
                'safari_allocation_id' => $safariAllocation->id,
                'user_id' => $request->user()?->id,
                'client_id' => $clientId,
                'entry_type' => $validated['entry_type'],
                'location' => $validated['location'],
                'odometer_reading' => $validated['odometer_reading'],
                'liters' => $validated['liters'] ?? null,
                'unit_price' => $validated['unit_price'] ?? null,
                'station' => $validated['station'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'photo_path' => $photoPath,
                'recorded_at' => $validated['recorded_at'] ?? now(),
            ]);
        } catch (Throwable $e) {
            // If the insert raced another sync attempt on the same client_id,
            // recover the winning row instead of returning a 500.
            if ($clientId !== null && $clientId !== '') {
                $existing = OdometerLog::where('client_id', $clientId)->first();
                if ($existing !== null) {
                    $this->discardPhoto($photoPath);

                    return response()->json([
                        'message' => 'Odometer log already recorded.',
                        'log' => $this->transform($existing->loadMissing('user:id,name')),
                    ], 200);
                }
            }
            $this->discardPhoto($photoPath);
            throw $e;
        }

        return response()->json([
            'message' => 'Odometer log recorded successfully.',
            'log' => $this->transform($log->loadMissing('user:id,name')),
        ], 201);
    }

    /**
     * PUT /api/odometer-logs/{odometerLog}
     */
    public function update(Request $request, OdometerLog $odometerLog): JsonResponse
    {
        $this->authorize('update', $odometerLog);

        $validated = $request->validate([
            'entry_type' => ['sometimes', Rule::in(self::ENTRY_TYPES)],
            'location' => ['sometimes', 'string', 'max:255'],
            'odometer_reading' => ['sometimes', 'integer', 'min:0'],
            'liters' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'station' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'recorded_at' => ['sometimes', 'nullable', 'date'],
            'photo' => ['sometimes', 'nullable', 'file', 'image', 'max:10240'],
        ]);

        // Photo replacement: clean the previous file once the new one is in.
        $newPhotoPath = $this->storePhoto($request);
        if ($newPhotoPath !== null) {
            $oldPhoto = $odometerLog->photo_path;
            $validated['photo_path'] = $newPhotoPath;
            $odometerLog->fill($validated)->save();
            $this->discardPhoto($oldPhoto);
        } else {
            $odometerLog->fill($validated)->save();
        }

        return response()->json([
            'message' => 'Odometer log updated successfully.',
            'log' => $this->transform($odometerLog->fresh()->loadMissing('user:id,name')),
        ]);
    }

    /**
     * DELETE /api/odometer-logs/{odometerLog}
     */
    public function destroy(Request $request, OdometerLog $odometerLog): JsonResponse
    {
        $this->authorize('delete', $odometerLog);

        $photo = $odometerLog->photo_path;
        $odometerLog->delete();
        $this->discardPhoto($photo);

        return response()->json([
            'message' => 'Odometer log deleted successfully.',
        ]);
    }

    /**
     * Save the uploaded `photo` file (if any) to the `public` disk and
     * return its relative path.
     */
    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        $file = $request->file('photo');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = 'odometer-photos/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    private function discardPhoto(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (Throwable) {
            // Best-effort cleanup – never let storage errors break the API.
        }
    }

    private function transform(OdometerLog $log): array
    {
        $photoUrl = null;
        if (! empty($log->photo_path)) {
            try {
                $photoUrl = Storage::disk('public')->url($log->photo_path);
            } catch (Throwable) {
                $photoUrl = null;
            }
        }

        return [
            'id' => $log->id,
            'trip_id' => $log->safari_allocation_id,
            'safari_allocation_id' => $log->safari_allocation_id,
            'user_id' => $log->user_id,
            'recorded_by' => $log->user?->name,
            'client_id' => $log->client_id,
            'entry_type' => $log->entry_type,
            'location' => $log->location,
            'odometer_reading' => (int) $log->odometer_reading,
            'liters' => $log->liters !== null ? (float) $log->liters : null,
            'unit_price' => $log->unit_price !== null ? (float) $log->unit_price : null,
            'station' => $log->station,
            'notes' => $log->notes,
            'photo_path' => $log->photo_path,
            'photo_url' => $photoUrl,
            'recorded_at' => optional($log->recorded_at)?->toIso8601String(),
            'created_at' => optional($log->created_at)?->toIso8601String(),
            'updated_at' => optional($log->updated_at)?->toIso8601String(),
        ];
    }
}
