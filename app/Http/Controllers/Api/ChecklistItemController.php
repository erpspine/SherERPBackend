<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistItemController extends Controller
{
    public function store(Request $request, Checklist $checklist): JsonResponse
    {
        if ($response = $this->forbiddenIfNotOwnedByUser($request, $checklist)) {
            return $response;
        }

        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $nextSortOrder = ChecklistItem::query()
            ->where('checklist_id', $checklist->id)
            ->max('sort_order');

        $item = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'text' => $validated['text'],
            'is_completed' => false,
            'sort_order' => $validated['sort_order'] ?? ($nextSortOrder + 1),
        ]);

        return response()->json([
            'message' => 'Checklist item created successfully.',
            'item' => $this->transformItem($item),
        ], 201);
    }

    public function update(Request $request, Checklist $checklist, ChecklistItem $item): JsonResponse
    {
        if ($response = $this->forbiddenIfNotOwnedByUser($request, $checklist)) {
            return $response;
        }

        if ((int) $item->checklist_id !== (int) $checklist->id) {
            return response()->json([
                'message' => 'Checklist item does not belong to this checklist.',
            ], 422);
        }

        $validated = $request->validate([
            'text' => ['sometimes', 'string', 'max:500'],
            'is_completed' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        if (array_key_exists('is_completed', $validated)) {
            $validated['is_completed'] = (bool) $validated['is_completed'];
        }

        $item->update($validated);
        $item->refresh();

        return response()->json([
            'message' => 'Checklist item updated successfully.',
            'item' => $this->transformItem($item),
        ]);
    }

    public function destroy(Request $request, Checklist $checklist, ChecklistItem $item): JsonResponse
    {
        if ($response = $this->forbiddenIfNotOwnedByUser($request, $checklist)) {
            return $response;
        }

        if ((int) $item->checklist_id !== (int) $checklist->id) {
            return response()->json([
                'message' => 'Checklist item does not belong to this checklist.',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'message' => 'Checklist item deleted successfully.',
        ]);
    }

    private function transformItem(ChecklistItem $item): array
    {
        return [
            'id' => $item->id,
            'checklistId' => $item->checklist_id,
            'text' => $item->text,
            'isCompleted' => (bool) $item->is_completed,
            'sortOrder' => (int) $item->sort_order,
            'createdAt' => $item->created_at?->toIso8601String(),
            'updatedAt' => $item->updated_at?->toIso8601String(),
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
