<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Client::query()
            ->selectRaw('clients.*, (SELECT COUNT(*) FROM quotations INNER JOIN leads ON leads.id = quotations.lead_id WHERE leads.agent_email = clients.email) AS quotations_count')
            ->latest('clients.id')
            ->get();

        return response()->json([
            'message' => 'Clients fetched successfully.',
            'clients' => $clients->map(fn(Client $client): array => $this->transform($client))->values(),
        ]);
    }

    public function show(Client $client): JsonResponse
    {
        $client = Client::query()
            ->selectRaw('clients.*, (SELECT COUNT(*) FROM quotations INNER JOIN leads ON leads.id = quotations.lead_id WHERE leads.agent_email = clients.email) AS quotations_count')
            ->findOrFail($client->id);

        return response()->json([
            'message' => 'Client fetched successfully.',
            'client' => $this->transform($client),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'email'   => ['nullable', 'email', 'max:255', 'unique:clients,email'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $client = Client::create($validated);

        return response()->json([
            'message' => 'Client created successfully.',
            'client' => $this->transform($client),
        ], 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'name'    => ['sometimes', 'string', 'max:255'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'email'   => ['sometimes', 'nullable', 'email', 'max:255', 'unique:clients,email,' . $client->id],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $client->update($validated);

        return response()->json([
            'message' => 'Client updated successfully.',
            'client' => $this->transform($client),
        ]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Client $client): array
    {
        return [
            'id'              => $client->id,
            'name'            => $client->name,
            'company'         => $client->company,
            'phone'           => $client->phone,
            'email'           => $client->email,
            'address'         => $client->address,
            'totalQuotations' => (int) ($client->quotations_count ?? 0),
            'createdAt'       => $client->created_at?->toIso8601String(),
            'updatedAt'       => $client->updated_at?->toIso8601String(),
        ];
    }
}
