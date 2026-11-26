<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The logged-in user's own profile. Unlike PUT /users/{user}, this needs no
 * users.update permission and only touches personal contact details.
 */
class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['profile' => $this->payload($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $name = trim((string) ($validated['name'] ?? ''));
        if ($name === '') {
            $name = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        }
        if ($name === '') {
            return response()->json([
                'message' => 'Please enter your name.',
                'errors' => ['first_name' => ['Please enter your name.']],
            ], 422);
        }

        $user->update([
            'name' => $name,
            'email' => $validated['email'],
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $user->phone,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'profile' => $this->payload($user->refresh()),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(User $user): array
    {
        $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];

        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => $user->getRoleNames()->values(),
        ];
    }
}
