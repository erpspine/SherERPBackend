<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SignatureImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The logged-in user's saved signature. Managers save it once and it is
 * applied to the "Manager Approval" box of every inspection they submit.
 */
class UserSignatureController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->payload($request->user()));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signature' => ['required', 'string', 'max:4000000'],
        ]);

        $path = SignatureImage::storeBase64Png($validated['signature'], 'user-signatures');
        if ($path === null) {
            return response()->json([
                'message' => 'The signature must be a PNG image.',
                'errors' => ['signature' => ['The signature must be a PNG image.']],
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $previous = $user->signature_path;
        $user->forceFill(['signature_path' => $path])->save();

        if ($previous && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }

        return response()->json($this->payload($user) + ['message' => 'Signature saved.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
            $user->forceFill(['signature_path' => null])->save();
        }

        return response()->json($this->payload($user) + ['message' => 'Signature removed.']);
    }

    /** @return array<string, mixed> */
    private function payload(User $user): array
    {
        return [
            'name' => $user->name,
            'signature' => $user->signature_path,
            'signatureUrl' => $user->signature_path ? Storage::url($user->signature_path) : null,
        ];
    }
}
