<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDeletes
{
    private const ALLOWED_EMAILS = [
        'tanveer@sher.co.tz',
        'arif@sher.co.tz',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('DELETE')) {
            return $next($request);
        }

        $email = strtolower(trim((string) $request->user()?->email));

        $isFuelManagementDelete = $request->is('api/fuel-management/*');
        $isTahirFuelDelete = $isFuelManagementDelete && $email === 'tahir@sher.co.tz';

        if (! $isTahirFuelDelete && ! in_array($email, self::ALLOWED_EMAILS, true)) {
            return response()->json([
                'message' => 'You are not authorized to delete records.',
                'error' => 'delete_forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
