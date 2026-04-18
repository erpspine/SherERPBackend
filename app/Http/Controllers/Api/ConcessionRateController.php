<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConcessionRate;
use App\Models\Park;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConcessionRateController extends Controller
{
    private const TYPES      = ['non_resident', 'resident', 'citizen'];
    private const CATEGORIES = ['adult', 'child'];

    public function index(): JsonResponse
    {
        $concessionRates = ConcessionRate::query()->with('park')->orderBy('id')->get();

        return response()->json([
            'message' => 'Concession rates fetched successfully.',
            'concessionRates' => $concessionRates->map(fn(ConcessionRate $concessionRate): array => $this->transform($concessionRate))->values(),
        ]);
    }

    public function show(ConcessionRate $concessionRate): JsonResponse
    {
        $concessionRate->load('park');

        return response()->json([
            'message' => 'Concession rate fetched successfully.',
            'concessionRate' => $this->transform($concessionRate),
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

        $exists = ConcessionRate::query()
            ->where('park_id', $validated['park_id'])
            ->where('type', $validated['type'])
            ->where('category', $validated['category'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'type' => ['A concession rate for this park, type and category already exists.'],
                ],
            ], 422);
        }

        $concessionRate = ConcessionRate::create($validated);
        $concessionRate->load('park');

        return response()->json([
            'message' => 'Concession rate created successfully.',
            'concessionRate' => $this->transform($concessionRate),
        ], 201);
    }

    public function update(Request $request, ConcessionRate $concessionRate): JsonResponse
    {
        $validated = $request->validate([
            'park_id'  => ['sometimes', 'integer', 'exists:parks,id'],
            'type'     => ['sometimes', 'string', Rule::in(self::TYPES)],
            'category' => ['sometimes', 'string', Rule::in(self::CATEGORIES)],
            'rate'     => ['sometimes', 'numeric', 'min:0'],
        ]);

        $checkParkId = $validated['park_id'] ?? $concessionRate->park_id;
        $checkType = $validated['type'] ?? $concessionRate->type;
        $checkCategory = $validated['category'] ?? $concessionRate->category;

        $duplicate = ConcessionRate::query()
            ->where('park_id', $checkParkId)
            ->where('type', $checkType)
            ->where('category', $checkCategory)
            ->where('id', '!=', $concessionRate->id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'type' => ['A concession rate for this park, type and category already exists.'],
                ],
            ], 422);
        }

        $concessionRate->update($validated);
        $concessionRate->refresh()->load('park');

        return response()->json([
            'message' => 'Concession rate updated successfully.',
            'concessionRate' => $this->transform($concessionRate),
        ]);
    }

    public function destroy(ConcessionRate $concessionRate): JsonResponse
    {
        $concessionRate->delete();

        return response()->json([
            'message' => 'Concession rate deleted successfully.',
        ]);
    }

    /** List all concession rates for a specific park, grouped by type → category */
    public function byPark(Park $park): JsonResponse
    {
        $rates = $park->concessionRates()->get();

        $grouped = collect(self::TYPES)->mapWithKeys(function (string $type) use ($rates): array {
            return [$type => collect(self::CATEGORIES)->mapWithKeys(function (string $cat) use ($rates, $type): array {
                $row = $rates->first(fn(ConcessionRate $r) => $r->type === $type && $r->category === $cat);
                return [$cat => $row ? (float) $row->rate : null];
            })];
        });

        return response()->json([
            'message' => 'Concession rates fetched successfully.',
            'park'    => ['id' => $park->id, 'name' => $park->name, 'region' => $park->region],
            'types'   => self::TYPES,
            'concessionRates' => $rates->map(fn(ConcessionRate $concessionRate): array => $this->transform($concessionRate))->values(),
            'rates'   => $grouped,
        ]);
    }

    private function transform(ConcessionRate $concessionRate): array
    {
        return [
            'id' => $concessionRate->id,
            'parkId' => $concessionRate->park_id,
            'parkName' => $concessionRate->relationLoaded('park') ? $concessionRate->park?->name : null,
            'type' => $concessionRate->type,
            'category' => $concessionRate->category,
            'rate' => (float) $concessionRate->rate,
            'createdAt' => $concessionRate->created_at?->toISOString(),
            'updatedAt' => $concessionRate->updated_at?->toISOString(),
        ];
    }
}
