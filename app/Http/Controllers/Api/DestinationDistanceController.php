<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DestinationDistance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DestinationDistanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) ($request->query('search', $request->query('q', ''))));

        $destinationDistances = DestinationDistance::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('destination_from', 'like', '%' . $search . '%')
                        ->orWhere('destination_to', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('destination_from')
            ->orderBy('destination_to')
            ->get();

        return response()->json([
            'message' => 'Destination distances fetched successfully.',
            'destinationDistances' => $destinationDistances->map(fn(DestinationDistance $destinationDistance): array => $this->transform($destinationDistance))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destinationFrom' => ['required', 'string', 'max:255'],
            'destinationTo' => ['required', 'string', 'max:255'],
            'distanceKm' => ['required', 'numeric', 'gt:0'],
            'distance' => ['sometimes', 'numeric', 'gt:0'],
        ]);

        $payload = $this->mapRequestToDb($validated);

        validator($payload, [
            'destination_from' => ['required', 'string', 'max:255'],
            'destination_to' => ['required', 'string', 'max:255', Rule::unique('destination_distances')->where(fn($query) => $query->where('destination_from', $payload['destination_from']))],
            'distance_km' => ['required', 'numeric', 'gt:0'],
        ])->validate();

        $destinationDistance = DestinationDistance::create($payload);

        return response()->json([
            'message' => 'Destination distance created successfully.',
            'destinationDistance' => $this->transform($destinationDistance),
        ], 201);
    }

    public function update(Request $request, DestinationDistance $destinationDistance): JsonResponse
    {
        $validated = $request->validate([
            'destinationFrom' => ['sometimes', 'string', 'max:255'],
            'destinationTo' => ['sometimes', 'string', 'max:255'],
            'distanceKm' => ['sometimes', 'numeric', 'gt:0'],
            'distance' => ['sometimes', 'numeric', 'gt:0'],
        ]);

        $payload = $this->mapRequestToDb($validated);
        $candidate = array_merge($destinationDistance->only(['destination_from', 'destination_to', 'distance_km']), $payload);

        validator($candidate, [
            'destination_from' => ['required', 'string', 'max:255'],
            'destination_to' => ['required', 'string', 'max:255', Rule::unique('destination_distances')->where(fn($query) => $query->where('destination_from', $candidate['destination_from']))->ignore($destinationDistance->id)],
            'distance_km' => ['required', 'numeric', 'gt:0'],
        ])->validate();

        $destinationDistance->update($payload);
        $destinationDistance->refresh();

        return response()->json([
            'message' => 'Destination distance updated successfully.',
            'destinationDistance' => $this->transform($destinationDistance),
        ]);
    }

    public function destroy(DestinationDistance $destinationDistance): JsonResponse
    {
        $destinationDistance->delete();

        return response()->json([
            'message' => 'Destination distance deleted successfully.',
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function mapRequestToDb(array $validated): array
    {
        $payload = [];

        if (array_key_exists('destinationFrom', $validated)) {
            $payload['destination_from'] = trim((string) $validated['destinationFrom']);
        }

        if (array_key_exists('destinationTo', $validated)) {
            $payload['destination_to'] = trim((string) $validated['destinationTo']);
        }

        if (array_key_exists('distanceKm', $validated)) {
            $payload['distance_km'] = $validated['distanceKm'];
        } elseif (array_key_exists('distance', $validated)) {
            $payload['distance_km'] = $validated['distance'];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(DestinationDistance $destinationDistance): array
    {
        return [
            'id' => $destinationDistance->id,
            'destinationFrom' => $destinationDistance->destination_from,
            'destinationTo' => $destinationDistance->destination_to,
            'distanceKm' => (float) $destinationDistance->distance_km,
            'createdAt' => $destinationDistance->created_at?->toISOString(),
            'updatedAt' => $destinationDistance->updated_at?->toISOString(),
        ];
    }
}
