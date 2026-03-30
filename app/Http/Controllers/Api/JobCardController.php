<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\Lead;
use App\Models\SafariAllocation;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class JobCardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JobCard::class);

        $query = JobCard::query()->with('lead')->latest('id');

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

        $jobCard->load('lead');

        return response()->json([
            'message' => 'Job card fetched successfully.',
            'jobCard' => $this->transform($jobCard),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', JobCard::class);

        $validated = $request->validate([
            'leadId' => ['required', 'integer', 'exists:leads,id'],
            'guideLanguage' => ['sometimes', 'string', 'max:60'],
            'safariStartDate' => ['sometimes', 'date'],
            'safariEndDate' => ['sometimes', 'date'],
            'numberOfDays' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'pickupLocation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dropoffLocation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'routeSummary' => ['sometimes', 'nullable', 'string', 'max:500'],
            'routeItinerary' => ['sometimes', 'nullable', 'array'],
            'routeItinerary.*.dayTitle' => ['sometimes', 'string', 'max:100'],
            'routeItinerary.*.description' => ['sometimes', 'string'],
            'additionalDetails' => ['sometimes', 'nullable', 'string'],
            'bookingReferenceNo' => ['sometimes', 'string', 'max:50'],
            'tourOperatorClientName' => ['sometimes', 'string', 'max:255'],
            'contactPerson' => ['sometimes', 'string', 'max:255'],
            'contactNumber' => ['sometimes', 'string', 'max:50'],
            'contactEmail' => ['sometimes', 'nullable', 'email', 'max:255'],
            'adults' => ['sometimes', 'integer', 'min:0'],
            'children' => ['sometimes', 'integer', 'min:0'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]);

        $lead = Lead::query()->findOrFail($validated['leadId']);

        $startDate = isset($validated['safariStartDate'])
            ? Carbon::parse($validated['safariStartDate'])
            : Carbon::parse($lead->start_date);

        $endDate = isset($validated['safariEndDate'])
            ? Carbon::parse($validated['safariEndDate'])
            : Carbon::parse($lead->end_date);

        $numberOfDays = isset($validated['numberOfDays'])
            ? (int) $validated['numberOfDays']
            : max(1, $startDate->diffInDays($endDate) + 1);

        $jobCard = JobCard::create([
            'lead_id' => $lead->id,
            'job_card_no' => null,
            'booking_reference_no' => $validated['bookingReferenceNo'] ?? $lead->booking_ref,
            'tour_operator_client_name' => $validated['tourOperatorClientName'] ?? $lead->client_company,
            'contact_person' => $validated['contactPerson'] ?? $lead->agent_contact,
            'contact_number' => $validated['contactNumber'] ?? $lead->agent_phone,
            'contact_email' => $validated['contactEmail'] ?? $lead->agent_email,
            'adults' => $validated['adults'] ?? (int) $lead->pax_adults,
            'children' => $validated['children'] ?? (int) $lead->pax_children,
            'nationality' => $validated['nationality'] ?? $lead->client_country,
            'guide_language' => $validated['guideLanguage'] ?? 'English',
            'safari_start_date' => $startDate->toDateString(),
            'safari_end_date' => $endDate->toDateString(),
            'number_of_days' => $numberOfDays,
            'route_summary' => $validated['routeSummary'] ?? $lead->route_parks,
            'route_itinerary' => $validated['routeItinerary'] ?? null,
            'pickup_location' => $validated['pickupLocation'] ?? null,
            'dropoff_location' => $validated['dropoffLocation'] ?? null,
            'additional_details' => $validated['additionalDetails'] ?? $lead->special_requirements,
        ]);

        $jobCard->update([
            'job_card_no' => $this->generateJobCardNo($jobCard->id),
        ]);

        $jobCard->load('lead');

        return response()->json([
            'message' => 'Job card created successfully.',
            'jobCard' => $this->transform($jobCard),
        ], 201);
    }

    public function update(Request $request, JobCard $jobCard): JsonResponse
    {
        $this->authorize('update', $jobCard);

        $validated = $request->validate([
            'leadId' => ['sometimes', 'integer', 'exists:leads,id'],
            'guideLanguage' => ['sometimes', 'string', 'max:60'],
            'safariStartDate' => ['sometimes', 'date'],
            'safariEndDate' => ['sometimes', 'date'],
            'numberOfDays' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'pickupLocation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dropoffLocation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'routeSummary' => ['sometimes', 'nullable', 'string', 'max:500'],
            'routeItinerary' => ['sometimes', 'nullable', 'array'],
            'routeItinerary.*.dayTitle' => ['sometimes', 'string', 'max:100'],
            'routeItinerary.*.description' => ['sometimes', 'string'],
            'additionalDetails' => ['sometimes', 'nullable', 'string'],
            'bookingReferenceNo' => ['sometimes', 'string', 'max:50'],
            'tourOperatorClientName' => ['sometimes', 'string', 'max:255'],
            'contactPerson' => ['sometimes', 'string', 'max:255'],
            'contactNumber' => ['sometimes', 'string', 'max:50'],
            'contactEmail' => ['sometimes', 'nullable', 'email', 'max:255'],
            'adults' => ['sometimes', 'integer', 'min:0'],
            'children' => ['sometimes', 'integer', 'min:0'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]);

        $leadId = $validated['leadId'] ?? $jobCard->lead_id;
        $lead = $leadId ? Lead::query()->find($leadId) : null;

        $startDate = array_key_exists('safariStartDate', $validated)
            ? Carbon::parse($validated['safariStartDate'])
            : Carbon::parse($jobCard->safari_start_date);

        $endDate = array_key_exists('safariEndDate', $validated)
            ? Carbon::parse($validated['safariEndDate'])
            : Carbon::parse($jobCard->safari_end_date);

        $numberOfDays = array_key_exists('numberOfDays', $validated)
            ? (int) $validated['numberOfDays']
            : max(1, $startDate->diffInDays($endDate) + 1);

        $jobCard->update([
            'lead_id' => $leadId,
            'booking_reference_no' => $validated['bookingReferenceNo'] ?? $jobCard->booking_reference_no,
            'tour_operator_client_name' => $validated['tourOperatorClientName'] ?? $jobCard->tour_operator_client_name,
            'contact_person' => $validated['contactPerson'] ?? $jobCard->contact_person,
            'contact_number' => $validated['contactNumber'] ?? $jobCard->contact_number,
            'contact_email' => array_key_exists('contactEmail', $validated) ? $validated['contactEmail'] : $jobCard->contact_email,
            'adults' => $validated['adults'] ?? $jobCard->adults,
            'children' => $validated['children'] ?? $jobCard->children,
            'nationality' => array_key_exists('nationality', $validated) ? $validated['nationality'] : $jobCard->nationality,
            'guide_language' => $validated['guideLanguage'] ?? $jobCard->guide_language,
            'safari_start_date' => $startDate->toDateString(),
            'safari_end_date' => $endDate->toDateString(),
            'number_of_days' => $numberOfDays,
            'route_summary' => array_key_exists('routeSummary', $validated)
                ? $validated['routeSummary']
                : $jobCard->route_summary,
            'route_itinerary' => array_key_exists('routeItinerary', $validated)
                ? $validated['routeItinerary']
                : $jobCard->route_itinerary,
            'pickup_location' => array_key_exists('pickupLocation', $validated)
                ? $validated['pickupLocation']
                : $jobCard->pickup_location,
            'dropoff_location' => array_key_exists('dropoffLocation', $validated)
                ? $validated['dropoffLocation']
                : $jobCard->dropoff_location,
            'additional_details' => array_key_exists('additionalDetails', $validated)
                ? $validated['additionalDetails']
                : $jobCard->additional_details,
        ]);

        $jobCard->load('lead');

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

        $jobCard->load('lead');

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
    private function transform(JobCard $jobCard): array
    {
        return [
            'id' => $jobCard->id,
            'jobCardNo' => $jobCard->job_card_no,
            'leadId' => $jobCard->lead_id,
            'bookingReferenceNo' => $jobCard->booking_reference_no,
            'tourOperatorClientName' => $jobCard->tour_operator_client_name,
            'contactPerson' => $jobCard->contact_person,
            'contactNumber' => $jobCard->contact_number,
            'contactEmail' => $jobCard->contact_email,
            'numberOfClients' => [
                'adults' => $jobCard->adults,
                'children' => $jobCard->children,
            ],
            'nationality' => $jobCard->nationality,
            'guideLanguage' => $jobCard->guide_language,
            'safariStartDate' => optional($jobCard->safari_start_date)->format('Y-m-d'),
            'safariEndDate' => optional($jobCard->safari_end_date)->format('Y-m-d'),
            'numberOfDays' => $jobCard->number_of_days,
            'routeSummary' => $jobCard->route_summary,
            'routeItinerary' => $jobCard->route_itinerary ?? [],
            'pickupLocation' => $jobCard->pickup_location,
            'dropoffLocation' => $jobCard->dropoff_location,
            'additionalDetails' => $jobCard->additional_details,
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
