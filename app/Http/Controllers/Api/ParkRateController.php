<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Park;
use App\Models\ParkRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParkRateController extends Controller
{
    private const TYPES      = ['non_resident', 'resident', 'citizen'];
    private const CATEGORIES = ['adult', 'child'];

    public function index(): JsonResponse
    {
        $rates = ParkRate::query()->with('park')->orderBy('id')->get();

        return response()->json([
            'message' => 'Park rates fetched successfully.',
            'parkRates' => $rates->map(fn(ParkRate $rate): array => $this->transform($rate))->values(),
        ]);
    }

    public function show(ParkRate $parkRate): JsonResponse
    {
        $parkRate->load('park');

        return response()->json([
            'message' => 'Park rate fetched successfully.',
            'parkRate' => $this->transform($parkRate),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'park_id'  => ['required', 'integer', 'exists:parks,id'],
            'type'     => ['required', 'string', Rule::in(self::TYPES)],
            'category' => ['required', 'string', Rule::in(self::CATEGORIES)],
            'rate'     => ['required', 'numeric', 'min:0'],
        ]);

        // Enforce unique combination of park_id + type + category
        $exists = ParkRate::where('park_id', $validated['park_id'])
            ->where('type', $validated['type'])
            ->where('category', $validated['category'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => ['type' => ['A rate for this park, type and category already exists.']],
            ], 422);
        }

        $parkRate = ParkRate::create($validated);
        $parkRate->load('park');

        return response()->json([
            'message'  => 'Park rate created successfully.',
            'parkRate' => $this->transform($parkRate),
        ], 201);
    }

    public function update(Request $request, ParkRate $parkRate): JsonResponse
    {
        $validated = $request->validate([
            'park_id'  => ['sometimes', 'integer', 'exists:parks,id'],
            'type'     => ['sometimes', 'string', Rule::in(self::TYPES)],
            'category' => ['sometimes', 'string', Rule::in(self::CATEGORIES)],
            'rate'     => ['sometimes', 'numeric', 'min:0'],
        ]);

        // Check uniqueness if any of the three key fields change
        $checkId       = $validated['park_id']  ?? $parkRate->park_id;
        $checkType     = $validated['type']      ?? $parkRate->type;
        $checkCategory = $validated['category']  ?? $parkRate->category;

        $duplicate = ParkRate::where('park_id', $checkId)
            ->where('type', $checkType)
            ->where('category', $checkCategory)
            ->where('id', '!=', $parkRate->id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => ['type' => ['A rate for this park, type and category already exists.']],
            ], 422);
        }

        $parkRate->update($validated);
        $parkRate->refresh()->load('park');

        return response()->json([
            'message'  => 'Park rate updated successfully.',
            'parkRate' => $this->transform($parkRate),
        ]);
    }

    public function destroy(ParkRate $parkRate): JsonResponse
    {
        $parkRate->delete();

        return response()->json([
            'message' => 'Park rate deleted successfully.',
        ]);
    }

    /** List all rates for a specific park, grouped by type → category */
    public function byPark(Park $park): JsonResponse
    {
        $rates = $park->rates()->get();

        $grouped = collect(self::TYPES)->mapWithKeys(function (string $type) use ($rates): array {
            return [$type => collect(self::CATEGORIES)->mapWithKeys(function (string $cat) use ($rates, $type): array {
                $row = $rates->first(fn(ParkRate $r) => $r->type === $type && $r->category === $cat);
                return [$cat => $row ? (float) $row->rate : null];
            })];
        });

        return response()->json([
            'message' => 'Park rates fetched successfully.',
            'park'    => ['id' => $park->id, 'name' => $park->name, 'region' => $park->region],
            'types'   => self::TYPES,
            'parkRates' => $rates->map(fn(ParkRate $rate): array => $this->transform($rate))->values(),
            'rates'   => $grouped,
        ]);
    }

    private function transform(ParkRate $rate): array
    {
        return [
            'id'        => $rate->id,
            'parkId'    => $rate->park_id,
            'parkName'  => $rate->relationLoaded('park') ? $rate->park?->name : null,
            'type'      => $rate->type,
            'category'  => $rate->category,
            'rate'      => (float) $rate->rate,
            'createdAt' => $rate->created_at?->toISOString(),
            'updatedAt' => $rate->updated_at?->toISOString(),
        ];
    }
}
