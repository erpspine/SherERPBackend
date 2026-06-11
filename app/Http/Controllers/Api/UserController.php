<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\UserCreatedMail;
use App\Mail\UserPasswordResetMail;
use App\Models\User;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->with('roles:id,name')
            ->latest('id')
            ->get([
                'id',
                'name',
                'email',
                'phone',
                'languages_spoken',
                'work_experience',
                'driving_started_at',
                'role',
                'status',
                'receive_notifications',
                'last_login_at',
                'created_at',
            ]);

        return response()->json([
            'message' => 'Users fetched successfully.',
            'users' => $users->map(fn (User $user): array => $this->transformUser($user))->values(),
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadMissing('roles:id,name');

        return response()->json([
            'message' => 'User fetched successfully.',
            'user' => $this->transformUser($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('roles:id,name');

        return response()->json($this->authenticationPayload($user, 'Authenticated user fetched successfully.'));
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $user->loadMissing('roles:id,name');
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json($this->authenticationPayload($user, 'Login successful.', $token));
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim((string) $validated['identifier']);
        $user = $this->resolveUserByIdentifier($identifier);
        $looksLikeEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        if (! $user) {
            return response()->json([
                'message' => 'If the account exists, a reset token has been sent.',
            ]);
        }

        /** @var PasswordBroker $passwordBroker */
        $passwordBroker = Password::broker();
        $token = $passwordBroker->createToken($user);

        $smsSent = false;
        $emailSent = false;

        if (! $looksLikeEmail && $user->phone) {
            $smsBody = implode("\n", [
                'Your password reset code is: ' . $token,
                'Use this code in the app to set a new password.',
            ]);
            $smsSent = app(SmsService::class)->send($user->phone, $smsBody);
        }

        if ($looksLikeEmail && $user->email) {
            try {
                Mail::raw(
                    "Your password reset code is: {$token}\nUse this code in the app to set a new password.",
                    function ($message) use ($user): void {
                        $message->to($user->email)
                            ->subject(config('app.name', 'SHER ERP') . ' Password Reset Code');
                    }
                );
                $emailSent = true;
            } catch (\Throwable) {
                // Keep the endpoint resilient even when mail transport is unavailable.
                $emailSent = false;
            }
        }

        return response()->json([
            'message' => ($looksLikeEmail ? $emailSent : $smsSent)
                ? 'Password reset token sent successfully.'
                : 'Password reset token generated successfully.',
            'channel' => $looksLikeEmail ? 'email' : 'sms',
            // Fallback for environments where mail/SMS transport is not configured.
            'resetToken' => (($looksLikeEmail && ! $emailSent) || (! $looksLikeEmail && ! $smsSent))
                ? $token
                : null,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $identifier = trim((string) $validated['identifier']);
        $user = $this->resolveUserByIdentifier($identifier);

        if (! $user || ! $user->email) {
            return response()->json([
                'message' => 'Unable to resolve account for password reset.',
                'errors' => [
                    'identifier' => ['Account not found for the provided email or phone number.'],
                ],
            ], 422);
        }

        $status = Password::broker()->reset(
            [
                'email' => $user->email,
                'token' => $validated['token'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }

    private function resolveUserByIdentifier(string $identifier): ?User
    {
        $trimmed = trim($identifier);
        if ($trimmed === '') {
            return null;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) !== false) {
            return User::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($trimmed)])
                ->first();
        }

        $smsService = app(SmsService::class);
        $normalizedPhone = $smsService->formatPhoneNumber($trimmed);

        $candidates = array_values(array_unique(array_filter([
            $trimmed,
            preg_replace('/[^0-9]/', '', $trimmed) ?? '',
            $normalizedPhone,
        ])));

        if (empty($candidates)) {
            return null;
        }

        return User::query()->whereIn('phone', $candidates)->first();
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        if (! $user || ! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'current_password' => ['Current password is incorrect.'],
                ],
            ], 422);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'languages_spoken' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_string($value) || is_array($value) || $value === null) {
                    return;
                }

                $fail('Languages spoken must be a string or an array of strings.');
            }],
            'languages_spoken.*' => ['nullable', 'string', 'max:50'],
            'driving_started_at' => ['nullable', 'date', 'before_or_equal:today'],
            'role' => ['nullable', 'string', Rule::in($this->availableRoles())],
            'roles' => ['nullable', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in($this->availableRoles())],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'receive_notifications' => ['sometimes', 'boolean'],
            'send_sms' => ['sometimes', 'boolean'],
        ]);

        $roles = $this->extractRoleNames($validated, true);
        $primaryRole = $roles[0];
        $driverProfile = $this->extractDriverProfileFields($validated, $primaryRole, true);

        $plainPassword = Str::random(12);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'languages_spoken' => $driverProfile['languages_spoken'],
                'work_experience' => $driverProfile['work_experience'],
                'driving_started_at' => $driverProfile['driving_started_at'],
                'role' => $primaryRole,
                'status' => $validated['status'],
                'receive_notifications' => (bool) ($validated['receive_notifications'] ?? false),
                'password' => $plainPassword,
            ]);

            $user->syncRoles($roles);
            $user->loadMissing('roles:id,name');

            Mail::to($user->email)->send(new UserCreatedMail($user, $plainPassword));

            $smsSent = false;
            if (! empty($validated['send_sms']) && $user->phone) {
                $smsSent = app(SmsService::class)->send(
                    $user->phone,
                    $this->buildCredentialsSmsBody($user->name, $user->email, $plainPassword)
                );
            }

            DB::commit();

            return response()->json([
                'message' => $smsSent
                    ? 'User created. Credentials sent by email and SMS.'
                    : 'User created and credentials sent by email.',
                'sms_sent' => $smsSent,
                'user' => $this->transformUser($user),
            ], 201);
        } catch (\Throwable $exception) {
            DB::rollBack();
            report($exception);

            return response()->json([
                'message' => 'User could not be created.',
            ], 500);
        }
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30'],
            'languages_spoken' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_string($value) || is_array($value) || $value === null) {
                    return;
                }

                $fail('Languages spoken must be a string or an array of strings.');
            }],
            'languages_spoken.*' => ['nullable', 'string', 'max:50'],
            'driving_started_at' => ['nullable', 'date', 'before_or_equal:today'],
            'role' => ['nullable', 'string', Rule::in($this->availableRoles())],
            'roles' => ['nullable', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in($this->availableRoles())],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'receive_notifications' => ['sometimes', 'boolean'],
        ]);

        $roles = $this->extractRoleNames($validated, false, $user);
        $primaryRole = $roles[0] ?? $user->role;
        $driverProfile = $this->extractDriverProfileFields($validated, $primaryRole, false, $user);
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'languages_spoken' => $driverProfile['languages_spoken'],
            'work_experience' => $driverProfile['work_experience'],
            'driving_started_at' => $driverProfile['driving_started_at'],
            'role' => $primaryRole,
            'status' => $validated['status'],
            'receive_notifications' => (bool) ($validated['receive_notifications'] ?? false),
        ]);
        $user->syncRoles($roles);
        $user->loadMissing('roles:id,name');

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $this->transformUser($user, true),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function adminResetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'send_sms' => ['sometimes', 'boolean'],
        ]);

        $plainPassword = Str::random(12);

        DB::beginTransaction();

        try {
            $user->forceFill([
                'password' => $plainPassword,
                'remember_token' => Str::random(60),
            ])->save();

            Mail::to($user->email)->send(new UserPasswordResetMail($user, $plainPassword));

            $smsSent = false;
            if (! empty($validated['send_sms']) && $user->phone) {
                $smsSent = app(SmsService::class)->send(
                    $user->phone,
                    $this->buildCredentialsSmsBody($user->name, $user->email, $plainPassword, true)
                );
            }

            DB::commit();

            return response()->json([
                'message' => $smsSent
                    ? 'Password reset. New credentials sent by email and SMS.'
                    : 'Password reset and new credentials sent by email.',
                'sms_sent' => $smsSent,
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            report($exception);

            return response()->json([
                'message' => 'Password could not be reset.',
            ], 500);
        }
    }

    private function buildCredentialsSmsBody(string $name, string $email, string $password, bool $isReset = false): string
    {
        $appName = config('app.name', 'SHER ERP');
        $url = config('app.frontend_url', config('app.url', ''));
        $intro = $isReset
            ? "Your {$appName} password has been reset."
            : "Welcome to {$appName}, {$name}.";

        $lines = [
            $intro,
            "Email: {$email}",
            "Password: {$password}",
        ];
        if (is_string($url) && $url !== '') {
            $lines[] = "Login: {$url}";
        }

        return implode("\n", $lines);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'roles' => $this->availableRoles(),
        ]);
    }

    public function permissions(): JsonResponse
    {
        return response()->json([
            'permissions' => config('access.permissions', []),
        ]);
    }

    public function rolePermissions(): JsonResponse
    {
        $allPermissions = config('access.permissions', []);
        $roleNames = $this->availableRoles();

        $roles = collect($roleNames)
            ->map(function (string $roleName) {
                $role = Role::findOrCreate($roleName, 'web');
                $role->loadMissing('permissions:id,name');

                return [
                    'name' => $roleName,
                    'permissions' => $role->permissions->pluck('name')->values(),
                ];
            })
            ->values();

        return response()->json([
            'roles' => $roles,
            'permissions' => $allPermissions,
        ]);
    }

    public function updateRolePermissions(Request $request, string $role): JsonResponse
    {
        $availableRoles = $this->availableRoles();
        if (! in_array($role, $availableRoles, true)) {
            throw ValidationException::withMessages([
                'role' => ['Invalid role selected.'],
            ]);
        }

        $allowedPermissions = config('access.permissions', []);
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', Rule::in($allowedPermissions)],
        ]);

        $permissions = array_values(array_unique($validated['permissions'] ?? []));

        $targetRole = Role::findOrCreate($role, 'web');
        $targetRole->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'message' => 'Role permissions updated successfully.',
            'role' => [
                'name' => $targetRole->name,
                'permissions' => $targetRole->permissions()->pluck('name')->values(),
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function availableRoles(): array
    {
        return array_keys(config('access.roles', []));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, string>
     */
    private function extractRoleNames(array $validated, bool $required, ?User $user = null): array
    {
        $roles = array_values(array_unique(array_filter(Arr::wrap($validated['roles'] ?? $validated['role'] ?? []))));

        if ($roles === [] && $user) {
            $roles = $user->roles()->pluck('name')->all();
        }

        if ($required && $roles === []) {
            throw ValidationException::withMessages([
                'roles' => ['At least one valid role is required.'],
            ]);
        }

        return $roles;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformUser(User $user, bool $includePermissions = false): array
    {
        $user->loadMissing('roles:id,name');
        $roles = $user->roles->pluck('name')->values();
        $languages = $this->explodeLanguages($user->languages_spoken);
        $drivingStartedAt = $this->normalizeDateString($user->driving_started_at);
        $computedExperience = $this->calculateExperienceFromStartDate($drivingStartedAt);
        $experience = $computedExperience ?? $user->work_experience;

        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'languages_spoken' => $user->languages_spoken,
            'languages_spoken_list' => $languages,
            'driving_started_at' => $drivingStartedAt,
            'work_experience' => $experience,
            'languagesSpoken' => $user->languages_spoken,
            'languagesSpokenList' => $languages,
            'drivingStartedAt' => $drivingStartedAt,
            'workExperience' => $experience,
            'role' => $user->role ?? $roles->first(),
            'roles' => $roles,
            'status' => $user->status,
            'receive_notifications' => (bool) $user->receive_notifications,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
        ];

        if ($includePermissions) {
            $payload['permissions'] = $user->getAllPermissions()->pluck('name')->values();
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{languages_spoken: ?string, work_experience: null, driving_started_at: ?string}
     */
    private function extractDriverProfileFields(
        array $validated,
        ?string $role,
        bool $required,
        ?User $user = null,
    ): array {
        $languages = array_key_exists('languages_spoken', $validated)
            ? $this->normalizeLanguagesForStorage($validated['languages_spoken'])
            : ($user?->languages_spoken ?? null);
        $drivingStartedAt = isset($validated['driving_started_at'])
            ? (string) $validated['driving_started_at']
            : $this->normalizeDateString($user?->driving_started_at);

        $languages = $languages === '' ? null : $languages;
        $drivingStartedAt = $drivingStartedAt === '' ? null : $drivingStartedAt;

        if ($role === 'Driver') {
            if ($required && ($languages === null || $drivingStartedAt === null)) {
                throw ValidationException::withMessages([
                    'languages_spoken' => ['Languages spoken is required for drivers.'],
                    'driving_started_at' => ['Driving start date is required for drivers.'],
                ]);
            }

            return [
                'languages_spoken' => $languages,
                'work_experience' => null,
                'driving_started_at' => $drivingStartedAt,
            ];
        }

        return [
            'languages_spoken' => null,
            'work_experience' => null,
            'driving_started_at' => null,
        ];
    }

    private function calculateExperienceFromStartDate(?string $startDate): ?string
    {
        if ($startDate === null || trim($startDate) === '') {
            return null;
        }

        try {
            $started = Carbon::parse($startDate)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        $today = Carbon::today();
        if ($started->greaterThan($today)) {
            return null;
        }

        $years = $started->diffInYears($today);
        $months = $started->copy()->addYears($years)->diffInMonths($today);

        if ($years <= 0 && $months <= 0) {
            return 'Less than 1 month';
        }

        $parts = [];
        if ($years > 0) {
            $parts[] = $years === 1 ? '1 year' : $years.' years';
        }
        if ($months > 0) {
            $parts[] = $months === 1 ? '1 month' : $months.' months';
        }

        return implode(', ', $parts);
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        return null;
    }

    /**
     * @param  mixed  $value
     */
    private function normalizeLanguagesForStorage(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $items = is_array($value) ? $value : explode(',', (string) $value);

        $languages = collect($items)
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter(fn (string $item): bool => $item !== '')
            ->unique()
            ->values();

        if ($languages->isEmpty()) {
            return null;
        }

        $normalized = $languages->implode(', ');

        if (strlen($normalized) > 255) {
            throw ValidationException::withMessages([
                'languages_spoken' => ['Languages spoken exceeds the maximum length of 255 characters.'],
            ]);
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function explodeLanguages(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $item): string => trim($item))
            ->filter(fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function authenticationPayload(User $user, string $message, ?string $token = null): array
    {
        $payload = [
            'message' => $message,
            'user' => $this->transformUser($user),
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];

        if ($token !== null) {
            $payload['token'] = $token;
            $payload['token_type'] = 'Bearer';
        }

        return $payload;
    }
}
