<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LeadCreatedMail;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(): JsonResponse
    {
        $leads = Lead::query()->with(['quotationSender', 'piSender'])->latest('id')->get();

        return response()->json([
            'message' => 'Leads fetched successfully.',
            'leads' => $leads->map(fn(Lead $lead): array => $this->transformLead($lead))->values(),
        ]);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->loadMissing(['quotationSender', 'piSender']);

        return response()->json([
            'message' => 'Lead fetched successfully.',
            'lead' => $this->transformLead($lead),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'clientCompany' => ['required', 'string', 'max:255'],
            'agentContact' => ['required', 'string', 'max:255'],
            'agentEmail' => ['required', 'email', 'max:255'],
            'agentPhone' => ['required', 'string', 'max:50'],
            'clientCountry' => ['required', 'string', 'max:120'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'routeParks' => ['required', 'string', 'max:500'],
            'paxAdults' => ['required', 'integer', 'min:0'],
            'paxChildren' => ['required', 'integer', 'min:0'],
            'noOfVehicles' => ['required', 'integer', 'min:1'],
            'specialRequirements' => ['nullable', 'string'],
            'bookingStatus' => ['required', Rule::in(['Pending', 'Confirmed', 'Cancelled', 'Completed', 'Quotation Sent', 'PI Sent'])],
        ]);

        $leadData = $this->mapRequestToDb($validated);
        $leadData['source'] = 'Internal';

        // Generate booking reference if not provided
        if (!isset($leadData['booking_ref']) || empty($leadData['booking_ref'])) {
            $leadData['booking_ref'] = $this->createBookingRef();
        }

        $lead = Lead::create($leadData);

        $this->createClientFromLeadIfMissing($validated);

        // Get current authenticated user
        /** @var User $currentUser */
        $currentUser = $request->user();

        // Send notifications to users with receive_notifications enabled
        $notificationUsers = User::where('receive_notifications', true)
            ->where('status', 'Active')
            ->get();

        foreach ($notificationUsers as $user) {
            Mail::to($user->email)->send(new LeadCreatedMail($lead, $currentUser));
        }

        return response()->json([
            'message' => 'Lead created successfully.',
            'lead' => $this->transformLead($lead),
        ], 201);
    }

    /**
     * Create a client from lead details if one does not already exist.
     *
     * @param  array<string, mixed>  $validated
     */
    private function createClientFromLeadIfMissing(array $validated): void
    {
        Client::firstOrCreate(
            ['email' => $validated['agentEmail']],
            [
                'name' => $validated['agentContact'],
                'company' => $validated['clientCompany'],
                'phone' => $validated['agentPhone'],
                'address' => $validated['clientCountry'],
            ]
        );
    }

    /**
     * Sync client record from lead data on edit.
     * Creates the client if they don't exist yet.
     * If the client already exists, only fills fields that are currently blank.
     *
     * @param  array<string, mixed>  $validated
     */
    private function syncClientFromLead(array $validated): void
    {
        $email = $validated['agentEmail'] ?? null;

        if ($email === null) {
            return;
        }

        $client = Client::where('email', $email)->first();

        if ($client === null) {
            Client::create([
                'email'   => $email,
                'name'    => $validated['agentContact'] ?? null,
                'company' => $validated['clientCompany'] ?? null,
                'phone'   => $validated['agentPhone'] ?? null,
                'address' => $validated['clientCountry'] ?? null,
            ]);

            return;
        }

        // Only fill fields that are currently blank on the existing client
        $fill = [];

        if (empty($client->name) && !empty($validated['agentContact'])) {
            $fill['name'] = $validated['agentContact'];
        }

        if (empty($client->company) && !empty($validated['clientCompany'])) {
            $fill['company'] = $validated['clientCompany'];
        }

        if (empty($client->phone) && !empty($validated['agentPhone'])) {
            $fill['phone'] = $validated['agentPhone'];
        }

        if (empty($client->address) && !empty($validated['clientCountry'])) {
            $fill['address'] = $validated['clientCountry'];
        }

        if (!empty($fill)) {
            $client->update($fill);
        }
    }

    /**
     * Generate a unique booking reference in format: BK-YYYY-MM-NNNN
     * The sequence number resets to 1 at the start of each month.
     */
    private function createBookingRef(): string
    {
        $year  = date('Y');
        $month = date('m');
        $prefix = "BK-{$year}-{$month}-";

        // Find the highest sequence number already used this month
        $latest = Lead::query()
            ->where('booking_ref', 'like', $prefix . '%')
            ->orderByDesc('booking_ref')
            ->value('booking_ref');

        $next = 1;

        if ($latest !== null) {
            $parts = explode('-', $latest);
            $lastSeq = (int) end($parts);
            $next = $lastSeq + 1;
        }

        $candidate = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        // Collision-safe: if somehow the candidate already exists, keep incrementing
        while (Lead::query()->where('booking_ref', $candidate)->exists()) {
            $next++;
            $candidate = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'bookingRef' => ['sometimes', 'string', 'max:50', Rule::unique('leads', 'booking_ref')->ignore($lead->id)],
            'clientCompany' => ['sometimes', 'string', 'max:255'],
            'agentContact' => ['sometimes', 'string', 'max:255'],
            'agentEmail' => ['sometimes', 'email', 'max:255'],
            'agentPhone' => ['sometimes', 'string', 'max:50'],
            'clientCountry' => ['sometimes', 'string', 'max:120'],
            'startDate' => ['sometimes', 'date'],
            'endDate' => ['sometimes', 'date'],
            'routeParks' => ['sometimes', 'string', 'max:500'],
            'paxAdults' => ['sometimes', 'integer', 'min:0'],
            'paxChildren' => ['sometimes', 'integer', 'min:0'],
            'noOfVehicles' => ['sometimes', 'integer', 'min:1'],
            'specialRequirements' => ['sometimes', 'nullable', 'string'],
            'bookingStatus' => ['sometimes', Rule::in(['Pending', 'Confirmed', 'Cancelled', 'Completed', 'Quotation Sent', 'PI Sent'])],
        ]);

        if (isset($validated['startDate']) && isset($validated['endDate']) && $validated['endDate'] < $validated['startDate']) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => ['endDate' => ['The endDate must be a date after or equal to startDate.']],
            ], 422);
        }

        $lead->update($this->mapRequestToDb($validated));
        $lead->refresh();

        $this->syncClientFromLead(array_merge(
            [
                'agentEmail'    => $lead->agent_email,
                'agentContact'  => $lead->agent_contact,
                'clientCompany' => $lead->client_company,
                'agentPhone'    => $lead->agent_phone,
                'clientCountry' => $lead->client_country,
            ],
            $validated
        ));

        return response()->json([
            'message' => 'Lead updated successfully.',
            'lead' => $this->transformLead($lead),
        ]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json([
            'message' => 'Lead deleted successfully.',
        ]);
    }

    public function generateBookingRef(): JsonResponse
    {
        $bookingRef = $this->createBookingRef();

        return response()->json([
            'message' => 'Booking reference generated successfully.',
            'bookingRef' => $bookingRef,
        ]);
    }

    /**
     * Capture a lead from an external website (public endpoint).
     * This endpoint is intended to be called from external websites.
     * Requires X-Lead-API-Key header for identification.
     *
     * @return JsonResponse
     */
    public function captureFromWebsite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'clientCompany' => ['required', 'string', 'max:255'],
            'agentContact' => ['required', 'string', 'max:255'],
            'agentEmail' => ['required', 'email', 'max:255'],
            'agentPhone' => ['required', 'string', 'max:50'],
            'clientCountry' => ['required', 'string', 'max:120'],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'routeParks' => ['required', 'string', 'max:500'],
            'paxAdults' => ['required', 'integer', 'min:1'],
            'paxChildren' => ['required', 'integer', 'min:0'],
            'noOfVehicles' => ['required', 'integer', 'min:1'],
            'specialRequirements' => ['nullable', 'string'],
            // Honeypot field for bot protection - should always be empty
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        // If honeypot field has any value, reject silently (return success to confuse bots)
        if (!empty($request->input('website'))) {
            // Log suspicious activity but return success to bot
            \Illuminate\Support\Facades\Log::warning('Honeypot triggered for lead submission', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'message' => 'Thank you! Your lead has been received. We will contact you shortly.',
            ], 201);
        }

        $leadData = $this->mapRequestToDb($validated);
        $leadData['source'] = 'Website';
        $leadData['booking_status'] = 'Pending';

        // Generate booking reference for website leads
        if (!isset($leadData['booking_ref']) || empty($leadData['booking_ref'])) {
            $leadData['booking_ref'] = $this->createBookingRef();
        }

        // Attach API key if provided
        $apiKey = $request->input('_lead_api_key');
        if ($apiKey) {
            $leadData['lead_api_key_id'] = $apiKey->id;
        }

        $lead = Lead::create($leadData);

        // Create client from lead details
        $this->createClientFromLeadIfMissing($validated);

        // Send notifications to users with receive_notifications enabled
        $notificationUsers = User::where('receive_notifications', true)
            ->where('status', 'Active')
            ->get();

        foreach ($notificationUsers as $user) {
            Mail::to($user->email)->send(new LeadCreatedMail($lead, null));
        }

        return response()->json([
            'message' => 'Thank you! Your lead has been received. We will contact you shortly.',
            'lead' => $this->transformLead($lead),
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapRequestToDb(array $validated): array
    {
        $map = [
            'bookingRef' => 'booking_ref',
            'clientCompany' => 'client_company',
            'agentContact' => 'agent_contact',
            'agentEmail' => 'agent_email',
            'agentPhone' => 'agent_phone',
            'clientCountry' => 'client_country',
            'startDate' => 'start_date',
            'endDate' => 'end_date',
            'routeParks' => 'route_parks',
            'paxAdults' => 'pax_adults',
            'paxChildren' => 'pax_children',
            'noOfVehicles' => 'no_of_vehicles',
            'specialRequirements' => 'special_requirements',
            'bookingStatus' => 'booking_status',
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
    private function transformLead(Lead $lead): array
    {
        $sentByName = $lead->booking_status === 'PI Sent'
            ? $lead->piSender?->name
            : $lead->quotationSender?->name;

        $sentById = $lead->booking_status === 'PI Sent'
            ? $lead->pi_sent_by
            : $lead->quotation_sent_by;

        return [
            'id' => $lead->id,
            'bookingRef' => $lead->booking_ref,
            'clientCompany' => $lead->client_company,
            'agentContact' => $lead->agent_contact,
            'agentEmail' => $lead->agent_email,
            'agentPhone' => $lead->agent_phone,
            'clientCountry' => $lead->client_country,
            'startDate' => optional($lead->start_date)->format('Y-m-d'),
            'endDate' => optional($lead->end_date)->format('Y-m-d'),
            'routeParks' => $lead->route_parks,
            'paxAdults' => $lead->pax_adults,
            'paxChildren' => $lead->pax_children,
            'noOfVehicles' => $lead->no_of_vehicles,
            'specialRequirements' => $lead->special_requirements,
            'bookingStatus' => $lead->booking_status,
            'source' => $lead->source,
            'apiKeyName' => $lead->apiKey?->name,
            'sentBy' => $sentByName,
            'sentById' => $sentById,
            'quotationSentAt' => $lead->quotation_sent_at?->toISOString(),
            'piSentAt' => $lead->pi_sent_at?->toISOString(),
            'createdAt' => $lead->created_at?->toISOString(),
            'updatedAt' => $lead->updated_at?->toISOString(),
        ];
    }
}
