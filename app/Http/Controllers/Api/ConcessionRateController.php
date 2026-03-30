<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConcessionRate;
use App\Models\Park;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConcessionRateController extends Controller
{
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
            'park_id' => ['required', 'integer', 'exists:parks,id'],
            'type' => ['required', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'rate' => ['required', 'numeric', 'min:0'],
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
            'park_id' => ['sometimes', 'integer', 'exists:parks,id'],
            'type' => ['sometimes', 'string', 'max:50'],
            'category' => ['sometimes', 'string', 'max:50'],
            'rate' => ['sometimes', 'numeric', 'min:0'],
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

    public function byPark(Park $park): JsonResponse
    {
        $concessionRates = $park->concessionRates()->orderBy('type')->orderBy('category')->get();

        return response()->json([
            'message' => 'Concession rates fetched successfully.',
            'concessionRates' => $concessionRates->map(fn(ConcessionRate $concessionRate): array => $this->transform($concessionRate))->values(),
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
