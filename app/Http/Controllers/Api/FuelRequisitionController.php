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

class FuelRequisitionController extends Controller
{
    public function index(): JsonResponse
    {
        $fuelRequisitions = FuelRequisition::query()
            ->with(['lead', 'requester', 'responder'])
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
        $fuelRequisition->loadMissing(['lead', 'requester', 'responder']);

        return response()->json([
            'message' => 'Fuel requisition fetched successfully.',
            'fuelRequisition' => $this->transformFuelRequisition($fuelRequisition),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leadId' => ['required', 'exists:leads,id'],
            'litres' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        /** @var User $requestUser */
        $requestUser = $request->user();

        $fuelRequisition = FuelRequisition::create([
            'lead_id' => (int) $validated['leadId'],
            'user_id' => $requestUser->id,
            'litres' => $validated['litres'],
            'reason' => trim((string) $validated['reason']),
            'status' => 'Pending',
        ]);

        $fuelRequisition->loadMissing(['lead', 'requester', 'responder']);

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

    private function respond(Request $request, FuelRequisition $fuelRequisition, string $status): JsonResponse
    {
        $validated = $request->validate([
            'responseNote' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var User $requestUser */
        $requestUser = $request->user();

        $fuelRequisition->update([
            'status' => $status,
            'responded_by' => $requestUser->id,
            'responded_at' => now(),
            'response_note' => isset($validated['responseNote']) ? trim((string) $validated['responseNote']) : null,
        ]);

        $fuelRequisition->refresh()->loadMissing(['lead', 'requester', 'responder']);

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
            'litres' => (float) $fuelRequisition->litres,
            'reason' => $fuelRequisition->reason,
            'status' => $fuelRequisition->status ?? 'Pending',
            'responseNote' => $fuelRequisition->response_note,
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
