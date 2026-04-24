<?php

namespace App\Http\Middleware;

use App\Models\LeadApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateLeadApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Lead-API-Key');

        if (!$apiKey) {
            return response()->json([
                'message' => 'API key is required.',
                'error' => 'missing_api_key',
            ], 401);
        }

        $leadApiKey = LeadApiKey::findByKey($apiKey);

        if (!$leadApiKey) {
            return response()->json([
                'message' => 'Invalid or inactive API key.',
                'error' => 'invalid_api_key',
            ], 401);
        }

        // Store the API key instance in the request for later use
        $request->merge(['_lead_api_key' => $leadApiKey]);

        return $next($request);
    }
}
