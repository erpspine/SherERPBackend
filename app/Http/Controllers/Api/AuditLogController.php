<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->with('user:id,name,email')->latest();

        if ($request->filled('action') && in_array($request->string('action')->toString(), ['CREATE', 'UPDATE', 'DELETE'], true)) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . addcslashes($request->string('search')->toString(), '%_\\') . '%';

            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery
                    ->where('action', 'like', $search)
                    ->orWhere('method', 'like', $search)
                    ->orWhere('path', 'like', $search)
                    ->orWhere('route_name', 'like', $search)
                    ->orWhere('ip_address', 'like', $search)
                    ->orWhere('status_code', 'like', $search)
                    ->orWhere('route_parameters', 'like', $search)
                    ->orWhere('request_data', 'like', $search)
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', $search)->orWhere('email', 'like', $search);
                    });
            });
        }

        $logs = $query->paginate(min($request->integer('per_page', 25), 100));

        return response()->json($logs);
    }
}
