<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaseAllocation;
use App\Models\OdometerLog;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LeaseOdometerLogController extends Controller
{
    private const TANK_CAPACITY_LITERS = 180.0;

    public function index(Request $request, LeaseAllocation $leaseAllocation): JsonResponse
    {
        $this->authorizeDriver($request, $leaseAllocation);

        $logs = $leaseAllocation->odometerLogs()
            ->with('user:id,name')
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        return response()->json(['logs' => $logs->map(fn(OdometerLog $log): array => $this->transform($log))->values()]);
    }

    public function store(Request $request, LeaseAllocation $leaseAllocation): JsonResponse
    {
        $this->authorizeDriver($request, $leaseAllocation);
        $this->authorize('create', OdometerLog::class);

        $type = $request->input('entry_type');
        if (in_array($type, ['Start', 'Stop', 'End'], true)) {
            $request->merge(['entry_type' => 'Movement']);
        }

        $validated = $request->validate([
            'client_id' => ['nullable', 'string', 'max:64'],
            'entry_type' => ['required', Rule::in(['Movement', 'Fuel'])],
            'fuel_fill_type' => ['nullable', Rule::in(['full_tank', 'extra'])],
            'location' => ['required', 'string', 'max:255'],
            'odometer_reading' => ['required', 'integer', 'min:0'],
            'liters' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'station' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'recorded_at' => ['nullable', 'date'],
            'photo' => ['nullable', 'file', 'image', 'max:10240'],
        ]);

        if (! empty($validated['client_id'])) {
            $existing = OdometerLog::where('client_id', $validated['client_id'])->first();
            if ($existing) {
                return response()->json(['log' => $this->transform($existing->loadMissing('user:id,name'))]);
            }
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $photoPath = 'odometer-photos/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;
            Storage::disk('public')->put($photoPath, file_get_contents($file->getRealPath()));
        }

        $log = OdometerLog::create([
            'lease_allocation_id' => $leaseAllocation->id,
            'user_id' => $request->user()->id,
            'client_id' => $validated['client_id'] ?? null,
            'entry_type' => $validated['entry_type'],
            'fuel_fill_type' => $validated['entry_type'] === 'Fuel'
                ? ($validated['fuel_fill_type'] ?? 'full_tank')
                : null,
            'location' => $validated['location'],
            'odometer_reading' => $validated['odometer_reading'],
            'liters' => $validated['liters'] ?? null,
            'unit_price' => $validated['unit_price'] ?? null,
            'station' => $validated['station'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'photo_path' => $photoPath,
            'recorded_at' => isset($validated['recorded_at']) ? Carbon::parse($validated['recorded_at']) : now(),
        ]);

        return response()->json(['log' => $this->transform($log->load('user:id,name'))], 201);
    }

    public function report(Request $request, LeaseAllocation $leaseAllocation): JsonResponse
    {
        $payload = $this->buildReport($leaseAllocation, false);

        return response()->json([
            'message' => 'Odometer report fetched successfully.',
            'report' => $payload['report'],
            'logs' => $payload['logs'],
            'fuelRefills' => $payload['fuelRefills'],
        ]);
    }

    public function pdf(Request $request, LeaseAllocation $leaseAllocation): Response
    {
        $payload = $this->buildReport($leaseAllocation, true);

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

        return $pdf->download('odometer-log-report-lease-' . $leaseAllocation->id . '.pdf');
    }

    private function authorizeDriver(Request $request, LeaseAllocation $allocation): void
    {
        abort_unless((int) $allocation->driver_id === (int) $request->user()->id, 403, 'This lease is assigned to another driver.');
    }

    /**
     * @return array{report: array<string, mixed>, logs: array<int, array<string, mixed>>, fuelRefills: array<int, array<string, mixed>>}
     */
    private function buildReport(LeaseAllocation $leaseAllocation, bool $includePdfImageData = false): array
    {
        $leaseAllocation->loadMissing([
            'leaseContract:id,client_name,group_name,lease_type,start_date,end_date',
            'vehicle:id,vehicle_no,plate_no,make,model',
            'driver:id,name',
        ]);

        $logs = $leaseAllocation->odometerLogs()
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
        $refillNo = 0;

        foreach ($fuelRows as $fuelRow) {
            $refillNo++;
            $fillType = $fuelRow['fuel_fill_type'] ?? 'full_tank';
            $isFullTank = $fillType === 'full_tank';
            $distanceCovered = null;

            if ($isFullTank && $previousFuel !== null) {
                $distanceCovered = max(
                    0,
                    (int) ($fuelRow['odometer_reading'] ?? 0) - (int) ($previousFuel['odometer_reading'] ?? 0)
                );
            }

            $fuelAdded = $fuelRow['liters'] !== null ? (float) $fuelRow['liters'] : null;
            $fuelConsumed = $isFullTank && $fuelAdded !== null ? max(0, self::TANK_CAPACITY_LITERS - $fuelAdded) : null;

            $driverAverage = null;
            if ($distanceCovered !== null && $fuelConsumed !== null && $fuelConsumed > 0) {
                $driverAverage = round($distanceCovered / $fuelConsumed, 2);
                $totalRefillDistance += $distanceCovered;
                $totalFuelConsumed += $fuelConsumed;
            }

            $fuelRefills[] = [
                'refillNo' => $refillNo,
                'fillType' => $fillType,
                'fillTypeLabel' => $fillType === 'extra' ? 'Partial Refill' : 'Full Tank',
                'date' => $fuelRow['recorded_at'] ?? null,
                'odometer' => $fuelRow['odometer_reading'] ?? null,
                'fuelAdded' => $fuelAdded,
                'fuelConsumed' => $fuelConsumed,
                'distanceCovered' => $distanceCovered,
                'driverAverage' => $driverAverage,
                'station' => $fuelRow['station'] ?? null,
                'recordedBy' => $fuelRow['recorded_by'] ?? null,
            ];

            if ($isFullTank) {
                $previousFuel = $fuelRow;
            }
        }

        $overallDriverAverage = $totalFuelConsumed > 0
            ? round($totalRefillDistance / $totalFuelConsumed, 2)
            : null;

        $contract = $leaseAllocation->leaseContract;
        $report = [
            'tripId' => (int) $leaseAllocation->id,
            'assignmentType' => 'lease',
            'leadBookingRef' => $contract?->lease_type ?: 'Long Term Lease',
            'groupName' => $leaseAllocation->group_name ?: $contract?->group_name,
            'clientCompany' => $contract?->client_name,
            'routeParks' => $leaseAllocation->itinerary,
            'tripStartDate' => optional($leaseAllocation->start_date)->toDateString(),
            'tripEndDate' => optional($leaseAllocation->end_date)->toDateString(),
            'vehicleLabel' => trim(implode(' ', array_filter([
                $leaseAllocation->vehicle?->vehicle_no,
                $leaseAllocation->vehicle?->plate_no,
                $leaseAllocation->vehicle?->make,
                $leaseAllocation->vehicle?->model,
            ]))),
            'driverName' => $leaseAllocation->driver?->name,
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

    private function transform(OdometerLog $log): array
    {
        return [
            'id' => $log->id,
            'lease_allocation_id' => $log->lease_allocation_id,
            'user_id' => $log->user_id,
            'recorded_by' => $log->user?->name,
            'client_id' => $log->client_id,
            'entry_type' => $log->entry_type,
            'fuel_fill_type' => $log->entry_type === 'Fuel'
                ? ($log->fuel_fill_type ?: 'full_tank')
                : null,
            'location' => $log->location,
            'odometer_reading' => (int) $log->odometer_reading,
            'liters' => $log->liters !== null ? (float) $log->liters : null,
            'unit_price' => $log->unit_price !== null ? (float) $log->unit_price : null,
            'station' => $log->station,
            'notes' => $log->notes,
            'photo_path' => $log->photo_path,
            'photo_url' => $log->photo_path ? asset('storage/' . ltrim($log->photo_path, '/')) : null,
            'recorded_at' => optional($log->recorded_at)?->toIso8601String(),
        ];
    }
}
