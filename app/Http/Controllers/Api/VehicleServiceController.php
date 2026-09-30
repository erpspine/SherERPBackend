<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\VehicleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = $this->filteredServiceQuery(request())->get();

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
            'serviceItems' => ['nullable', 'array'],
            'serviceItems.*.service' => ['required_with:serviceItems', 'string', 'max:255'],
            'serviceItems.*.details' => ['nullable', 'string', 'max:1000'],
            'serviceItems.*.cost' => ['nullable', 'numeric', 'min:0'],
            'serviceDate' => ['required_without:serviceDateOut', 'date'],
            'serviceDateOut' => ['nullable', 'date'],
            'serviceDateIn' => ['nullable', 'date'],
            'partsReplaced' => ['nullable', 'string'],
            'odometerOut' => ['nullable', 'integer', 'min:0'],
            'odometerIn' => ['nullable', 'integer', 'min:0'],
            'fuelOut' => ['nullable', 'integer', 'min:0', 'max:100'],
            'fuelIn' => ['nullable', 'integer', 'min:0', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['In Service', 'Returned', 'Cancelled'])],
        ]);

        $status = $validated['status'] ?? 'In Service';
        $serviceItems = $this->normalizeServiceItems($validated['serviceItems'] ?? null);
        $lineItemsCost = $this->sumServiceItemCost($serviceItems);

        if ($serviceItems !== []) {
            if (! isset($validated['serviceType']) || trim((string) $validated['serviceType']) === '') {
                $validated['serviceType'] = $this->summarizeServiceItems($serviceItems);
            }

            if (! array_key_exists('cost', $validated) || $validated['cost'] === null) {
                $validated['cost'] = $lineItemsCost;
            }

            $validated['serviceItems'] = $serviceItems;
        }

        $payload = $this->mapRequestToDb($validated);
        if (! array_key_exists('service_date', $payload) && array_key_exists('service_date_out', $payload)) {
            $payload['service_date'] = $payload['service_date_out'];
        }
        $payload['status'] = $status;
        $payload['service_date_out'] = $payload['service_date'] ?? $payload['service_date_out'] ?? null;

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
            'serviceItems' => ['sometimes', 'nullable', 'array'],
            'serviceItems.*.service' => ['required_with:serviceItems', 'string', 'max:255'],
            'serviceItems.*.details' => ['nullable', 'string', 'max:1000'],
            'serviceItems.*.cost' => ['nullable', 'numeric', 'min:0'],
            'serviceDate' => ['sometimes', 'date'],
            'serviceDateOut' => ['sometimes', 'nullable', 'date'],
            'serviceDateIn' => ['sometimes', 'nullable', 'date'],
            'partsReplaced' => ['sometimes', 'nullable', 'string'],
            'odometerOut' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'odometerIn' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'fuelOut' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'fuelIn' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in(['In Service', 'Returned', 'Cancelled'])],
        ]);

        if (array_key_exists('serviceItems', $validated)) {
            $serviceItems = $this->normalizeServiceItems($validated['serviceItems']);
            $validated['serviceItems'] = $serviceItems;

            if (
                (
                    ! array_key_exists('serviceType', $validated)
                    || trim((string) ($validated['serviceType'] ?? '')) === ''
                )
                && $serviceItems !== []
            ) {
                $validated['serviceType'] = $this->summarizeServiceItems($serviceItems);
            }

            if (
                (
                    ! array_key_exists('cost', $validated)
                    || $validated['cost'] === null
                )
                && $serviceItems !== []
            ) {
                $validated['cost'] = $this->sumServiceItemCost($serviceItems);
            }
        }

        $payload = $this->mapRequestToDb($validated);
        if (array_key_exists('service_date', $payload)) {
            $payload['service_date_out'] = $payload['service_date'];
        }

        $vehicleService->update($payload);
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

    public function exportExcel(Request $request): StreamedResponse
    {
        $rows = $this->filteredServiceQuery($request)
            ->get()
            ->map(fn(VehicleService $service): array => $this->transformVehicleService($service))
            ->values();

        $filename = 'vehicle-services-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Vehicle No',
                'Plate No',
                'Service Date',
                'Service Center',
                'Service Type',
                'Line Items',
                'Odometer Out',
                'Odometer In',
                'Fuel Out',
                'Fuel In',
                'Cost',
                'Status',
                'Notes',
            ]);

            foreach ($rows as $row) {
                $lineItems = is_array($row['serviceItems'] ?? null)
                    ? collect($row['serviceItems'])
                    ->map(function (array $item): string {
                        $name = trim((string) ($item['service'] ?? ''));
                        $details = trim((string) ($item['details'] ?? ''));
                        $cost = $item['cost'] ?? null;

                        $parts = array_filter([
                            $name,
                            $details !== '' ? $details : null,
                            is_numeric($cost) ? 'USD ' . number_format((float) $cost, 2) : null,
                        ]);

                        return implode(' - ', $parts);
                    })
                    ->filter()
                    ->implode(' | ')
                    : '';

                fputcsv($output, [
                    (string) ($row['vehicleNo'] ?? ''),
                    (string) ($row['plateNo'] ?? ''),
                    (string) ($row['serviceDate'] ?? ''),
                    (string) ($row['serviceCenter'] ?? ''),
                    (string) ($row['serviceType'] ?? ''),
                    $lineItems,
                    $row['odometerOut'] ?? '',
                    $row['odometerIn'] ?? '',
                    $row['fuelOut'] ?? '',
                    $row['fuelIn'] ?? '',
                    (string) ($row['cost'] ?? ''),
                    (string) ($row['status'] ?? ''),
                    (string) ($row['notes'] ?? ''),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $rows = $this->filteredServiceQuery($request)
            ->get()
            ->map(fn(VehicleService $service): array => $this->transformVehicleService($service))
            ->values();

        $pdf = Pdf::loadView('vehicle-services.pdf', [
            'rows' => $rows,
            'company' => [
                'name' => Setting::get('company_name', config('app.name')),
                'email' => Setting::get('company_email'),
                'phone' => Setting::get('company_phone'),
            ],
            'generatedAt' => now()->format('d M Y H:i'),
            'stats' => [
                'total' => $rows->count(),
                'inService' => $rows->where('status', 'In Service')->count(),
                'returned' => $rows->where('status', 'Returned')->count(),
                'cancelled' => $rows->where('status', 'Cancelled')->count(),
                'totalCost' => $rows->sum(fn(array $row): float => (float) ($row['cost'] ?? 0)),
            ],
        ])->setPaper('a4', 'landscape');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $pageText = 'Page {PAGE_NUM} of {PAGE_COUNT}';
        $fontSize = 8;
        $x = $canvas->get_width() - $fontMetrics->getTextWidth($pageText, $font, $fontSize) - 28;
        $y = $canvas->get_height() - 22;
        $canvas->page_text($x, $y, $pageText, $font, $fontSize, [0.42, 0.45, 0.50]);

        $filename = 'vehicle-services-' . now()->format('Ymd-His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
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
            'serviceItems' => 'service_items',
            'serviceDate' => 'service_date',
            'serviceDateOut' => 'service_date_out',
            'serviceDateIn' => 'service_date_in',
            'partsReplaced' => 'parts_replaced',
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
        $serviceItems = $this->normalizeServiceItems($service->service_items);

        return [
            'id' => $service->id,
            'vehicleId' => $service->vehicle_id,
            'vehicleNo' => $service->vehicle?->vehicle_no,
            'plateNo' => $service->vehicle?->plate_no,
            'serviceCenter' => $service->service_center,
            'serviceType' => $service->service_type,
            'serviceItems' => $serviceItems,
            'serviceDate' => optional($service->service_date ?? $service->service_date_out)->format('Y-m-d'),
            'serviceDateOut' => optional($service->service_date_out)->format('Y-m-d'),
            'serviceDateIn' => optional($service->service_date_in)->format('Y-m-d'),
            'partsReplaced' => $service->parts_replaced,
            'odometerOut' => $service->odometer_out,
            'odometerIn' => $service->odometer_in,
            'fuelOut' => $service->fuel_out,
            'fuelIn' => $service->fuel_in,
            'cost' => $service->cost,
            'lineItemsCost' => $this->sumServiceItemCost($serviceItems),
            'notes' => $service->notes,
            'status' => $service->status,
            'createdAt' => $service->created_at?->toISOString(),
            'updatedAt' => $service->updated_at?->toISOString(),
        ];
    }

    private function filteredServiceQuery(Request $request)
    {
        $query = VehicleService::query()->with('vehicle');

        if ($request->filled('vehicleId')) {
            $query->where('vehicle_id', (int) $request->input('vehicleId'));
        }

        $status = trim((string) $request->input('status', ''));
        if ($status !== '' && in_array($status, ['In Service', 'Returned', 'Cancelled'], true)) {
            $query->where('status', $status);
        }

        $dateFrom = trim((string) $request->input('dateFrom', ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $query->whereDate('service_date', '>=', $dateFrom);
        }

        $dateTo = trim((string) $request->input('dateTo', ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $query->whereDate('service_date', '<=', $dateTo);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('service_center', 'like', '%' . $search . '%')
                    ->orWhere('service_type', 'like', '%' . $search . '%')
                    ->orWhere('parts_replaced', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhere('service_items', 'like', '%' . $search . '%')
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search): void {
                        $vehicleQuery
                            ->where('vehicle_no', 'like', '%' . $search . '%')
                            ->orWhere('plate_no', 'like', '%' . $search . '%')
                            ->orWhere('make', 'like', '%' . $search . '%')
                            ->orWhere('model', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->latest('service_date')->latest('id');
    }

    /**
     * @param mixed $items
     * @return array<int, array{service: string, details: string, cost: float|null}>
     */
    private function normalizeServiceItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(function ($item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $service = trim((string) ($item['service'] ?? ''));
                $details = trim((string) ($item['details'] ?? ''));
                $rawCost = $item['cost'] ?? null;
                $cost = is_numeric($rawCost) ? (float) $rawCost : null;

                if ($service === '' && $details === '' && $cost === null) {
                    return null;
                }

                return [
                    'service' => $service,
                    'details' => $details,
                    'cost' => $cost,
                ];
            })
            ->filter(fn($item): bool => is_array($item) && ($item['service'] ?? '') !== '')
            ->values()
            ->all();
    }

    /**
     * @param array<int, array{service: string, details: string, cost: float|null}> $items
     */
    private function summarizeServiceItems(array $items): string
    {
        $labels = collect($items)
            ->pluck('service')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($labels->isEmpty()) {
            return '';
        }

        return $labels->take(3)->implode(', ') . ($labels->count() > 3 ? ' +' . ($labels->count() - 3) . ' more' : '');
    }

    /**
     * @param array<int, array{service: string, details: string, cost: float|null}> $items
     */
    private function sumServiceItemCost(array $items): float
    {
        return collect($items)->sum(function (array $item): float {
            return is_numeric($item['cost'] ?? null) ? (float) $item['cost'] : 0.0;
        });
    }
}
