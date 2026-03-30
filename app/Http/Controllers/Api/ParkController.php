<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Park;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParkController extends Controller
{
    public function index(): JsonResponse
    {
        $parks = Park::query()->orderBy('name')->get();

        return response()->json([
            'message' => 'Parks fetched successfully.',
            'parks' => $parks->map(fn(Park $park): array => $this->transform($park))->values(),
        ]);
    }

    public function show(Park $park): JsonResponse
    {
        return response()->json([
            'message' => 'Park fetched successfully.',
            'park' => $this->transform($park),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255', 'unique:parks,name'],
            'region' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $park = Park::create($validated);

        return response()->json([
            'message' => 'Park created successfully.',
            'park' => $this->transform($park),
        ], 201);
    }

    public function update(Request $request, Park $park): JsonResponse
    {
        $validated = $request->validate([
            'name'   => ['sometimes', 'string', 'max:255', Rule::unique('parks', 'name')->ignore($park->id)],
            'region' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', Rule::in(['Active', 'Inactive'])],
        ]);

        $park->update($validated);
        $park->refresh();

        return response()->json([
            'message' => 'Park updated successfully.',
            'park' => $this->transform($park),
        ]);
    }

    public function destroy(Park $park): JsonResponse
    {
        $park->delete();

        return response()->json([
            'message' => 'Park deleted successfully.',
        ]);
    }

    private function transform(Park $park): array
    {
        return [
            'id'        => $park->id,
            'name'      => $park->name,
            'region'    => $park->region,
            'status'    => $park->status,
            'createdAt' => $park->created_at?->toISOString(),
            'updatedAt' => $park->updated_at?->toISOString(),
        ];
    }
}
