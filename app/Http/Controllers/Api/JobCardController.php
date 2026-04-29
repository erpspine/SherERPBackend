<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\SafariAllocation;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class JobCardController extends Controller
{
    private const TYPES = ['Safari', 'Test Drive', 'Service', 'Client Viewing', 'Others'];

    private const STATUSES = ['Open', 'Closed'];

    private const REASON_TYPES = ['Test Drive', 'Service', 'Others'];

    private const VEHICLE_RUN_TYPES = ['Test Drive', 'Service', 'Client Viewing', 'Others'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JobCard::class);

        $query = JobCard::query()->with(['lead', 'vehicle'])->latest('id');

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

        $jobCard->load(['lead', 'vehicle']);

        return response()->json([
            'message' => 'Job card fetched successfully.',
            'jobCard' => $this->transform($jobCard),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', JobCard::class);

        $validated = $request->validate($this->rules());

        $type = $validated['type'];
        if ($type === 'Safari' && empty($validated['leadId'])) {
            return response()->json([
                'message' => 'leadId is required for Safari job cards.',
            ], 422);
        }

        if ($type !== 'Safari' && empty($validated['vehicleId'])) {
            return response()->json([
                'message' => 'vehicleId is required for non-Safari job cards.',
            ], 422);
        }

        $payload = $this->applyTypeSpecificPayload($validated, $type, null);

        $jobCard = JobCard::create($payload);

        $jobCard->update([
            'job_card_no' => $this->generateJobCardNo($jobCard->id),
        ]);

        $jobCard->load(['lead', 'vehicle']);

        return response()->json([
            'message' => 'Job card created successfully.',
            'jobCard' => $this->transform($jobCard),
        ], 201);
    }

    public function update(Request $request, JobCard $jobCard): JsonResponse
    {
        $this->authorize('update', $jobCard);

        $validated = $request->validate($this->rules(isUpdate: true, jobCard: $jobCard));

        $type = $validated['type'] ?? $jobCard->type;
        $leadId = array_key_exists('leadId', $validated) ? $validated['leadId'] : $jobCard->lead_id;
        $vehicleId = array_key_exists('vehicleId', $validated) ? $validated['vehicleId'] : $jobCard->vehicle_id;

        if ($type === 'Safari' && empty($leadId)) {
            return response()->json([
                'message' => 'leadId is required for Safari job cards.',
            ], 422);
        }

        if ($type !== 'Safari' && empty($vehicleId)) {
            return response()->json([
                'message' => 'vehicleId is required for non-Safari job cards.',
            ], 422);
        }

        $payload = $this->applyTypeSpecificPayload($validated, $type, $jobCard);

        $jobCard->update($payload);

        $jobCard->load(['lead', 'vehicle']);

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

        $pdf = Pdf::loadView('job-cards.pdf', [
            'jobCard' => $this->transform($jobCard),
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
    private function rules(bool $isUpdate = false, ?JobCard $jobCard = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $requestedType = request()->input('type');
        $effectiveType = is_string($requestedType) && $requestedType !== '' ? $requestedType : $jobCard?->type;

        $leadIdRules = ['sometimes', 'nullable'];

        if ($effectiveType === 'Safari') {
            if (! $isUpdate) {
                $leadIdRules[] = 'required';
            }

            $leadIdRules[] = 'integer';
            $leadIdRules[] = 'exists:leads,id';
        }

        return [
            'leadId' => $leadIdRules,
            'vehicleId' => [$required, 'nullable', 'integer', 'exists:vehicles,id'],
            'type' => [$required, 'string', Rule::in(self::TYPES)],
            'status' => ['sometimes', 'string', Rule::in(self::STATUSES)],
            'safariStartDate' => ['sometimes', 'nullable', 'date'],
            'safariEndDate' => ['sometimes', 'nullable', 'date'],
            'timeOut' => ['sometimes', 'nullable', 'date_format:H:i'],
            'timeIn' => ['sometimes', 'nullable', 'date_format:H:i'],
            'routeSummary' => ['sometimes', 'nullable', 'string', 'max:500'],
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
            'route_itinerary' => $jobCard?->route_itinerary,
            'guide_language' => $jobCard?->guide_language,
        ];

        if ($type === 'Safari') {
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
        } else {
            $payload['lead_id'] = null;

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

    /**
     * @return array<string, mixed>
     */
    private function transform(JobCard $jobCard): array
    {
        $timeOut = $jobCard->time_out ? date('H:i', strtotime((string) $jobCard->time_out)) : null;
        $timeIn = $jobCard->time_in ? date('H:i', strtotime((string) $jobCard->time_in)) : null;

        return [
            'id' => $jobCard->id,
            'jobCardNo' => $jobCard->job_card_no,
            'leadId' => $jobCard->lead_id,
            'vehicleId' => $jobCard->vehicle_id,
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
            'vehicle' => $jobCard->vehicle ? [
                'id' => $jobCard->vehicle->id,
                'vehicle_no' => $jobCard->vehicle->vehicle_no,
                'plate_no' => $jobCard->vehicle->plate_no,
            ] : null,
            // Backward-compatible fields used by existing PDF/template consumers
            'numberOfClients' => [
                'adults' => $jobCard->adults,
                'children' => $jobCard->children,
            ],
            'guideLanguage' => $jobCard->guide_language,
            'routeItinerary' => $jobCard->route_itinerary ?? [],
            'createdAt' => $jobCard->created_at?->toISOString(),
            'updatedAt' => $jobCard->updated_at?->toISOString(),
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
}
