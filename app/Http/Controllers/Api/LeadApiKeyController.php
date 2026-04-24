<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadApiKeyController extends Controller
{
    public function index(): JsonResponse
    {
        $apiKeys = LeadApiKey::latest('id')->get();

        return response()->json([
            'message' => 'API keys fetched successfully.',
            'apiKeys' => $apiKeys->map(fn(LeadApiKey $key): array => $this->transform($key))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website_url' => ['required', 'url', 'max:255'],
        ]);

        // Generate a new API key
        $plainKey = LeadApiKey::generateKey();

        $apiKey = LeadApiKey::create([
            'name' => $validated['name'],
            'website_url' => $validated['website_url'],
            'key' => hash('sha256', $plainKey),
            'active' => true,
        ]);

        return response()->json([
            'message' => 'API key created successfully.',
            'apiKey' => [
                ...$this->transform($apiKey),
                'plainKey' => $plainKey, // Return the plain key only once (display to user to copy)
            ],
        ], 201);
    }

    public function show(LeadApiKey $apiKey): JsonResponse
    {
        return response()->json([
            'message' => 'API key fetched successfully.',
            'apiKey' => $this->transform($apiKey),
        ]);
    }

    public function update(Request $request, LeadApiKey $apiKey): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'website_url' => ['sometimes', 'url', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $apiKey->update($validated);

        return response()->json([
            'message' => 'API key updated successfully.',
            'apiKey' => $this->transform($apiKey),
        ]);
    }

    public function destroy(LeadApiKey $apiKey): JsonResponse
    {
        $apiKey->delete();

        return response()->json([
            'message' => 'API key deleted successfully.',
        ]);
    }

    public function regenerate(LeadApiKey $apiKey): JsonResponse
    {
        $plainKey = LeadApiKey::generateKey();

        $apiKey->update([
            'key' => hash('sha256', $plainKey),
        ]);

        return response()->json([
            'message' => 'API key regenerated successfully.',
            'apiKey' => [
                ...$this->transform($apiKey),
                'plainKey' => $plainKey,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(LeadApiKey $apiKey): array
    {
        $parsedHost = parse_url((string) $apiKey->website_url, PHP_URL_HOST);
        $localDomain = is_string($parsedHost) && $parsedHost !== ''
            ? $parsedHost
            : $apiKey->website_url;

        return [
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'website_url' => $apiKey->website_url,
            'localDomain' => $localDomain,
            'active' => $apiKey->active,
            'last_used_at' => $apiKey->last_used_at?->toISOString(),
            'created_at' => $apiKey->created_at?->toISOString(),
            'updated_at' => $apiKey->updated_at?->toISOString(),
        ];
    }
}
