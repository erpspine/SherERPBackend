<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /** Keys that belong to the company settings group. */
    private const COMPANY_KEYS = [
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'tax_registration_number',
        'default_currency',
        'default_vat',
        'logo',          // stored as a relative storage path
    ];

    // ── GET /api/settings/company ─────────────────────────────────────────────

    public function showCompany(): JsonResponse
    {
        $data = [];

        foreach (self::COMPANY_KEYS as $key) {
            $data[$key] = Setting::get($key);
        }

        // Resolve logo to a public URL if a path is stored
        if (!empty($data['logo'])) {
            $data['logo_url'] = Storage::url($data['logo']);
        } else {
            $data['logo_url'] = null;
        }

        return response()->json([
            'message' => 'Company settings retrieved successfully.',
            'settings' => $data,
        ]);
    }

    // ── PUT /api/settings/company ─────────────────────────────────────────────

    public function updateCompany(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'company_name'           => ['sometimes', 'string', 'max:255'],
            'company_email'          => ['sometimes', 'email', 'max:255'],
            'company_phone'          => ['sometimes', 'string', 'max:50'],
            'company_address'        => ['sometimes', 'string', 'max:500'],
            'tax_registration_number' => ['sometimes', 'string', 'max:100'],
            'default_currency'       => ['sometimes', 'string', 'max:10'],
            'default_vat'            => ['sometimes', 'numeric', 'min:0', 'max:100'],
            // Accept a file upload or a base64 data-URI string
            'logo'                   => ['sometimes', 'nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // ── Handle logo ───────────────────────────────────────────────────────
        if (array_key_exists('logo', $validated)) {
            $logo = $validated['logo'];

            if ($logo === null || $logo === '') {
                // Caller explicitly cleared the logo
                $this->deleteLogo();
                Setting::set('logo', null);
                unset($validated['logo']);
            } elseif ($request->hasFile('logo')) {
                // Multipart file upload
                $path = $request->file('logo')->store('settings', 'public');
                $this->deleteLogo(); // remove old file
                Setting::set('logo', $path);
                unset($validated['logo']);
            } elseif (is_string($logo) && str_starts_with($logo, 'data:')) {
                // Base64 data-URI  e.g. "data:image/png;base64,iVBOR..."
                $path = $this->storeBase64Logo($logo);
                if ($path === null) {
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors'  => ['logo' => ['Invalid base64 image data.']],
                    ], 422);
                }
                $this->deleteLogo();
                Setting::set('logo', $path);
                unset($validated['logo']);
            } else {
                // Unknown logo value — ignore silently
                unset($validated['logo']);
            }
        }

        // ── Persist remaining scalar settings ─────────────────────────────────
        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return $this->showCompany();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function storeBase64Logo(string $dataUri): ?string
    {
        // Parse  data:<mime>;base64,<data>
        if (!preg_match('/^data:(image\/[a-z]+);base64,(.+)$/i', $dataUri, $matches)) {
            return null;
        }

        $mime      = strtolower($matches[1]);
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => null,
        };

        if ($extension === null) {
            return null;
        }

        $decoded = base64_decode($matches[2], strict: true);
        if ($decoded === false) {
            return null;
        }

        $path = 'settings/logo.' . $extension;
        Storage::disk('public')->put($path, $decoded);
        return $path;
    }

    private function deleteLogo(): void
    {
        $existing = Setting::get('logo');
        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }
    }
}
