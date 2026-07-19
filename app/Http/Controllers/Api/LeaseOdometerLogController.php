<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaseAllocation;
use App\Models\OdometerLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeaseOdometerLogController extends Controller
{
    public function index(Request $request, LeaseAllocation $leaseAllocation): JsonResponse
    {
        $this->authorizeDriver($request, $leaseAllocation);

        $logs = $leaseAllocation->odometerLogs()
            ->with('user:id,name')
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        return response()->json(['logs' => $logs->map(fn (OdometerLog $log): array => $this->transform($log))->values()]);
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

    private function authorizeDriver(Request $request, LeaseAllocation $allocation): void
    {
        abort_unless((int) $allocation->driver_id === (int) $request->user()->id, 403, 'This lease is assigned to another driver.');
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
