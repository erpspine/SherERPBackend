<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SafariAllocation;
use App\Models\SafariFuelEntry;
use App\Models\LeaseAllocation;
use App\Models\Vehicle;
use App\Services\SafariFuelMetrics;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FuelManagementController extends Controller
{
    public function index(Request $request)
    {
        $feed = $this->fuelRows($request);
        return response()->json($feed);
    }

    public function saveMonthlyPrice(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'target_average' => ['sometimes', 'nullable', 'numeric', 'min:0.01', 'max:1000'],
        ]);
        Setting::set('fuel_deduction_price_' . $data['month'], number_format((float) $data['price'], 2, '.', ''));
        if (isset($data['target_average'])) Setting::set('fuel_target_average_' . $data['month'], number_format((float) $data['target_average'], 2, '.', '')); 
        return response()->json(['message' => 'Monthly deduction price saved.', 'prices' => $this->monthlyPrices(), 'target_averages' => $this->monthlyTargets()]);
    }


    private function monthlyTargets(): array
    {
        return Setting::where('key', 'like', 'fuel_target_average_%')->get()
            ->mapWithKeys(fn($setting) => [substr($setting->key, strlen('fuel_target_average_')) => (float) $setting->value])->all();
    }


    private function monthlyPrices(): array
    {
        return Setting::where('key', 'like', 'fuel_deduction_price_%')->get()
            ->mapWithKeys(fn($setting) => [substr($setting->key, strlen('fuel_deduction_price_')) => (float) $setting->value])->all();
    }

    public function exportPdf(Request $request)
    {
        $rows = collect($this->fuelRows($request)['entries']);
        $isReport = $request->boolean('report');
        $dateFrom = trim((string) $request->input('dateFrom', ''));
        $dateTo = trim((string) $request->input('dateTo', ''));
        $prices = $this->monthlyPrices();
        $targetAverages = $this->monthlyTargets();
        

        if ($isReport) {
            $rows = $rows->filter(fn(array $row): bool => $row['end_km'] !== null && $row['litres_filled_end'] !== null);
            $groups = $rows->filter(function (array $row) use ($request, $dateFrom, $dateTo): bool {
                $date = substr((string) ($row['filled_at'] ?? ''), 0, 10);
                $text = strtolower(implode(' ', [
                    $row['safari_allocation']['lead']['booking_ref'] ?? '',
                    $row['safari_allocation']['lead']['group_name'] ?? '',
                    $row['lease_allocation']['group_name'] ?? '',
                    $row['lease_allocation']['lease_contract']['client_name'] ?? '',
                    $row['vehicle']['vehicle_no'] ?? '',
                    $row['vehicle']['plate_no'] ?? '',
                    $row['driver']['name'] ?? '',
                ]));
                return (!$request->filled('vehicleId') || (string) ($row['vehicle_id'] ?? '') === (string) $request->input('vehicleId'))
                    && (!$request->filled('driverId') || (string) ($row['driver_id'] ?? '') === (string) $request->input('driverId'))
                    && (!$request->filled('search') || str_contains($text, strtolower((string) $request->input('search'))))
                    && (!$dateFrom || $date >= $dateFrom) && (!$dateTo || $date <= $dateTo);
            })->groupBy(fn(array $row) => $row['vehicle_id'] . ':' . substr($row['filled_at'], 0, 7))->sortKeys()->map(function ($items) use ($prices, $targetAverages): array {
                $month = substr($items->first()['filled_at'], 0, 7);
                $price = $prices[$month] ?? null;
                $calculated = $items->filter(fn(array $row): bool => $row['vehicle_average'] !== null && $row['km_done'] !== null && $row['litres_consumed'] > 0);
                $days = $items->contains(fn(array $row): bool => $row['trip_days'] === null) ? null : (int) $items->sum('trip_days');
                $km = (float) $calculated->sum('km_done');
                $litres = (float) $calculated->sum('litres_consumed');
                $expected = $km / 7;
                $excess = round(max(0, $litres - $expected), 2);
                $average = $litres > 0 ? $km / $litres : null;
                $targetAverage = $targetAverages[$month] ?? null;
                $status = $average === null ? 'Pending' : ($targetAverage === null ? 'Target not set' : ($average < $targetAverage ? 'Deduction' : ($average < 7 ? 'Warning' : 'Good')));
                return [
                    'vehicle' => $items->first()['vehicle'] ?? [],
                    'driver' => $items->map(fn(array $row) => $row['driver']['name'] ?? 'Unassigned')->unique()->implode(', '),
                    'month' => $month,
                    'price' => $price,
                    'trips' => $items->count(),
                    'days' => $days,
                    'litres' => $calculated->count() ? round($litres, 2) : null,
                    'km' => $calculated->count() ? $km : null,
                    'average_km_per_trip' => $days > 0 && $calculated->count() ? round($km / $days, 2) : null,
                    'expected' => $calculated->count() ? round($expected, 2) : null,
                    'excess' => $calculated->count() ? round($excess, 2) : null,
                    'deduction' => $status === 'Target not set' ? 'Target not set' : ($status === 'Deduction' ? ($price === null ? 'Price not set' : round($excess * $price, 2)) : 0),
                    'average' => $litres > 0 ? round($km / $litres, 2) : null,
                    'status' => $status,
                ];
            })->values();
            $title = 'FUEL CONSUMPTION REPORT';
            $columns = ['Vehicle', 'Driver', 'Month', 'Price/L', 'Trips', 'Total days', 'Consumed L', 'Total KM', 'Average KM per trip', 'Expected L', 'Excess L', 'Deduction', 'Actual average km/L', 'Status'];
            $totalDays = $groups->every(fn(array $row): bool => $row['days'] !== null) ? $groups->sum('days') : null;
            $totalLitres = (float) $groups->sum(fn(array $row): float => (float) ($row['litres'] ?? 0));
            $totalKm = (float) $groups->sum(fn(array $row): float => (float) ($row['km'] ?? 0));
            $deductionSummary = $groups->contains(fn(array $row): bool => $row['deduction'] === 'Target not set')
                ? 'Target not set'
                : ($groups->contains(fn(array $row): bool => $row['deduction'] === 'Price not set')
                    ? 'Price not set'
                    : round($groups->sum(fn(array $row): float => is_numeric($row['deduction']) ? (float) $row['deduction'] : 0), 2));
            $summary = [
                'trips' => $groups->sum('trips'),
                'days' => $totalDays,
                'litres' => $totalLitres > 0 ? round($totalLitres, 2) : null,
                'km' => $totalKm > 0 ? round($totalKm, 2) : null,
                'daily_average' => $totalDays > 0 ? round($totalKm / $totalDays, 2) : null,
                'deductions' => $deductionSummary,
            ];
        } else {
            $groups = $rows->filter(function (array $row) use ($request, $dateFrom, $dateTo): bool {
                $date = substr((string) ($row['filled_at'] ?? ''), 0, 10);
                $text = strtolower(implode(' ', [
                    $row['safari_allocation']['lead']['booking_ref'] ?? '',
                    $row['safari_allocation']['lead']['group_name'] ?? '',
                    $row['lease_allocation']['group_name'] ?? '',
                    $row['lease_allocation']['lease_contract']['client_name'] ?? '',
                    $row['vehicle']['vehicle_no'] ?? '',
                    $row['vehicle']['plate_no'] ?? '',
                    $row['driver']['name'] ?? '',
                ]));
                return (!$request->filled('vehicleId') || (string) ($row['vehicle_id'] ?? '') === (string) $request->input('vehicleId'))
                    && (!$request->filled('driverId') || (string) ($row['driver_id'] ?? '') === (string) $request->input('driverId'))
                    && (!$request->filled('search') || str_contains($text, strtolower((string) $request->input('search'))))
                    && (!$dateFrom || $date >= $dateFrom) && (!$dateTo || $date <= $dateTo);
            })->map(fn(array $row): array => [
                'vehicle' => ($row['vehicle']['plate_no'] ?? '—'),
                'driver' => $row['driver']['name'] ?? 'Unassigned',
                'date' => $row['filled_at'] ?? '',
                'beginning' => $row['litres_filled'] ?? null,
                'end' => $row['litres_filled_end'] ?? $row['litres_after_safari'] ?? null,
                'extra' => $row['extra_litres'] ?? null,
                'consumed' => $row['litres_consumed'] ?? null,
                'km' => $row['km_done'] ?? null,
                'average' => $row['vehicle_average'] ?? null,
            ])->values();
            $title = 'SAFARI FUEL AND AVERAGES';
            $columns = ['Vehicle', 'Driver', 'Date', 'Beginning L', 'End L', 'Extra L', 'Consumed L', 'KM Done', 'Average km/L'];
        }

        $company = ['name' => Setting::get('company_name', config('app.name')), 'email' => Setting::get('company_email'), 'phone' => Setting::get('company_phone'), 'address' => Setting::get('company_address')];
        $viewData = ['title' => $title, 'columns' => $columns, 'rows' => $groups, 'summary' => $summary ?? null, 'company' => $company, 'logoDataUri' => $this->resolveLogoDataUri(), 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'generatedAt' => now()->format('d/m/Y H:i')];
        $html = view()->file(resource_path('views/fuel-management.pdf.blade.php'), $viewData)->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        $filename = ($isReport ? 'fuel-consumption-report-' : 'safari-fuel-averages-') . now()->format('Ymd-His') . '.pdf';
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="' . $filename . '"']);
    }

    private function fuelRows(Request $request): array
    {
        $safariAllocations = SafariAllocation::with(['lead:id,booking_ref,group_name', 'vehicle:id,vehicle_no,plate_no', 'driver:id,name'])
            ->when($request->user()->hasRole('Driver'), fn($q) => $q->where('driver_id', $request->user()->id))
            ->orderByDesc('id')->get();
        $leaseAllocations = LeaseAllocation::with(['leaseContract:id,client_name,group_name', 'vehicle:id,vehicle_no,plate_no', 'driver:id,name'])
            ->when($request->user()->hasRole('Driver'), fn($q) => $q->where('driver_id', $request->user()->id))
            ->orderByDesc('id')->get();
        $allocations = $safariAllocations->map(fn($allocation) => array_merge($allocation->toArray(), ['allocation_type' => 'safari']))
            ->concat($leaseAllocations->map(fn($allocation) => [
                'id' => $allocation->id,
                'allocation_type' => 'long_term',
                'vehicle_id' => $allocation->vehicle_id,
                'driver_id' => $allocation->driver_id,
                'vehicle' => $allocation->vehicle?->toArray(),
                'driver' => $allocation->driver?->toArray(),
                'lease_contract' => $allocation->leaseContract?->toArray(),
                'group_name' => $allocation->group_name,
                'start_date' => $allocation->start_date?->format('Y-m-d'),
            ]))->values();
        // Load fuel history for vehicles in the visible allocations.
        $entries = SafariFuelEntry::with(['safariAllocation.lead:id,booking_ref,group_name', 'safariAllocation.driver:id,name', 'leaseAllocation.leaseContract:id,client_name,group_name', 'leaseAllocation.driver:id,name', 'vehicle:id,vehicle_no,plate_no,assigned_driver_id', 'vehicle.assignedDriver:id,name', 'driver:id,name'])
            ->whereIn('vehicle_id', $allocations->pluck('vehicle_id'))->orderBy('start_km')->orderBy('id')->get();
        $groups = $entries->groupBy('vehicle_id');
        $rows = [];
        foreach ($groups as $group) {
            foreach ($group as $index => $entry) {
                if ($entry->safari_allocation_id && !$safariAllocations->contains('id', $entry->safari_allocation_id)) continue;
                if ($entry->lease_allocation_id && !$leaseAllocations->contains('id', $entry->lease_allocation_id)) continue;
                $allocation = $entry->safariAllocation ?? $entry->leaseAllocation;
                $driver = $entry->driver ?? $allocation?->driver ?? $entry->vehicle?->assignedDriver;
                $days = $allocation?->start_date && $allocation?->end_date && $allocation->end_date >= $allocation->start_date
                    ? (int) $allocation->start_date->diffInDays($allocation->end_date) + 1 : null;
                $rows[] = array_merge($entry->toArray(), SafariFuelMetrics::calculate($entry->toArray(), isset($group[$index + 1]) ? $group[$index + 1]->toArray() : null), [
                    'driver' => $driver?->toArray(),
                    'driver_id' => $driver?->id,
                    'trip_days' => $days,
                ]);
            }
        }
        return [
            'monthly_prices' => $this->monthlyPrices(),
            'target_averages' => $this->monthlyTargets(),
            'can_manage_prices' => $request->user()->can('odometer-logs.update'),
            'allocations' => $allocations,
            'entries' => array_reverse($rows),
            'profiles' => DB::table('vehicle_fuel_profiles')->whereIn('vehicle_id', $allocations->pluck('vehicle_id'))->get(),
        ];
    }

    private function resolveLogoDataUri(): ?string
    {
        $path = Setting::get('logo');
        if (!is_string($path) || $path === '' || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) return null;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png'
        };
        return 'data:' . $mime . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($path));
    }

    public function save(Request $request, string $allocationType, int $allocationId)
    {
        $isLongTerm = $allocationType === 'long-term';
        abort_unless(in_array($allocationType, ['safari', 'long-term'], true), 404);
        $allocation = $isLongTerm ? LeaseAllocation::findOrFail($allocationId) : SafariAllocation::findOrFail($allocationId);
        if (!$isLongTerm) $this->authorize('view', $allocation);
        $allocationColumn = $isLongTerm ? 'lease_allocation_id' : 'safari_allocation_id';
        $existing = SafariFuelEntry::where($allocationColumn, $allocation->id)->first();
        abort_unless($request->user()->can($existing ? 'odometer-logs.update' : 'odometer-logs.create'), 403);
        $data = $request->validate([
            'filled_at' => ['required', 'date_format:Y-m-d'],
            'litres_filled' => ['required', 'numeric', 'min:0', 'max:10000'],
            'litres_filled_end' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'extra_litres' => ['required', 'numeric', 'min:0', 'max:10000'],
            'expected_average' => ['nullable', 'numeric', 'min:0.01', 'max:1000'],
            'start_km' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'end_km' => ['nullable', 'integer', 'gte:start_km', 'max:2147483647'],
        ]);
        DB::transaction(function () use ($allocation, $allocationColumn, $data, $request) {
            Vehicle::whereKey($allocation->vehicle_id)->lockForUpdate()->firstOrFail();
            $entry = SafariFuelEntry::where($allocationColumn, $allocation->id)->first();
            abort_unless($request->user()->can($entry ? 'odometer-logs.update' : 'odometer-logs.create'), 403);
            if ($entry && ($entry->vehicle_id != $allocation->vehicle_id || $entry->driver_id != $allocation->driver_id)) {
                throw ValidationException::withMessages(['safari' => 'Safari assignment changed. Restore the original vehicle and driver before editing this fuel record.']);
            }
            $others = SafariFuelEntry::where('vehicle_id', $allocation->vehicle_id)
                ->when($entry, fn($query) => $query->whereKey('!=', $entry->id))
                ->get();
            foreach ($others as $other) {
                $before = $other->start_km < $data['start_km'];
                if (
                    $other->start_km == $data['start_km'] ||
                    ($before && ($other->end_km > $data['start_km'] || $other->filled_at > $data['filled_at'])) ||
                    (!$before && (($data['end_km'] ?? $data['start_km']) > $other->start_km || $data['filled_at'] > $other->filled_at))
                ) {
                    throw ValidationException::withMessages(['start_km' => 'KM readings or refill date conflict with another safari for this vehicle.']);
                }
            }
            SafariFuelEntry::updateOrCreate([$allocationColumn => $allocation->id], array_merge($data, [
                'vehicle_id' => $allocation->vehicle_id,
                'driver_id' => $allocation->driver_id,
                'safari_allocation_id' => $allocationColumn === 'safari_allocation_id' ? $allocation->id : null,
                'lease_allocation_id' => $allocationColumn === 'lease_allocation_id' ? $allocation->id : null,
            ]));
        });
        return response()->json(['message' => 'Fuel entry saved.']);
    }

    public function destroy(Request $request, string $allocationType, int $allocationId)
    {
        abort_unless(in_array($allocationType, ['safari', 'long-term'], true), 404);
        $column = $allocationType === 'long-term' ? 'lease_allocation_id' : 'safari_allocation_id';
        $entry = SafariFuelEntry::where($column, $allocationId)->firstOrFail();
        $isTahir = strtolower(trim((string) $request->user()?->email)) === 'tahir@sher.co.tz';
        abort_unless($isTahir || $request->user()->can('odometer-logs.delete'), 403);
        $entry->delete();

        return response()->json(['message' => 'Fuel entry deleted.']);
    }
}
