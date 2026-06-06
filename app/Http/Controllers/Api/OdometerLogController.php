<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OdometerLog;
use App\Models\SafariAllocation;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OdometerLogController extends Controller
{
    // Simplified taxonomy: every reading is either a generic Movement
    // (any en-route odometer capture) or a Fuel-up that opens a tank
    // cycle. Legacy Start / Stop / End rows are coerced to Movement on
    // read so older app builds keep working.
    private const ENTRY_TYPES = ['Movement', 'Fuel'];

    private const LEGACY_TYPE_MAP = [
        'Start' => 'Movement',
        'Stop' => 'Movement',
        'End' => 'Movement',
    ];

    private const TANK_CAPACITY_LITERS = 180.0;

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

        // Older app builds may still send Start / Stop / End – coerce them
        // to Movement so the new tighter validator accepts the request.
        $this->coerceLegacyEntryType($request);

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
            $recordedAt = isset($validated['recorded_at'])
                ? Carbon::parse($validated['recorded_at'])
                : now();

            $log = DB::transaction(function () use (
                $safariAllocation,
                $request,
                $validated,
                $clientId,
                $photoPath,
                $recordedAt,
            ): OdometerLog {
                $entryType = $validated['entry_type'];

                // Decide which Fuel log this reading belongs to:
                //  - Fuel rows open their own cycle (fuel_log_id stays null).
                //  - Other rows attach to the latest Fuel log on this trip
                //    whose recorded_at is <= this reading.
                $fuelLogId = null;
                if ($entryType !== 'Fuel') {
                    $fuelLogId = OdometerLog::query()
                        ->where('safari_allocation_id', $safariAllocation->id)
                        ->where('entry_type', 'Fuel')
                        ->where('recorded_at', '<=', $recordedAt)
                        ->orderByDesc('recorded_at')
                        ->orderByDesc('id')
                        ->value('id');
                }

                $log = OdometerLog::create([
                    'safari_allocation_id' => $safariAllocation->id,
                    'user_id' => $request->user()?->id,
                    'fuel_log_id' => $fuelLogId,
                    'client_id' => $clientId,
                    'entry_type' => $entryType,
                    'location' => $validated['location'],
                    'odometer_reading' => $validated['odometer_reading'],
                    'liters' => $validated['liters'] ?? null,
                    'unit_price' => $validated['unit_price'] ?? null,
                    'station' => $validated['station'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'photo_path' => $photoPath,
                    'recorded_at' => $recordedAt,
                ]);

                if ($entryType === 'Fuel') {
                    // Closing the previous open tank cycle on this trip.
                    OdometerLog::query()
                        ->where('safari_allocation_id', $safariAllocation->id)
                        ->where('entry_type', 'Fuel')
                        ->whereNull('closed_at')
                        ->where('id', '!=', $log->id)
                        ->where('recorded_at', '<', $recordedAt)
                        ->orderByDesc('recorded_at')
                        ->orderByDesc('id')
                        ->limit(1)
                        ->update(['closed_at' => $recordedAt]);

                    // Adopt any orphan readings recorded between the
                    // previous fuel-up and this one that aren't yet linked
                    // to a fuel cycle (handles back-dated entries).
                    OdometerLog::query()
                        ->where('safari_allocation_id', $safariAllocation->id)
                        ->where('entry_type', '!=', 'Fuel')
                        ->whereNull('fuel_log_id')
                        ->where('recorded_at', '<=', $recordedAt)
                        ->update(['fuel_log_id' => $log->id]);
                }

                return $log;
            });
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

        $this->coerceLegacyEntryType($request);

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
     * GET /api/trips/{safariAllocation}/odometer-logs/report
     *
     * Returns report data for on-screen viewing without forcing a PDF download.
     */
    public function reportForTrip(Request $request, SafariAllocation $safariAllocation): JsonResponse
    {
        $this->authorize('view', $safariAllocation);

        $payload = $this->buildTripReport($safariAllocation, false);

        return response()->json([
            'message' => 'Odometer report fetched successfully.',
            'report' => $payload['report'],
            'logs' => $payload['logs'],
            'fuelRefills' => $payload['fuelRefills'],
        ]);
    }

    /**
     * GET /api/trips/{safariAllocation}/odometer-logs/pdf
     *
     * Downloads a trip odometer/fuel report PDF similar to the inspection
     * report style, including trip metadata and all recorded log rows.
     */
    public function pdfForTrip(Request $request, SafariAllocation $safariAllocation): Response
    {
        $this->authorize('view', $safariAllocation);

        $payload = $this->buildTripReport($safariAllocation, true);

        $company = [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
        ];

        $pdf = Pdf::loadView('odometer-logs.pdf', [
            'company' => $company,
            'report' => $payload['report'],
            'logs' => $payload['logs'],
            'fuelRefills' => $payload['fuelRefills'],
        ])->setPaper('a4', 'portrait');

        return $pdf->download('odometer-log-report-trip-' . $safariAllocation->id . '.pdf');
    }

    /**
     * @return array{report: array<string, mixed>, logs: array<int, array<string, mixed>>, fuelRefills: array<int, array<string, mixed>>}
     */
    private function buildTripReport(SafariAllocation $safariAllocation, bool $includePdfImageData = false): array
    {
        $safariAllocation->loadMissing([
            'lead:id,booking_ref,group_name,client_company,route_parks,start_date,end_date',
            'vehicle:id,vehicle_no,plate_no,make,model',
            'driver:id,name',
        ]);

        $logs = $safariAllocation->odometerLogs()
            ->with('user:id,name')
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $rows = $logs->map(fn(OdometerLog $log): array => $this->transform($log))->values();

        if ($includePdfImageData) {
            $rows = $rows->map(function (array $row): array {
                $row['photo_data_uri'] = $this->resolveImageDataUri((string) ($row['photo_path'] ?? ''));

                return $row;
            })->values();
        }

        $firstReading = $rows->first()['odometer_reading'] ?? null;
        $lastReading = $rows->last()['odometer_reading'] ?? null;

        $fuelRows = $rows
            ->filter(fn(array $row): bool => ($row['entry_type'] ?? '') === 'Fuel')
            ->values();

        $totalLiters = (float) $fuelRows->sum(fn(array $row): float => (float) ($row['liters'] ?? 0));
        $totalFuelCost = (float) $fuelRows->sum(function (array $row): float {
            $liters = (float) ($row['liters'] ?? 0);
            $unitPrice = (float) ($row['unit_price'] ?? 0);

            return $liters * $unitPrice;
        });

        $fuelRefills = [];
        $totalRefillDistance = 0.0;
        $totalFuelConsumed = 0.0;

        $previousFuel = null;
        foreach ($fuelRows as $index => $fuelRow) {
            $distanceCovered = null;
            if ($previousFuel !== null) {
                $distanceCovered = max(
                    0,
                    (int) ($fuelRow['odometer_reading'] ?? 0) - (int) ($previousFuel['odometer_reading'] ?? 0)
                );
            }

            $fuelAdded = $fuelRow['liters'] !== null ? (float) $fuelRow['liters'] : null;
            $fuelConsumed = $fuelAdded !== null ? max(0, self::TANK_CAPACITY_LITERS - $fuelAdded) : null;

            $driverAverage = null;
            if ($distanceCovered !== null && $fuelConsumed !== null && $fuelConsumed > 0) {
                $driverAverage = round($distanceCovered / $fuelConsumed, 2);
                $totalRefillDistance += $distanceCovered;
                $totalFuelConsumed += $fuelConsumed;
            }

            $fuelRefills[] = [
                'refillNo' => $index + 1,
                'date' => $fuelRow['recorded_at'] ?? null,
                'odometer' => $fuelRow['odometer_reading'] ?? null,
                'fuelAdded' => $fuelAdded,
                'fuelConsumed' => $fuelConsumed,
                'distanceCovered' => $distanceCovered,
                'driverAverage' => $driverAverage,
                'station' => $fuelRow['station'] ?? null,
                'recordedBy' => $fuelRow['recorded_by'] ?? null,
            ];

            $previousFuel = $fuelRow;
        }

        $overallDriverAverage = $totalFuelConsumed > 0
            ? round($totalRefillDistance / $totalFuelConsumed, 2)
            : null;

        $report = [
            'tripId' => (int) $safariAllocation->id,
            'leadBookingRef' => $safariAllocation->lead?->booking_ref,
            'groupName' => $safariAllocation->lead?->group_name,
            'clientCompany' => $safariAllocation->lead?->client_company,
            'routeParks' => $safariAllocation->lead?->route_parks,
            'tripStartDate' => optional($safariAllocation->start_date)->toDateString(),
            'tripEndDate' => optional($safariAllocation->end_date)->toDateString(),
            'vehicleLabel' => trim(implode(' ', array_filter([
                $safariAllocation->vehicle?->vehicle_no,
                $safariAllocation->vehicle?->plate_no,
                $safariAllocation->vehicle?->make,
                $safariAllocation->vehicle?->model,
            ]))),
            'driverName' => $safariAllocation->driver?->name,
            'tankCapacityLiters' => self::TANK_CAPACITY_LITERS,
            'totalLogs' => (int) $rows->count(),
            'firstReading' => $firstReading,
            'lastReading' => $lastReading,
            'distanceCovered' => ($firstReading !== null && $lastReading !== null)
                ? max(0, (int) $lastReading - (int) $firstReading)
                : null,
            'totalFuelEvents' => (int) $fuelRows->count(),
            'totalLiters' => $totalLiters,
            'totalFuelCost' => $totalFuelCost,
            'overallDriverAverage' => $overallDriverAverage,
            'generatedAt' => now()->toIso8601String(),
        ];

        return [
            'report' => $report,
            'logs' => $rows->all(),
            'fuelRefills' => $fuelRefills,
        ];
    }

    private function resolveImageDataUri(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        try {
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            $contents = Storage::disk('public')->get($path);
            if ($contents === '') {
                return null;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                default => 'image/jpeg',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        } catch (Throwable) {
            return null;
        }
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

    /**
     * Older app builds and any legacy DB rows may carry Start / Stop / End
     * entry types. Coerce those to the simplified Movement value so they
     * pass the new validator (and the client always sees Movement / Fuel).
     */
    private function coerceLegacyEntryType(Request $request): void
    {
        $raw = $request->input('entry_type');
        if (! is_string($raw) || $raw === '') {
            return;
        }
        $mapped = self::LEGACY_TYPE_MAP[$raw] ?? null;
        if ($mapped !== null) {
            $request->merge(['entry_type' => $mapped]);
        }
    }

    private function transform(OdometerLog $log): array
    {
        $photoUrl = null;
        if (! empty($log->photo_path)) {
            try {
                $photoUrl = asset('storage/' . ltrim((string) $log->photo_path, '/'));
            } catch (Throwable) {
                $photoUrl = null;
            }
        }

        $entryType = (string) $log->entry_type;
        $entryType = self::LEGACY_TYPE_MAP[$entryType] ?? $entryType;

        return [
            'id' => $log->id,
            'trip_id' => $log->safari_allocation_id,
            'safari_allocation_id' => $log->safari_allocation_id,
            'user_id' => $log->user_id,
            'recorded_by' => $log->user?->name,
            'fuel_log_id' => $log->fuel_log_id,
            'client_id' => $log->client_id,
            'entry_type' => $entryType,
            'location' => $log->location,
            'odometer_reading' => (int) $log->odometer_reading,
            'liters' => $log->liters !== null ? (float) $log->liters : null,
            'unit_price' => $log->unit_price !== null ? (float) $log->unit_price : null,
            'station' => $log->station,
            'notes' => $log->notes,
            'photo_path' => $log->photo_path,
            'photo_url' => $photoUrl,
            'recorded_at' => optional($log->recorded_at)?->toIso8601String(),
            'closed_at' => optional($log->closed_at)?->toIso8601String(),
            'created_at' => optional($log->created_at)?->toIso8601String(),
            'updated_at' => optional($log->updated_at)?->toIso8601String(),
        ];
    }
}
