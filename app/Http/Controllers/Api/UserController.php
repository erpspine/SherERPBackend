<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\UserCreatedMail;
use App\Models\User;
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

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->with('roles:id,name')
            ->latest('id')
            ->get(['id', 'name', 'email', 'phone', 'role', 'status', 'receive_notifications', 'last_login_at', 'created_at']);

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
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'If the email exists, a reset token has been generated.',
            ]);
        }

        /** @var PasswordBroker $passwordBroker */
        $passwordBroker = Password::broker();
        $token = $passwordBroker->createToken($user);

        return response()->json([
            'message' => 'Password reset token generated successfully.',
            'resetToken' => $token,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            [
                'email' => $validated['email'],
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
            'role' => ['nullable', 'string', Rule::in($this->availableRoles())],
            'roles' => ['nullable', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in($this->availableRoles())],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'receive_notifications' => ['sometimes', 'boolean'],
        ]);

        $roles = $this->extractRoleNames($validated, true);
        $primaryRole = $roles[0];

        $plainPassword = Str::random(12);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => $primaryRole,
                'status' => $validated['status'],
                'receive_notifications' => (bool) ($validated['receive_notifications'] ?? false),
                'password' => $plainPassword,
            ]);

            $user->syncRoles($roles);
            $user->loadMissing('roles:id,name');

            Mail::to($user->email)->send(new UserCreatedMail($user, $plainPassword));

            DB::commit();

            return response()->json([
                'message' => 'User created and credentials sent by email.',
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
            'role' => ['nullable', 'string', Rule::in($this->availableRoles())],
            'roles' => ['nullable', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in($this->availableRoles())],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'receive_notifications' => ['sometimes', 'boolean'],
        ]);

        $roles = $this->extractRoleNames($validated, false, $user);
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $roles[0] ?? $user->role,
            'status' => $validated['status'],
            'receive_notifications' => (bool) ($validated['receive_notifications'] ?? false),
        ]);
        $user->syncRoles($roles);
        $user->loadMissing('roles:id,name');

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $this->transformUser($user),
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
        $roles = config('access.roles', []);

        return response()->json([
            'roles' => collect($roles)
                ->map(fn (array $permissions, string $name): array => [
                    'name' => $name,
                    'permissions' => $permissions === ['*'] ? $allPermissions : $permissions,
                ])
                ->values(),
            'permissions' => $allPermissions,
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

        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role ?? $roles->first(),
            'roles' => $roles,
            'status' => $user->status,
            'receive_notifications' => (bool) $user->receive_notifications,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
        ];

        if ($includePermissions) {
            $payload['permissions'] = $user->getPermissionNames()->values();
        }

        return $payload;
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
            'permissions' => $user->getPermissionNames()->values(),
        ];

        if ($token !== null) {
            $payload['token'] = $token;
            $payload['token_type'] = 'Bearer';
        }

        return $payload;
    }
}
