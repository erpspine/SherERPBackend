<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\Lead;
use App\Models\LeaseAllocation;
use App\Models\SafariAllocation;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class JobCardController extends Controller
{
    private const TYPES = ['Safari', 'Safari - Daily', 'Safari - Monthly', 'Safari - Yearly', 'Long Term Lease', 'Test Drive', 'Service', 'Client Viewing', 'Others'];

    private const STATUSES = ['Open', 'Closed'];

    private const REASON_TYPES = ['Test Drive', 'Service', 'Others'];

    private const VEHICLE_RUN_TYPES = ['Test Drive', 'Service', 'Client Viewing', 'Others'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JobCard::class);

        $query = JobCard::query()->with(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract'])->latest('id');

        if ($request->user()?->hasRole('Driver')) {
            $query->whereIn(
                'lead_id',
                SafariAllocation::query()
                    ->where('driver_id', $request->user()->id)
                    ->select('lead_id')
            );
        }

        $jobCards = $query->get();

        return response()->json([
            'message' => 'Job cards fetched successfully.',
            'jobCards' => $jobCards->map(fn(JobCard $jobCard): array => $this->transform($jobCard))->values(),
        ]);
    }

    public function show(JobCard $jobCard): JsonResponse
    {
        $this->authorize('view', $jobCard);

        $jobCard->load(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract']);

        return response()->json([
            'message' => 'Job card fetched successfully.',
            'jobCard' => $this->transform($jobCard),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', JobCard::class);

        $validated = $request->validate($this->rules($request));

        $type = $validated['type'];
        if ($this->isSafariType($type) && empty($validated['leadId'])) {
            return response()->json([
                'message' => 'leadId is required for Safari job cards.',
            ], 422);
        }

        if ($this->isLeaseType($type) && empty($validated['leaseAllocationId'])) {
            return response()->json([
                'message' => 'leaseAllocationId is required for Long Term Lease job cards.',
            ], 422);
        }

        if ($this->isLeaseType($type) && ! empty($validated['leaseAllocationId'])) {
            $duplicate = $this->findDuplicateLeaseAllocationJobCard((int) $validated['leaseAllocationId']);
            if ($duplicate !== null) {
                return response()->json([
                    'message' => 'A job card already exists for this lease allocation (' . ($duplicate->job_card_no ?: ('ID ' . $duplicate->id)) . ').',
                ], 422);
            }
        }

        $payload = $this->applyTypeSpecificPayload($validated, $type, null);

        $jobCard = JobCard::create($payload);

        $jobCard->update([
            'job_card_no' => $this->generateJobCardNo($jobCard->id),
        ]);

        $jobCard->load(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract']);

        return response()->json([
            'message' => 'Job card created successfully.',
            'jobCard' => $this->transform($jobCard),
        ], 201);
    }

    public function update(Request $request, JobCard $jobCard): JsonResponse
    {
        $this->authorize('update', $jobCard);

        $validated = $request->validate($this->rules($request, isUpdate: true, jobCard: $jobCard));

        $type = $validated['type'] ?? $jobCard->type;
        $leadId = array_key_exists('leadId', $validated) ? $validated['leadId'] : $jobCard->lead_id;
        $vehicleId = array_key_exists('vehicleId', $validated) ? $validated['vehicleId'] : $jobCard->vehicle_id;
        $leaseContractId = array_key_exists('leaseContractId', $validated) ? $validated['leaseContractId'] : $jobCard->lease_contract_id;
        $leaseAllocationId = array_key_exists('leaseAllocationId', $validated) ? $validated['leaseAllocationId'] : $jobCard->lease_allocation_id;

        if ($this->isSafariType($type) && empty($leadId)) {
            return response()->json([
                'message' => 'leadId is required for Safari job cards.',
            ], 422);
        }

        if ($this->isLeaseType($type) && empty($leaseAllocationId)) {
            return response()->json([
                'message' => 'leaseAllocationId is required for Long Term Lease job cards.',
            ], 422);
        }

        if ($this->isLeaseType($type) && ! empty($leaseAllocationId)) {
            $duplicate = $this->findDuplicateLeaseAllocationJobCard(
                (int) $leaseAllocationId,
                (int) $jobCard->id,
            );
            if ($duplicate !== null) {
                return response()->json([
                    'message' => 'A job card already exists for this lease allocation (' . ($duplicate->job_card_no ?: ('ID ' . $duplicate->id)) . ').',
                ], 422);
            }
        }

        $payload = $this->applyTypeSpecificPayload($validated, $type, $jobCard);

        $jobCard->update($payload);

        $jobCard->load(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract']);

        return response()->json([
            'message' => 'Job card updated successfully.',
            'jobCard' => $this->transform($jobCard),
        ]);
    }

    public function destroy(JobCard $jobCard): JsonResponse
    {
        $this->authorize('delete', $jobCard);

        $jobCard->delete();

        return response()->json([
            'message' => 'Job card deleted successfully.',
        ]);
    }

    public function pdf(JobCard $jobCard): Response
    {
        $this->authorize('view', $jobCard);

        $jobCard->load(['lead', 'vehicle']);

        $company = [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
            'tax_registration_number' => Setting::get('tax_registration_number'),
        ];

        $transformedJobCard = $this->transform($jobCard);

        $transformedJobCard['groupName'] = null;
        if ($jobCard->lead_id) {
            $latestQuotation = \App\Models\Quotation::query()
                ->where('lead_id', $jobCard->lead_id)
                ->latest('quote_date')
                ->latest('id')
                ->first();
            $transformedJobCard['groupName'] = $latestQuotation?->group_name;
        }

        if ($this->isSafariType($jobCard->type) && $jobCard->lead_id) {
            $allocations = SafariAllocation::query()
                ->with(['vehicle', 'driver'])
                ->where('lead_id', $jobCard->lead_id)
                ->orderBy('start_date')
                ->orderBy('id')
                ->get();

            $transformedJobCard['allocatedVehicles'] = $allocations
                ->map(function (SafariAllocation $allocation): array {
                    return [
                        'vehicleNo' => (string) ($allocation->vehicle?->vehicle_no ?? ''),
                        'plateNo' => (string) ($allocation->vehicle?->plate_no ?? ''),
                        'driverName' => (string) ($allocation->driver?->name ?? ''),
                    ];
                })
                ->filter(fn(array $item): bool => trim($item['vehicleNo']) !== '' || trim($item['plateNo']) !== '' || trim($item['driverName']) !== '')
                ->unique(fn(array $item): string => implode('|', [
                    strtoupper(trim($item['vehicleNo'])),
                    strtoupper(trim($item['plateNo'])),
                    strtoupper(trim($item['driverName'])),
                ]))
                ->values()
                ->all();
        }

        $pdf = Pdf::loadView('job-cards.pdf', [
            'jobCard' => $transformedJobCard,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'job-card-' . $jobCard->id . '.pdf';

        return $pdf->download($filename);
    }

    private function generateJobCardNo(int $id): string
    {
        return 'JC-' . now()->format('Y') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, bool $isUpdate = false, ?JobCard $jobCard = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $requestedType = $request->input('type');
        $effectiveType = is_string($requestedType) && $requestedType !== '' ? $requestedType : $jobCard?->type;

        $leadIdRules = ['sometimes', 'nullable'];

        if ($this->isSafariType($effectiveType)) {
            if (! $isUpdate) {
                $leadIdRules[] = 'required';
            }

            $leadIdRules[] = 'integer';
            $leadIdRules[] = 'exists:leads,id';
        }

        return [
            'leadId' => $leadIdRules,
            'vehicleId' => ['sometimes', 'nullable', 'integer', 'exists:vehicles,id'],
            'leaseContractId' => ['sometimes', 'nullable', 'integer', 'exists:lease_contracts,id'],
            'leaseAllocationId' => ['sometimes', 'nullable', 'integer', 'exists:lease_allocations,id'],
            'type' => [$required, 'string', Rule::in(self::TYPES)],
            'status' => ['sometimes', 'string', Rule::in(self::STATUSES)],
            'safariStartDate' => ['sometimes', 'nullable', 'date'],
            'safariEndDate' => ['sometimes', 'nullable', 'date'],
            'timeOut' => ['sometimes', 'nullable', 'date_format:H:i'],
            'timeIn' => ['sometimes', 'nullable', 'date_format:H:i'],
            'routeSummary' => ['sometimes', 'nullable', 'string', 'max:500'],
            'routeItinerary' => ['sometimes', 'nullable', 'array'],
            'routeItinerary.*.date' => ['sometimes', 'nullable', 'string', 'max:100'],
            'routeItinerary.*.dayDescription' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'routeItinerary.*.allowancePerDay' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'additionalDetails' => ['sometimes', 'nullable', 'string'],
            'numberOfDays' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:365'],
            'pickupLocation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dropoffLocation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bookingReferenceNo' => ['sometimes', 'nullable', 'string', 'max:50'],
            'tourOperatorClientName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contactPerson' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contactNumber' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contactEmail' => ['sometimes', 'nullable', 'email', 'max:255'],
            'adults' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'children' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:120'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'clientDetails' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'kms' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'odometerOut' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'odometerIn' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'mileage' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'fuelGaugeOut' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'fuelGaugeIn' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'approximateFuelUsed' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'driverDetails' => ['sometimes', 'nullable', 'string', 'max:255'],
            'driverAllowance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function applyTypeSpecificPayload(array $validated, string $type, ?JobCard $jobCard): array
    {
        $payload = [
            'lead_id' => array_key_exists('leadId', $validated) ? $validated['leadId'] : $jobCard?->lead_id,
            'vehicle_id' => array_key_exists('vehicleId', $validated) ? $validated['vehicleId'] : $jobCard?->vehicle_id,
            'lease_contract_id' => array_key_exists('leaseContractId', $validated) ? $validated['leaseContractId'] : $jobCard?->lease_contract_id,
            'lease_allocation_id' => array_key_exists('leaseAllocationId', $validated) ? $validated['leaseAllocationId'] : $jobCard?->lease_allocation_id,
            'type' => $type,
            'status' => array_key_exists('status', $validated) ? $validated['status'] : ($jobCard?->status ?? 'Open'),
            'safari_start_date' => array_key_exists('safariStartDate', $validated) ? $validated['safariStartDate'] : optional($jobCard?->safari_start_date)->format('Y-m-d'),
            'safari_end_date' => array_key_exists('safariEndDate', $validated) ? $validated['safariEndDate'] : optional($jobCard?->safari_end_date)->format('Y-m-d'),
            'time_out' => array_key_exists('timeOut', $validated) ? $validated['timeOut'] : ($jobCard?->time_out ? date('H:i', strtotime((string) $jobCard->time_out)) : null),
            'time_in' => array_key_exists('timeIn', $validated) ? $validated['timeIn'] : ($jobCard?->time_in ? date('H:i', strtotime((string) $jobCard->time_in)) : null),
            'route_summary' => array_key_exists('routeSummary', $validated) ? $validated['routeSummary'] : $jobCard?->route_summary,
            'additional_details' => array_key_exists('additionalDetails', $validated) ? $validated['additionalDetails'] : $jobCard?->additional_details,
            'number_of_days' => array_key_exists('numberOfDays', $validated) ? $validated['numberOfDays'] : $jobCard?->number_of_days,
            'pickup_location' => array_key_exists('pickupLocation', $validated) ? $validated['pickupLocation'] : $jobCard?->pickup_location,
            'dropoff_location' => array_key_exists('dropoffLocation', $validated) ? $validated['dropoffLocation'] : $jobCard?->dropoff_location,
            'booking_reference_no' => array_key_exists('bookingReferenceNo', $validated) ? $validated['bookingReferenceNo'] : $jobCard?->booking_reference_no,
            'tour_operator_client_name' => array_key_exists('tourOperatorClientName', $validated) ? $validated['tourOperatorClientName'] : $jobCard?->tour_operator_client_name,
            'contact_person' => array_key_exists('contactPerson', $validated) ? $validated['contactPerson'] : $jobCard?->contact_person,
            'contact_number' => array_key_exists('contactNumber', $validated) ? $validated['contactNumber'] : $jobCard?->contact_number,
            'contact_email' => array_key_exists('contactEmail', $validated) ? $validated['contactEmail'] : $jobCard?->contact_email,
            'adults' => array_key_exists('adults', $validated) ? $validated['adults'] : $jobCard?->adults,
            'children' => array_key_exists('children', $validated) ? $validated['children'] : $jobCard?->children,
            'nationality' => array_key_exists('nationality', $validated) ? $validated['nationality'] : $jobCard?->nationality,
            'reason' => array_key_exists('reason', $validated) ? $validated['reason'] : $jobCard?->reason,
            'client_details' => array_key_exists('clientDetails', $validated) ? $validated['clientDetails'] : $jobCard?->client_details,
            'location' => array_key_exists('location', $validated) ? $validated['location'] : $jobCard?->location,
            'kms' => array_key_exists('kms', $validated) ? $validated['kms'] : $jobCard?->kms,
            'odometer_out' => array_key_exists('odometerOut', $validated) ? $validated['odometerOut'] : $jobCard?->odometer_out,
            'odometer_in' => array_key_exists('odometerIn', $validated) ? $validated['odometerIn'] : $jobCard?->odometer_in,
            'mileage' => array_key_exists('mileage', $validated) ? $validated['mileage'] : $jobCard?->mileage,
            'fuel_gauge_out' => array_key_exists('fuelGaugeOut', $validated) ? $validated['fuelGaugeOut'] : $jobCard?->fuel_gauge_out,
            'fuel_gauge_in' => array_key_exists('fuelGaugeIn', $validated) ? $validated['fuelGaugeIn'] : $jobCard?->fuel_gauge_in,
            'approximate_fuel_used' => array_key_exists('approximateFuelUsed', $validated) ? $validated['approximateFuelUsed'] : $jobCard?->approximate_fuel_used,
            'driver_details' => array_key_exists('driverDetails', $validated) ? $validated['driverDetails'] : $jobCard?->driver_details,
            'driver_allowance' => array_key_exists('driverAllowance', $validated) ? $validated['driverAllowance'] : $jobCard?->driver_allowance,
            'route_itinerary' => $jobCard?->route_itinerary,
            'guide_language' => $jobCard?->guide_language,
        ];

        $manualRouteItinerary = $this->normalizeRouteItinerary($validated['routeItinerary'] ?? null);

        if ($this->isSafariType($type)) {
            $payload['route_itinerary'] = ! empty($manualRouteItinerary)
                ? $manualRouteItinerary
                : $this->resolveSafariItinerary(
                    $payload['lead_id'] ? (int) $payload['lead_id'] : null,
                    $jobCard
                );

            $payload['lease_contract_id'] = null;
            $payload['lease_allocation_id'] = null;

            $payload['reason'] = null;
            $payload['client_details'] = null;
            $payload['location'] = null;
            $payload['kms'] = null;
            $payload['odometer_out'] = null;
            $payload['odometer_in'] = null;
            $payload['mileage'] = null;
            $payload['fuel_gauge_out'] = null;
            $payload['fuel_gauge_in'] = null;
            $payload['approximate_fuel_used'] = null;
            $payload['driver_details'] = null;
        } elseif ($this->isLeaseType($type)) {
            $payload['lead_id'] = null;
            $payload['route_itinerary'] = ! empty($manualRouteItinerary)
                ? $manualRouteItinerary
                : $this->normalizeRouteItinerary($jobCard?->route_itinerary);

            $allocation = $payload['lease_allocation_id']
                ? LeaseAllocation::query()->with(['vehicle', 'driver', 'leaseContract'])->find($payload['lease_allocation_id'])
                : null;

            if ($allocation) {
                // Allocation is the source of truth for vehicle, dates, itinerary.
                $payload['lease_contract_id'] = $allocation->lease_contract_id;
                $payload['vehicle_id'] = $allocation->vehicle_id;

                if ($allocation->start_date) {
                    $payload['safari_start_date'] = \Carbon\Carbon::parse($allocation->start_date)->toDateString();
                }
                if ($allocation->end_date) {
                    $payload['safari_end_date'] = \Carbon\Carbon::parse($allocation->end_date)->toDateString();
                }
                if ($allocation->start_date && $allocation->end_date) {
                    $payload['number_of_days'] = \Carbon\Carbon::parse($allocation->start_date)
                        ->diffInDays(\Carbon\Carbon::parse($allocation->end_date)) + 1;
                }

                $itineraryText = trim((string) ($allocation->itinerary ?? ''));
                if ($itineraryText !== '') {
                    // Keep route_summary short enough for DB column constraints.
                    if (empty($payload['route_summary'])) {
                        $payload['route_summary'] = Str::limit($itineraryText, 240, '...');
                    }

                    if (empty($payload['additional_details'])) {
                        $payload['additional_details'] = $itineraryText;
                    }
                }

                if (empty($payload['route_itinerary'])) {
                    $payload['route_itinerary'] = $this->normalizeRouteItinerary($allocation->itinerary_items);
                }

                $contract = $allocation->leaseContract;
                if ($contract) {
                    $payload['tour_operator_client_name'] = $payload['tour_operator_client_name'] ?: $contract->client_name;
                }

                if ($allocation->driver) {
                    $payload['driver_details'] = $allocation->driver->name;
                }
            } else {
                $payload['lease_contract_id'] = null;
            }

            $payload['reason'] = null;
            $payload['client_details'] = null;
            $payload['location'] = null;
            $payload['kms'] = null;
            $payload['booking_reference_no'] = null;
            $payload['contact_person'] = null;
            $payload['contact_number'] = null;
            $payload['contact_email'] = null;
            $payload['adults'] = null;
            $payload['children'] = null;
            $payload['nationality'] = null;
            $payload['pickup_location'] = null;
            $payload['dropoff_location'] = null;
        } else {
            $payload['lead_id'] = null;
            $payload['lease_contract_id'] = null;
            $payload['lease_allocation_id'] = null;

            if (! in_array($type, self::REASON_TYPES, true)) {
                $payload['reason'] = null;
            }

            if ($type !== 'Client Viewing') {
                $payload['client_details'] = null;
                $payload['location'] = null;
                $payload['kms'] = null;
            }

            if (! in_array($type, self::VEHICLE_RUN_TYPES, true)) {
                $payload['odometer_out'] = null;
                $payload['odometer_in'] = null;
                $payload['mileage'] = null;
                $payload['fuel_gauge_out'] = null;
                $payload['fuel_gauge_in'] = null;
                $payload['approximate_fuel_used'] = null;
                $payload['driver_details'] = null;
            }

            $payload['number_of_days'] = null;
            $payload['pickup_location'] = null;
            $payload['dropoff_location'] = null;
            $payload['booking_reference_no'] = null;
            $payload['tour_operator_client_name'] = null;
            $payload['contact_person'] = null;
            $payload['contact_number'] = null;
            $payload['contact_email'] = null;
            $payload['adults'] = null;
            $payload['children'] = null;
            $payload['nationality'] = null;
        }

        return $payload;
    }

    private function isSafariType(?string $type): bool
    {
        return is_string($type) && str_starts_with(strtolower($type), 'safari');
    }

    private function isLeaseType(?string $type): bool
    {
        return is_string($type) && strtolower(trim($type)) === 'long term lease';
    }

    private function findDuplicateLeaseAllocationJobCard(int $leaseAllocationId, ?int $ignoreJobCardId = null): ?JobCard
    {
        $query = JobCard::query()
            ->where('lease_allocation_id', $leaseAllocationId)
            ->whereRaw('LOWER(TRIM(type)) = ?', ['long term lease']);

        if ($ignoreJobCardId !== null) {
            $query->where('id', '!=', $ignoreJobCardId);
        }

        return $query->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(JobCard $jobCard): array
    {
        $timeOut = $jobCard->time_out ? date('H:i', strtotime((string) $jobCard->time_out)) : null;
        $timeIn = $jobCard->time_in ? date('H:i', strtotime((string) $jobCard->time_in)) : null;
        $routeItinerary = $this->normalizeRouteItinerary($jobCard->route_itinerary);

        if (empty($routeItinerary) && $this->isSafariType($jobCard->type)) {
            $routeItinerary = $this->resolveSafariItinerary($jobCard->lead_id ? (int) $jobCard->lead_id : null, $jobCard);
        }

        return [
            'id' => $jobCard->id,
            'jobCardNo' => $jobCard->job_card_no,
            'leadId' => $jobCard->lead_id,
            'vehicleId' => $jobCard->vehicle_id,
            'leaseContractId' => $jobCard->lease_contract_id,
            'leaseAllocationId' => $jobCard->lease_allocation_id,
            'type' => $jobCard->type,
            'status' => $jobCard->status,
            'safariStartDate' => optional($jobCard->safari_start_date)->format('Y-m-d'),
            'safariEndDate' => optional($jobCard->safari_end_date)->format('Y-m-d'),
            'timeOut' => $timeOut,
            'timeIn' => $timeIn,
            'routeSummary' => $jobCard->route_summary,
            'additionalDetails' => $jobCard->additional_details,
            'numberOfDays' => $jobCard->number_of_days,
            'pickupLocation' => $jobCard->pickup_location,
            'dropoffLocation' => $jobCard->dropoff_location,
            'bookingReferenceNo' => $jobCard->booking_reference_no,
            'tourOperatorClientName' => $jobCard->tour_operator_client_name,
            'contactPerson' => $jobCard->contact_person,
            'contactNumber' => $jobCard->contact_number,
            'contactEmail' => $jobCard->contact_email,
            'adults' => $jobCard->adults,
            'children' => $jobCard->children,
            'nationality' => $jobCard->nationality,
            'reason' => $jobCard->reason,
            'clientDetails' => $jobCard->client_details,
            'location' => $jobCard->location,
            'kms' => $jobCard->kms !== null ? (float) $jobCard->kms : null,
            'odometerOut' => $jobCard->odometer_out,
            'odometerIn' => $jobCard->odometer_in,
            'mileage' => $jobCard->mileage,
            'fuelGaugeOut' => $jobCard->fuel_gauge_out !== null ? (float) $jobCard->fuel_gauge_out : null,
            'fuelGaugeIn' => $jobCard->fuel_gauge_in !== null ? (float) $jobCard->fuel_gauge_in : null,
            'approximateFuelUsed' => $jobCard->approximate_fuel_used !== null ? (float) $jobCard->approximate_fuel_used : null,
            'driverDetails' => $jobCard->driver_details,
            'driverAllowance' => $jobCard->driver_allowance !== null ? (float) $jobCard->driver_allowance : null,
            'vehicle' => $jobCard->vehicle ? [
                'id' => $jobCard->vehicle->id,
                'vehicle_no' => $jobCard->vehicle->vehicle_no,
                'plate_no' => $jobCard->vehicle->plate_no,
            ] : null,
            'leaseContract' => $jobCard->leaseContract ? [
                'id' => $jobCard->leaseContract->id,
                'clientName' => $jobCard->leaseContract->client_name,
                'leaseType' => $jobCard->leaseContract->lease_type,
                'startDate' => optional($jobCard->leaseContract->start_date)->toDateString(),
                'endDate' => optional($jobCard->leaseContract->end_date)->toDateString(),
                'status' => $jobCard->leaseContract->status,
            ] : null,
            'leaseAllocation' => $jobCard->leaseAllocation ? [
                'id' => $jobCard->leaseAllocation->id,
                'leaseContractId' => $jobCard->leaseAllocation->lease_contract_id,
                'vehicleId' => $jobCard->leaseAllocation->vehicle_id,
                'startDate' => optional($jobCard->leaseAllocation->start_date)->toDateString(),
                'endDate' => optional($jobCard->leaseAllocation->end_date)->toDateString(),
                'itinerary' => $jobCard->leaseAllocation->itinerary,
                'status' => $jobCard->leaseAllocation->status,
                'vehicle' => $jobCard->leaseAllocation->vehicle ? [
                    'id' => $jobCard->leaseAllocation->vehicle->id,
                    'vehicleNo' => $jobCard->leaseAllocation->vehicle->vehicle_no,
                    'plateNo' => $jobCard->leaseAllocation->vehicle->plate_no,
                ] : null,
                'driver' => $jobCard->leaseAllocation->driver ? [
                    'id' => $jobCard->leaseAllocation->driver->id,
                    'name' => $jobCard->leaseAllocation->driver->name,
                ] : null,
                'contract' => $jobCard->leaseAllocation->leaseContract ? [
                    'id' => $jobCard->leaseAllocation->leaseContract->id,
                    'clientName' => $jobCard->leaseAllocation->leaseContract->client_name,
                    'leaseType' => $jobCard->leaseAllocation->leaseContract->lease_type,
                ] : null,
            ] : null,
            // Backward-compatible fields used by existing PDF/template consumers
            'numberOfClients' => [
                'adults' => $jobCard->adults,
                'children' => $jobCard->children,
            ],
            'guideLanguage' => $jobCard->guide_language,
            'routeItinerary' => $routeItinerary,
            'createdAt' => $jobCard->created_at?->toISOString(),
            'updatedAt' => $jobCard->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function resolveSafariItinerary(?int $leadId, ?JobCard $jobCard): array
    {
        $existing = $this->normalizeRouteItinerary($jobCard?->route_itinerary);
        if (!empty($existing)) {
            return $existing;
        }

        if (!$leadId) {
            return [];
        }

        $lead = Lead::query()
            ->with([
                'quotations' => fn($query) => $query->latest('quote_date')->latest('id')->limit(1),
            ])
            ->find($leadId);

        $quotation = $lead?->quotations->first();
        $sections = is_array($quotation?->day_sections) ? $quotation->day_sections : [];

        return $this->normalizeRouteItinerary($sections);
    }

    /**
     * @param mixed $itinerary
     * @return array<int, array<string, string>>
     */
    private function normalizeRouteItinerary(mixed $itinerary): array
    {
        if (!is_array($itinerary)) {
            return [];
        }

        return collect($itinerary)
            ->map(function ($item): ?array {
                if (is_string($item)) {
                    $value = trim($item);
                    if ($value === '') {
                        return null;
                    }

                    return [
                        'date' => $value,
                        'dayDescription' => '',
                        'allowancePerDay' => null,
                    ];
                }

                if (!is_array($item)) {
                    return null;
                }

                $date = trim((string) ($item['date'] ?? $item['dayDate'] ?? $item['dayTitle'] ?? ''));
                $description = trim((string) ($item['dayDescription'] ?? $item['dateDescription'] ?? $item['description'] ?? $item['details'] ?? ''));
                $allowanceRaw = $item['allowancePerDay'] ?? $item['allowance_per_day'] ?? null;
                $allowance = null;
                if ($allowanceRaw !== null && $allowanceRaw !== '') {
                    if (is_numeric($allowanceRaw)) {
                        $allowance = (float) $allowanceRaw;
                    }
                }

                if ($date === '' && $description === '' && $allowance === null) {
                    return null;
                }

                return [
                    'date' => $date,
                    'dayDescription' => $description,
                    'allowancePerDay' => $allowance,
                ];
            })
            ->filter()
            ->values()
            ->all();
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
}
