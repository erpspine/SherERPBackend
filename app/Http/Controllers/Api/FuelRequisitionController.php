<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\FuelRequisitionCreatedMail;
use App\Mail\FuelRequisitionRespondedMail;
use App\Models\FuelRequisition;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FuelRequisitionController extends Controller
{
    private const RESPONSE_STATUSES = ['Approved', 'Rejected', 'Amend'];

    public function index(): JsonResponse
    {
        $fuelRequisitions = FuelRequisition::query()
            ->with(['lead', 'requester', 'responder', 'approvedBy', 'rejectedBy', 'amendedBy'])
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Fuel requisitions fetched successfully.',
            'fuelRequisitions' => $fuelRequisitions
                ->map(fn(FuelRequisition $fuelRequisition): array => $this->transformFuelRequisition($fuelRequisition))
                ->values(),
        ]);
    }

    public function show(FuelRequisition $fuelRequisition): JsonResponse
    {
        $fuelRequisition->loadMissing(['lead', 'requester', 'responder', 'approvedBy', 'rejectedBy', 'amendedBy']);

        return response()->json([
            'message' => 'Fuel requisition fetched successfully.',
            'fuelRequisition' => $this->transformFuelRequisition($fuelRequisition),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'leadId' => ['required', 'integer', 'exists:leads,id', Rule::unique('fuel_requisitions', 'lead_id')],
            'litres' => ['required', 'numeric', 'gt:0', 'max:99999.99'],
            'baseRatePerKm' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:2000'],
            'transportItinerary' => ['sometimes', 'array'],
            'transportItinerary.*.day' => ['required_with:transportItinerary', 'integer', 'min:1'],
            'transportItinerary.*.date' => ['required_with:transportItinerary', 'date'],
            'transportItinerary.*.destinations' => ['required_with:transportItinerary', 'array'],
            'transportItinerary.*.destinations.*.destinationFrom' => ['required_with:transportItinerary.*.destinations', 'string', 'max:255'],
            'transportItinerary.*.destinations.*.destinationTo' => ['nullable', 'string', 'max:255'],
            'transportItinerary.*.destinations.*.distanceKm' => ['required_with:transportItinerary.*.destinations', 'numeric', 'gt:0'],
            'transportItinerary.*.distanceKm' => ['required_with:transportItinerary', 'numeric', 'gt:0'],
            'totalDistanceKm' => ['required', 'numeric', 'min:0'],
            'totalFuelLitres' => ['required', 'numeric', 'gt:0'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $reason = trim((string) $request->input('reason', ''));
            if ($reason === '') {
                $validator->errors()->add('reason', 'The reason field is required.');
            }

            $itinerary = $request->input('transportItinerary', []);
            if (! is_array($itinerary)) {
                return;
            }

            $totalDistance = 0.0;

            foreach ($itinerary as $dayIndex => $day) {
                if (! is_array($day)) {
                    continue;
                }

                $destinations = $day['destinations'] ?? null;
                if (! is_array($destinations) || count($destinations) === 0) {
                    $validator->errors()->add("transportItinerary.{$dayIndex}.destinations", 'Each day must contain at least one destination.');
                    continue;
                }

                $dayDistance = 0.0;

                foreach ($destinations as $destinationIndex => $destination) {
                    if (! is_array($destination)) {
                        continue;
                    }

                    $destinationFrom = trim((string) ($destination['destinationFrom'] ?? ''));
                    if ($destinationFrom === '') {
                        $validator->errors()->add("transportItinerary.{$dayIndex}.destinations.{$destinationIndex}.destinationFrom", 'The destinationFrom field is required.');
                    }

                    $distanceKm = $destination['distanceKm'] ?? null;
                    if (is_numeric($distanceKm)) {
                        $dayDistance += (float) $distanceKm;
                    }
                }

                $declaredDayDistance = $day['distanceKm'] ?? null;
                if (is_numeric($declaredDayDistance) && abs(((float) $declaredDayDistance) - $dayDistance) > 0.01) {
                    $validator->errors()->add("transportItinerary.{$dayIndex}.distanceKm", 'Day distanceKm must equal the sum of destination distances.');
                }

                $totalDistance += $dayDistance;
            }

            $declaredTotalDistance = $request->input('totalDistanceKm');
            if (is_numeric($declaredTotalDistance) && abs(((float) $declaredTotalDistance) - $totalDistance) > 0.01) {
                $validator->errors()->add('totalDistanceKm', 'totalDistanceKm must equal the sum of all day distanceKm values.');
            }

            $litres = $request->input('litres');
            $totalFuelLitres = $request->input('totalFuelLitres');
            if (is_numeric($litres) && is_numeric($totalFuelLitres) && abs(((float) $litres) - ((float) $totalFuelLitres)) > 0.01) {
                $validator->errors()->add('totalFuelLitres', 'totalFuelLitres must match litres.');
            }
        });

        $validated = $validator->validate();

        /** @var User $requestUser */
        $requestUser = $request->user();

        $fuelRequisition = FuelRequisition::create([
            'lead_id' => (int) $validated['leadId'],
            'user_id' => $requestUser->id,
            'litres' => $validated['litres'],
            'base_rate_per_km' => $validated['baseRatePerKm'],
            'reason' => trim((string) $validated['reason']),
            'transport_itinerary' => $validated['transportItinerary'] ?? [],
            'total_distance_km' => $validated['totalDistanceKm'],
            'total_fuel_litres' => $validated['totalFuelLitres'],
            'status' => 'Pending',
            'approved_by' => null,
            'rejected_by' => null,
            'amended_by' => null,
        ]);

        $fuelRequisition->loadMissing(['lead', 'requester', 'responder', 'approvedBy', 'rejectedBy', 'amendedBy']);

        $recipientEmails = User::query()
            ->where('receive_notifications', true)
            ->where('status', 'Active')
            ->whereNotNull('email')
            ->pluck('email')
            ->filter(fn($email): bool => is_string($email) && $email !== '')
            ->values()
            ->all();

        if ($recipientEmails !== []) {
            Mail::to($recipientEmails)->send(new FuelRequisitionCreatedMail($fuelRequisition, $requestUser));
        }

        return response()->json([
            'message' => 'Fuel requisition created successfully.',
            'fuelRequisition' => $this->transformFuelRequisition($fuelRequisition),
        ], 201);
    }

    public function approve(Request $request, FuelRequisition $fuelRequisition): JsonResponse
    {
        return $this->respond($request, $fuelRequisition, 'Approved');
    }

    public function reject(Request $request, FuelRequisition $fuelRequisition): JsonResponse
    {
        return $this->respond($request, $fuelRequisition, 'Rejected');
    }

    public function updateStatus(Request $request, FuelRequisition $fuelRequisition): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', self::RESPONSE_STATUSES)],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'approvalNote' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'approval_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'responseNote' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $status = (string) $validated['status'];
        $note = $validated['note']
            ?? $validated['notes']
            ?? $validated['approvalNote']
            ?? $validated['approval_note']
            ?? $validated['responseNote']
            ?? null;

        return $this->respond($request, $fuelRequisition, $status, $note);
    }

    private function respond(Request $request, FuelRequisition $fuelRequisition, string $status, ?string $note = null): JsonResponse
    {
        if ($note === null) {
            $validated = $request->validate([
                'responseNote' => ['nullable', 'string', 'max:2000'],
                'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'approvalNote' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'approval_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            ]);

            $note = $validated['note']
                ?? $validated['notes']
                ?? $validated['approvalNote']
                ?? $validated['approval_note']
                ?? $validated['responseNote']
                ?? null;
        }

        /** @var User $requestUser */
        $requestUser = $request->user();

        $fuelRequisition->update([
            'status' => $status,
            'responded_by' => $requestUser->id,
            'approved_by' => $status === 'Approved' ? $requestUser->id : $fuelRequisition->approved_by,
            'rejected_by' => $status === 'Rejected' ? $requestUser->id : $fuelRequisition->rejected_by,
            'amended_by' => $status === 'Amend' ? $requestUser->id : $fuelRequisition->amended_by,
            'responded_at' => now(),
            'response_note' => is_string($note) ? trim($note) : null,
        ]);

        $fuelRequisition->refresh()->loadMissing(['lead', 'requester', 'responder', 'approvedBy', 'rejectedBy', 'amendedBy']);

        if (is_string($fuelRequisition->requester?->email) && $fuelRequisition->requester->email !== '') {
            Mail::to($fuelRequisition->requester->email)->send(
                new FuelRequisitionRespondedMail(
                    fuelRequisition: $fuelRequisition,
                    responder: $requestUser,
                )
            );
        }

        return response()->json([
            'message' => 'Fuel requisition ' . strtolower($status) . ' successfully.',
            'id' => $fuelRequisition->id,
            'status' => $fuelRequisition->status,
            'note' => $fuelRequisition->response_note,
            'approvedBy' => $fuelRequisition->approved_by,
            'rejectedBy' => $fuelRequisition->rejected_by,
            'amendedBy' => $fuelRequisition->amended_by,
            'fuelRequisition' => $this->transformFuelRequisition($fuelRequisition),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformFuelRequisition(FuelRequisition $fuelRequisition): array
    {
        return [
            'id' => $fuelRequisition->id,
            'lead' => [
                'id' => $fuelRequisition->lead?->id,
                'bookingRef' => $fuelRequisition->lead?->booking_ref,
                'clientCompany' => $fuelRequisition->lead?->client_company,
            ],
            'leadId' => $fuelRequisition->lead_id,
            'litres' => (float) $fuelRequisition->litres,
            'baseRatePerKm' => $fuelRequisition->base_rate_per_km !== null ? (float) $fuelRequisition->base_rate_per_km : null,
            'reason' => $fuelRequisition->reason,
            'transportItinerary' => $fuelRequisition->transport_itinerary ?? [],
            'totalDistanceKm' => $fuelRequisition->total_distance_km !== null ? (float) $fuelRequisition->total_distance_km : null,
            'totalFuelLitres' => $fuelRequisition->total_fuel_litres !== null ? (float) $fuelRequisition->total_fuel_litres : (float) $fuelRequisition->litres,
            'status' => $fuelRequisition->status ?? 'Pending',
            'note' => $fuelRequisition->response_note,
            'notes' => $fuelRequisition->response_note,
            'approvalNote' => $fuelRequisition->response_note,
            'approval_note' => $fuelRequisition->response_note,
            'responseNote' => $fuelRequisition->response_note,
            'approvedBy' => [
                'id' => $fuelRequisition->approvedBy?->id,
                'name' => $fuelRequisition->approvedBy?->name,
                'email' => $fuelRequisition->approvedBy?->email,
            ],
            'rejectedBy' => [
                'id' => $fuelRequisition->rejectedBy?->id,
                'name' => $fuelRequisition->rejectedBy?->name,
                'email' => $fuelRequisition->rejectedBy?->email,
            ],
            'amendedBy' => [
                'id' => $fuelRequisition->amendedBy?->id,
                'name' => $fuelRequisition->amendedBy?->name,
                'email' => $fuelRequisition->amendedBy?->email,
            ],
            'requestedBy' => [
                'id' => $fuelRequisition->requester?->id,
                'name' => $fuelRequisition->requester?->name,
                'email' => $fuelRequisition->requester?->email,
            ],
            'respondedBy' => [
                'id' => $fuelRequisition->responder?->id,
                'name' => $fuelRequisition->responder?->name,
                'email' => $fuelRequisition->responder?->email,
            ],
            'respondedAt' => $fuelRequisition->responded_at?->toISOString(),
            'createdAt' => $fuelRequisition->created_at?->toISOString(),
            'updatedAt' => $fuelRequisition->updated_at?->toISOString(),
        ];
    }
}
