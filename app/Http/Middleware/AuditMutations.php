<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMutations
{
    private const METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private const IGNORED_PATHS = [
        'login',
        'logout',
        'forgot-password',
        'reset-password',
        'change-password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array(strtoupper($request->method()), self::METHODS, true)) {
            return $next($request);
        }

        $response = $next($request);

        if (! in_array(trim($request->path(), '/'), self::IGNORED_PATHS, true)) {
            try {
                AuditLog::create([
                    'user_id' => $request->user()?->id,
                    'action' => $this->actionFor($request),
                    'method' => strtoupper($request->method()),
                    'path' => '/' . ltrim($request->path(), '/'),
                    'route_name' => $request->route()?->getName(),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 1024) ?: null,
                    'status_code' => $response->getStatusCode(),
                    'route_parameters' => $this->sanitize($request->route()?->parameters() ?? []),
                    'request_data' => $this->sanitize($request->except([
                        'password',
                        'password_confirmation',
                        'current_password',
                        'token',
                        'api_key',
                        'photo',
                        'attachment',
                    ])),
                ]);
            } catch (\Throwable) {
                // Audit failures must never break the business operation.
            }
        }

        return $response;
    }

    private function actionFor(Request $request): string
    {
        return match (strtoupper($request->method())) {
            'DELETE' => 'DELETE',
            'PUT', 'PATCH' => 'UPDATE',
            default => 'CREATE',
        };
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->sanitize($item), $value);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }
}
