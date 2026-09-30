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

        $logs = $query->paginate(min($request->integer('per_page', 25), 100));

        return response()->json($logs);
    }
}
