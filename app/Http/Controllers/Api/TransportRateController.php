<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransportRateController extends Controller
{
    public function index(): JsonResponse
    {
        $transportRates = TransportRate::query()->orderBy('particular')->get();

        return response()->json([
            'message' => 'Transport rates fetched successfully.',
            'transportRates' => $transportRates->map(fn(TransportRate $transportRate): array => $this->transform($transportRate))->values(),
        ]);
    }

    public function show(TransportRate $transportRate): JsonResponse
    {
        return response()->json([
            'message' => 'Transport rate fetched successfully.',
            'transportRate' => $this->transform($transportRate),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'particular' => ['required', 'string', 'max:255', 'unique:transport_rates,particular'],
            'rate' => ['required', 'numeric', 'min:0'],
        ]);

        $transportRate = TransportRate::create($validated);

        return response()->json([
            'message' => 'Transport rate created successfully.',
            'transportRate' => $this->transform($transportRate),
        ], 201);
    }

    public function update(Request $request, TransportRate $transportRate): JsonResponse
    {
        $validated = $request->validate([
            'particular' => ['sometimes', 'string', 'max:255', Rule::unique('transport_rates', 'particular')->ignore($transportRate->id)],
            'rate' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $transportRate->update($validated);
        $transportRate->refresh();

        return response()->json([
            'message' => 'Transport rate updated successfully.',
            'transportRate' => $this->transform($transportRate),
        ]);
    }

    public function destroy(TransportRate $transportRate): JsonResponse
    {
        $transportRate->delete();

        return response()->json([
            'message' => 'Transport rate deleted successfully.',
        ]);
    }

    private function transform(TransportRate $transportRate): array
    {
        return [
            'id' => $transportRate->id,
            'particular' => $transportRate->particular,
            'rate' => (float) $transportRate->rate,
            'createdAt' => $transportRate->created_at?->toISOString(),
            'updatedAt' => $transportRate->updated_at?->toISOString(),
        ];
    }
}
