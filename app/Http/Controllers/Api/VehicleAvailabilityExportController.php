<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SafariAllocation;
use App\Models\Vehicle;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleAvailabilityExportController extends Controller
{
    public function pdf(Request $request): Response
    {
        $month = $this->resolveMonth($request);
        [$start, $end] = $this->resolveMonthRange($month);

        $rows = $this->buildRows($start, $end);
        $company = [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
            'tax_registration_number' => Setting::get('tax_registration_number'),
        ];

        $pdf = Pdf::loadView('vehicle-availability', [
            'rows' => $rows,
            'monthLabel' => $start->format('F Y'),
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('vehicle-availability-' . $month . '.pdf');
    }

    public function excel(Request $request): StreamedResponse
    {
        $month = $this->resolveMonth($request);
        [$start, $end] = $this->resolveMonthRange($month);

        $rows = $this->buildRows($start, $end);
        $filename = 'vehicle-availability-' . $month . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            // UTF-8 BOM for better Excel compatibility.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Vehicle No',
                'Plate No',
                'Make',
                'Model',
                'Status',
                'Booked Days',
                'Total Days',
                'Availability %',
                'Bookings',
            ]);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['vehicleNo'],
                    $row['plateNo'],
                    $row['make'],
                    $row['model'],
                    $row['status'],
                    $row['bookedDays'],
                    $row['totalDays'],
                    $row['availabilityPercent'],
                    $row['bookingsSummary'],
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveMonthRange(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return [$start, $end];
    }

    private function resolveMonth(Request $request): string
    {
        $month = (string) $request->query('month', '');

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return now()->format('Y-m');
        }

        return $month;
    }

    /**
     * @return array<int, array<string, int|float|string>>
     */
    private function buildRows(Carbon $start, Carbon $end): array
    {
        $vehicles = Vehicle::query()
            ->orderBy('vehicle_no')
            ->orderBy('id')
            ->get(['id', 'vehicle_no', 'plate_no', 'make', 'model']);

        $allocations = SafariAllocation::query()
            ->with([
                'lead:id,booking_ref,start_date,end_date,route_parks',
                'driver:id,name',
            ])
            ->whereHas('lead', function ($query) use ($start, $end): void {
                $query
                    ->whereDate('start_date', '<=', $end->toDateString())
                    ->whereDate('end_date', '>=', $start->toDateString());
            })
            ->get();

        $allocationsByVehicle = $allocations->groupBy('vehicle_id');
        $totalDays = (int) $start->diffInDays($end) + 1;

        return $vehicles->map(function (Vehicle $vehicle) use ($allocationsByVehicle, $start, $end, $totalDays): array {
            $vehicleAllocations = $allocationsByVehicle->get($vehicle->id, collect());
            $bookedDates = [];
            $bookings = [];

            foreach ($vehicleAllocations as $allocation) {
                $lead = $allocation->lead;
                if ($lead === null || $lead->start_date === null || $lead->end_date === null) {
                    continue;
                }

                $leadStart = Carbon::parse($lead->start_date)->startOfDay();
                $leadEnd = Carbon::parse($lead->end_date)->endOfDay();

                if ($leadEnd->lt($start) || $leadStart->gt($end)) {
                    continue;
                }

                $overlapStart = $leadStart->greaterThan($start) ? $leadStart->copy() : $start->copy();
                $overlapEnd = $leadEnd->lessThan($end) ? $leadEnd->copy() : $end->copy();

                for ($cursor = $overlapStart->copy(); $cursor->lte($overlapEnd); $cursor->addDay()) {
                    $bookedDates[$cursor->toDateString()] = true;
                }

                $bookingRef = (string) ($lead->booking_ref ?: '-');
                $driverName = (string) ($allocation->driver?->name ?: 'Unknown');
                $route = (string) ($lead->route_parks ?: '-');

                $bookings[] = sprintf(
                    '%s (%s to %s) - Driver: %s - Route: %s',
                    $bookingRef,
                    $overlapStart->toDateString(),
                    $overlapEnd->toDateString(),
                    $driverName,
                    $route
                );
            }

            $bookedDays = count($bookedDates);
            $availabilityPercent = $totalDays > 0
                ? round((($totalDays - $bookedDays) / $totalDays) * 100, 2)
                : 0.0;

            $status = 'Available';
            if ($bookedDays >= $totalDays && $totalDays > 0) {
                $status = 'Fully Booked';
            } elseif ($bookedDays > 0) {
                $status = 'Partially Booked';
            }

            return [
                'vehicleNo' => (string) ($vehicle->vehicle_no ?: '-'),
                'plateNo' => (string) ($vehicle->plate_no ?: '-'),
                'make' => (string) ($vehicle->make ?: '-'),
                'model' => (string) ($vehicle->model ?: '-'),
                'status' => $status,
                'bookedDays' => $bookedDays,
                'totalDays' => $totalDays,
                'availabilityPercent' => $availabilityPercent,
                'bookingsSummary' => empty($bookings) ? '-' : implode(' | ', $bookings),
            ];
        })->values()->all();
    }
}
