<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\SafariAllocation;
use App\Models\Vehicle;
use App\Models\VehicleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleHistoryController extends Controller
{
    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'eventType' => ['sometimes', 'string'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'sort' => ['sometimes', 'in:asc,desc'],
        ]);

        $events = collect();

        $events->push([
            'eventType' => 'vehicle_registered',
            'eventDate' => $vehicle->created_at?->toDateString(),
            'eventAt' => $vehicle->created_at?->toISOString(),
            'title' => 'Vehicle registered',
            'details' => [
                'vehicleId' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicle_no,
                'plateNo' => $vehicle->plate_no,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
            ],
        ]);

        $allocations = SafariAllocation::query()
            ->with(['lead:id,booking_ref,client_company', 'driver:id,name'])
            ->where('vehicle_id', $vehicle->id)
            ->latest('id')
            ->get();

        foreach ($allocations as $allocation) {
            $events->push([
                'eventType' => 'vehicle_allocated',
                'eventDate' => $allocation->created_at?->toDateString(),
                'eventAt' => $allocation->created_at?->toISOString(),
                'title' => 'Vehicle allocated',
                'details' => [
                    'allocationId' => $allocation->id,
                    'leadId' => $allocation->lead_id,
                    'bookingRef' => $allocation->lead?->booking_ref,
                    'clientCompany' => $allocation->lead?->client_company,
                    'driverId' => $allocation->driver_id,
                    'driverName' => $allocation->driver?->name,
                    'status' => $allocation->status,
                    'notes' => $allocation->notes,
                ],
            ]);
        }

        $inspections = Inspection::query()
            ->with(['lead:id,booking_ref,client_company', 'user:id,name', 'items:id,inspection_id,status'])
            ->where('vehicle_id', $vehicle->id)
            ->latest('id')
            ->get();

        foreach ($inspections as $inspection) {
            $okCount = $inspection->items->where('status', 'OK')->count();
            $nokCount = $inspection->items->where('status', 'NOK')->count();

            $isPre = $inspection->type === 'pre_departure';
            $title = $isPre ? 'Pre checklist submitted' : 'Post checklist submitted';
            $eventType = $isPre ? 'pre_checklist' : 'post_checklist';

            $events->push([
                'eventType' => $eventType,
                'eventDate' => $inspection->created_at?->toDateString(),
                'eventAt' => $inspection->created_at?->toISOString(),
                'title' => $title,
                'details' => [
                    'inspectionId' => $inspection->id,
                    'type' => $inspection->type,
                    'leadId' => $inspection->lead_id,
                    'bookingRef' => $inspection->lead?->booking_ref,
                    'clientCompany' => $inspection->lead?->client_company,
                    'submittedBy' => $inspection->user?->name,
                    'okCount' => $okCount,
                    'nokCount' => $nokCount,
                    'remarks' => $inspection->remarks,
                ],
            ]);
        }

        $services = VehicleService::query()
            ->where('vehicle_id', $vehicle->id)
            ->latest('id')
            ->get();

        foreach ($services as $service) {
            $serviceOutAt = $service->service_date_out ? Carbon::parse($service->service_date_out)->startOfDay() : null;

            $events->push([
                'eventType' => 'service_out',
                'eventDate' => $serviceOutAt?->toDateString(),
                'eventAt' => $serviceOutAt?->toISOString(),
                'title' => 'Vehicle sent to service',
                'details' => [
                    'serviceId' => $service->id,
                    'serviceCenter' => $service->service_center,
                    'serviceType' => $service->service_type,
                    'odometerOut' => $service->odometer_out,
                    'fuelOut' => $service->fuel_out,
                    'status' => $service->status,
                    'notes' => $service->notes,
                ],
            ]);

            if ($service->service_date_in) {
                $serviceInAt = Carbon::parse($service->service_date_in)->startOfDay();

                $events->push([
                    'eventType' => 'service_returned',
                    'eventDate' => $serviceInAt->toDateString(),
                    'eventAt' => $serviceInAt->toISOString(),
                    'title' => 'Vehicle returned from service',
                    'details' => [
                        'serviceId' => $service->id,
                        'serviceCenter' => $service->service_center,
                        'serviceType' => $service->service_type,
                        'odometerIn' => $service->odometer_in,
                        'fuelIn' => $service->fuel_in,
                        'cost' => $service->cost,
                        'notes' => $service->notes,
                    ],
                ]);
            }
        }

        $timeline = $events->filter(fn(array $event): bool => !empty($event['eventAt']));

        if (!empty($validated['eventType'])) {
            $types = collect(explode(',', (string) $validated['eventType']))
                ->map(fn(string $type): string => trim($type))
                ->filter()
                ->values();

            if ($types->isNotEmpty()) {
                $timeline = $timeline->filter(fn(array $event): bool => $types->contains((string) $event['eventType']));
            }
        }

        if (!empty($validated['from'])) {
            $from = Carbon::parse((string) $validated['from'])->startOfDay();
            $timeline = $timeline->filter(fn(array $event): bool => Carbon::parse((string) $event['eventAt'])->greaterThanOrEqualTo($from));
        }

        if (!empty($validated['to'])) {
            $to = Carbon::parse((string) $validated['to'])->endOfDay();
            $timeline = $timeline->filter(fn(array $event): bool => Carbon::parse((string) $event['eventAt'])->lessThanOrEqualTo($to));
        }

        $sort = $validated['sort'] ?? 'desc';
        $timeline = ($sort === 'asc')
            ? $timeline->sortBy('eventAt')->values()
            : $timeline->sortByDesc('eventAt')->values();

        return response()->json([
            'message' => 'Vehicle history fetched successfully.',
            'vehicle' => [
                'id' => $vehicle->id,
                'vehicleNo' => $vehicle->vehicle_no,
                'plateNo' => $vehicle->plate_no,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'status' => $vehicle->status,
            ],
            'filters' => [
                'eventType' => $validated['eventType'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'sort' => $sort,
            ],
            'history' => $timeline,
        ]);
    }
}
