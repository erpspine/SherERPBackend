<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChecklistController extends Controller
{
    private const CHECKLIST_TYPES = [
        'pre_departure',
        'post_departure',
    ];

    public function index(Request $request): JsonResponse
    {
        $checklists = Checklist::query()
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->orderBy('checklist_type')
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Checklists fetched successfully.',
            'checklists' => $checklists->map(fn(Checklist $checklist): array => $this->transformChecklist($checklist))->values(),
        ]);
    }

    public function show(Request $request, Checklist $checklist): JsonResponse
    {
        if ($response = $this->forbiddenIfNotOwnedByUser($request, $checklist)) {
            return $response;
        }

        $checklist->load('items');

        return response()->json([
            'message' => 'Checklist fetched successfully.',
            'checklist' => $this->transformChecklist($checklist),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'checklist_type' => ['required', Rule::in(self::CHECKLIST_TYPES)],
        ]);

        $checklist = Checklist::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'checklist_type' => $validated['checklist_type'],
        ]);

        $checklist->load('items');

        return response()->json([
            'message' => 'Checklist created successfully.',
            'checklist' => $this->transformChecklist($checklist),
        ], 201);
    }

    public function update(Request $request, Checklist $checklist): JsonResponse
    {
        if ($response = $this->forbiddenIfNotOwnedByUser($request, $checklist)) {
            return $response;
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'checklist_type' => ['sometimes', Rule::in(self::CHECKLIST_TYPES)],
        ]);

        $checklist->update($validated);
        $checklist->refresh()->load('items');

        return response()->json([
            'message' => 'Checklist updated successfully.',
            'checklist' => $this->transformChecklist($checklist),
        ]);
    }

    public function destroy(Request $request, Checklist $checklist): JsonResponse
    {
        if ($response = $this->forbiddenIfNotOwnedByUser($request, $checklist)) {
            return $response;
        }

        $checklist->delete();

        return response()->json([
            'message' => 'Checklist deleted successfully.',
        ]);
    }

    public static function transformChecklist(Checklist $checklist): array
    {
        return [
            'id' => $checklist->id,
            'title' => $checklist->title,
            'checklistType' => $checklist->checklist_type,
            'items' => $checklist->items->map(fn($item): array => [
                'id' => $item->id,
                'text' => $item->text,
                'isCompleted' => (bool) $item->is_completed,
                'sortOrder' => (int) $item->sort_order,
                'createdAt' => $item->created_at?->toIso8601String(),
                'updatedAt' => $item->updated_at?->toIso8601String(),
            ])->values(),
            'createdAt' => $checklist->created_at?->toIso8601String(),
            'updatedAt' => $checklist->updated_at?->toIso8601String(),
        ];
    }

    private function forbiddenIfNotOwnedByUser(Request $request, Checklist $checklist): ?JsonResponse
    {
        if ((int) $checklist->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to access this checklist.',
            ], 403);
        }

        return null;
    }
}
