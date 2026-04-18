<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LeadCreatedMail;
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
            'bookingRef' => ['required', 'string', 'max:50', 'unique:leads,booking_ref'],
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

        $lead = Lead::create($this->mapRequestToDb($validated));

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
            'sentBy' => $sentByName,
            'sentById' => $sentById,
            'quotationSentAt' => $lead->quotation_sent_at?->toISOString(),
            'piSentAt' => $lead->pi_sent_at?->toISOString(),
            'createdAt' => $lead->created_at?->toISOString(),
            'updatedAt' => $lead->updated_at?->toISOString(),
        ];
    }
}
