<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = InventoryCategory::query()
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Inventory categories fetched successfully.',
            'categories' => $categories->map(fn(InventoryCategory $category): array => $this->transformCategory($category))->values(),
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:inventory_categories,name'],
            'description' => ['nullable', 'string', 'max:2000'],
            'isActive' => ['sometimes', 'boolean'],
        ]);

        $category = InventoryCategory::create([
            'name' => trim((string) $validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
        ]);

        $category->loadCount('items');

        return response()->json([
            'message' => 'Inventory category created successfully.',
            'category' => $this->transformCategory($category),
        ], 201);
    }

    public function updateCategory(Request $request, InventoryCategory $inventoryCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('inventory_categories', 'name')->ignore($inventoryCategory->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'isActive' => ['sometimes', 'boolean'],
        ]);

        $payload = [];
        if (array_key_exists('name', $validated)) {
            $payload['name'] = trim((string) $validated['name']);
        }
        if (array_key_exists('description', $validated)) {
            $payload['description'] = $validated['description'];
        }
        if (array_key_exists('isActive', $validated)) {
            $payload['is_active'] = (bool) $validated['isActive'];
        }

        $inventoryCategory->update($payload);
        $inventoryCategory->refresh()->loadCount('items');

        return response()->json([
            'message' => 'Inventory category updated successfully.',
            'category' => $this->transformCategory($inventoryCategory),
        ]);
    }

    public function destroyCategory(InventoryCategory $inventoryCategory): JsonResponse
    {
        if ($inventoryCategory->items()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Cannot delete a category that has items.',
            ]);
        }

        $inventoryCategory->delete();

        return response()->json([
            'message' => 'Inventory category deleted successfully.',
        ]);
    }

    public function items(Request $request): JsonResponse
    {
        $query = InventoryItem::query()->with('category');

        $categoryId = (int) $request->input('categoryId', 0);
        if ($categoryId > 0) {
            $query->where('inventory_category_id', $categoryId);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhere('unit', 'like', '%' . $search . '%')
                    ->orWhereHas('category', function ($categoryQuery) use ($search): void {
                        $categoryQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->boolean('lowStock')) {
            $query->whereColumn('current_stock', '<=', 'reorder_level');
        }

        $items = $query->orderBy('name')->get();

        return response()->json([
            'message' => 'Inventory items fetched successfully.',
            'items' => $items->map(fn(InventoryItem $item): array => $this->transformItem($item))->values(),
        ]);
    }

    public function showItem(InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->loadMissing('category');

        return response()->json([
            'message' => 'Inventory item fetched successfully.',
            'item' => $this->transformItem($inventoryItem),
        ]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categoryId' => ['required', 'integer', 'exists:inventory_categories,id'],
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:80', 'unique:inventory_items,sku'],
            'unit' => ['required', 'string', 'max:40'],
            'reorderLevel' => ['nullable', 'numeric', 'min:0'],
            'openingStock' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'isActive' => ['sometimes', 'boolean'],
        ]);

        $item = InventoryItem::create([
            'inventory_category_id' => (int) $validated['categoryId'],
            'name' => trim((string) $validated['name']),
            'sku' => isset($validated['sku']) ? trim((string) $validated['sku']) : null,
            'unit' => trim((string) $validated['unit']),
            'reorder_level' => (float) ($validated['reorderLevel'] ?? 0),
            // Start at zero so OPENING movement establishes the initial balance once.
            'current_stock' => 0,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
        ]);

        $item->loadMissing('category');

        if (((float) ($validated['openingStock'] ?? 0)) > 0) {
            $this->createMovement(
                inventoryItemId: $item->id,
                movementType: 'IN',
                quantity: (float) $validated['openingStock'],
                vehicleId: null,
                issuedToUserId: null,
                reference: 'OPENING',
                notes: 'Opening stock',
                movementDate: now()->toISOString(),
                userId: (int) $request->user()->id,
            );

            $item->refresh()->loadMissing('category');
        }

        return response()->json([
            'message' => 'Inventory item created successfully.',
            'item' => $this->transformItem($item),
        ], 201);
    }

    public function updateItem(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $validated = $request->validate([
            'categoryId' => ['sometimes', 'integer', 'exists:inventory_categories,id'],
            'name' => ['sometimes', 'string', 'max:180'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:80', Rule::unique('inventory_items', 'sku')->ignore($inventoryItem->id)],
            'unit' => ['sometimes', 'string', 'max:40'],
            'reorderLevel' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'isActive' => ['sometimes', 'boolean'],
        ]);

        $payload = [];
        if (array_key_exists('categoryId', $validated)) {
            $payload['inventory_category_id'] = (int) $validated['categoryId'];
        }
        if (array_key_exists('name', $validated)) {
            $payload['name'] = trim((string) $validated['name']);
        }
        if (array_key_exists('sku', $validated)) {
            $payload['sku'] = $validated['sku'] !== null ? trim((string) $validated['sku']) : null;
        }
        if (array_key_exists('unit', $validated)) {
            $payload['unit'] = trim((string) $validated['unit']);
        }
        if (array_key_exists('reorderLevel', $validated)) {
            $payload['reorder_level'] = (float) $validated['reorderLevel'];
        }
        if (array_key_exists('description', $validated)) {
            $payload['description'] = $validated['description'];
        }
        if (array_key_exists('isActive', $validated)) {
            $payload['is_active'] = (bool) $validated['isActive'];
        }

        $inventoryItem->update($payload);
        $inventoryItem->refresh()->loadMissing('category');

        return response()->json([
            'message' => 'Inventory item updated successfully.',
            'item' => $this->transformItem($inventoryItem),
        ]);
    }

    public function destroyItem(InventoryItem $inventoryItem): JsonResponse
    {
        if ($inventoryItem->movements()->exists()) {
            throw ValidationException::withMessages([
                'item' => 'Cannot delete an item that already has stock movements.',
            ]);
        }

        $inventoryItem->delete();

        return response()->json([
            'message' => 'Inventory item deleted successfully.',
        ]);
    }

    public function stockIn(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'movementDate' => ['nullable', 'date'],
        ]);

        $movement = $this->createMovement(
            inventoryItemId: $inventoryItem->id,
            movementType: 'IN',
            quantity: (float) $validated['quantity'],
            vehicleId: null,
            issuedToUserId: null,
            reference: $validated['reference'] ?? null,
            notes: $validated['notes'] ?? null,
            movementDate: $validated['movementDate'] ?? now()->toISOString(),
            userId: (int) $request->user()->id,
        );

        return response()->json([
            'message' => 'Stock added successfully.',
            'movement' => $this->transformMovement($movement),
            'item' => $this->transformItem($movement->item),
        ]);
    }

    public function issue(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'vehicleId' => ['nullable', 'integer', 'exists:vehicles,id'],
            'issuedToUserId' => ['nullable', 'integer', 'exists:users,id'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'movementDate' => ['nullable', 'date'],
        ]);

        $movement = $this->createMovement(
            inventoryItemId: $inventoryItem->id,
            movementType: 'OUT',
            quantity: (float) $validated['quantity'],
            vehicleId: isset($validated['vehicleId']) ? (int) $validated['vehicleId'] : null,
            issuedToUserId: isset($validated['issuedToUserId']) ? (int) $validated['issuedToUserId'] : null,
            reference: $validated['reference'] ?? null,
            notes: $validated['notes'] ?? null,
            movementDate: $validated['movementDate'] ?? now()->toISOString(),
            userId: (int) $request->user()->id,
        );

        return response()->json([
            'message' => 'Item issued successfully.',
            'movement' => $this->transformMovement($movement),
            'item' => $this->transformItem($movement->item),
        ]);
    }

    public function movements(Request $request): JsonResponse
    {
        $query = InventoryMovement::query()
            ->with(['item.category', 'vehicle', 'issuedToUser:id,name,email', 'createdBy:id,name,email']);

        $itemId = (int) $request->input('itemId', 0);
        if ($itemId > 0) {
            $query->where('inventory_item_id', $itemId);
        }

        $categoryId = (int) $request->input('categoryId', 0);
        if ($categoryId > 0) {
            $query->whereHas('item', fn($q) => $q->where('inventory_category_id', $categoryId));
        }

        $movementType = trim((string) $request->input('movementType', ''));
        if ($movementType !== '' && in_array($movementType, ['IN', 'OUT', 'ADJUSTMENT'], true)) {
            $query->where('movement_type', $movementType);
        }

        $dateFrom = trim((string) $request->input('dateFrom', ''));
        if ($dateFrom !== '') {
            $query->whereDate('movement_date', '>=', $dateFrom);
        }

        $dateTo = trim((string) $request->input('dateTo', ''));
        if ($dateTo !== '') {
            $query->whereDate('movement_date', '<=', $dateTo);
        }

        $movements = $query->latest('id')->limit(500)->get();

        return response()->json([
            'message' => 'Inventory movements fetched successfully.',
            'movements' => $movements->map(fn(InventoryMovement $movement): array => $this->transformMovement($movement))->values(),
        ]);
    }

    public function itemMovements(InventoryItem $inventoryItem): JsonResponse
    {
        $movements = InventoryMovement::query()
            ->with(['item.category', 'vehicle', 'issuedToUser:id,name,email', 'createdBy:id,name,email'])
            ->where('inventory_item_id', $inventoryItem->id)
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Inventory item movements fetched successfully.',
            'movements' => $movements->map(fn(InventoryMovement $movement): array => $this->transformMovement($movement))->values(),
        ]);
    }

    private function createMovement(
        int $inventoryItemId,
        string $movementType,
        float $quantity,
        ?int $vehicleId,
        ?int $issuedToUserId,
        ?string $reference,
        ?string $notes,
        string $movementDate,
        int $userId,
    ): InventoryMovement {
        return DB::transaction(function () use (
            $inventoryItemId,
            $movementType,
            $quantity,
            $vehicleId,
            $issuedToUserId,
            $reference,
            $notes,
            $movementDate,
            $userId,
        ): InventoryMovement {
            if (! in_array($movementType, ['IN', 'OUT', 'ADJUSTMENT'], true)) {
                throw ValidationException::withMessages([
                    'movementType' => 'Unsupported movement type.',
                ]);
            }

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity must be greater than zero.',
                ]);
            }

            $item = InventoryItem::query()
                ->with('category')
                ->lockForUpdate()
                ->findOrFail($inventoryItemId);

            $before = (float) $item->current_stock;
            $after = $movementType === 'OUT'
                ? $before - $quantity
                : $before + $quantity;

            if ($movementType === 'OUT' && $after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Issued quantity cannot exceed available stock (' . number_format($before, 2) . ').',
                ]);
            }

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock cannot go below zero.',
                ]);
            }

            $item->current_stock = $after;
            $item->save();

            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'balance_before' => $before,
                'balance_after' => $after,
                'vehicle_id' => $vehicleId,
                'issued_to_user_id' => $issuedToUserId,
                'created_by' => $userId,
                'reference' => $reference !== null ? trim((string) $reference) : null,
                'notes' => $notes !== null ? trim((string) $notes) : null,
                'movement_date' => $movementDate,
            ]);

            $movement->load([
                'item.category',
                'vehicle',
                'issuedToUser:id,name,email',
                'createdBy:id,name,email',
            ]);

            $movement->setRelation('item', $item);

            return $movement;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCategory(InventoryCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'isActive' => (bool) $category->is_active,
            'itemsCount' => isset($category->items_count) ? (int) $category->items_count : null,
            'createdAt' => $category->created_at?->toISOString(),
            'updatedAt' => $category->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformItem(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'categoryId' => $item->inventory_category_id,
            'categoryName' => $item->category?->name,
            'name' => $item->name,
            'sku' => $item->sku,
            'unit' => $item->unit,
            'reorderLevel' => (float) $item->reorder_level,
            'currentStock' => (float) $item->current_stock,
            'isLowStock' => (float) $item->current_stock <= (float) $item->reorder_level,
            'description' => $item->description,
            'isActive' => (bool) $item->is_active,
            'createdAt' => $item->created_at?->toISOString(),
            'updatedAt' => $item->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformMovement(InventoryMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'itemId' => $movement->inventory_item_id,
            'itemName' => $movement->item?->name,
            'categoryId' => $movement->item?->inventory_category_id,
            'categoryName' => $movement->item?->category?->name,
            'movementType' => $movement->movement_type,
            'quantity' => (float) $movement->quantity,
            'balanceBefore' => (float) $movement->balance_before,
            'balanceAfter' => (float) $movement->balance_after,
            'vehicleId' => $movement->vehicle_id,
            'vehicleNo' => $movement->vehicle?->vehicle_no,
            'vehiclePlateNo' => $movement->vehicle?->plate_no,
            'issuedToUserId' => $movement->issued_to_user_id,
            'issuedToUserName' => $movement->issuedToUser?->name,
            'reference' => $movement->reference,
            'notes' => $movement->notes,
            'movementDate' => $movement->movement_date?->toISOString(),
            'createdByUserId' => $movement->created_by,
            'createdByUserName' => $movement->createdBy?->name,
            'createdAt' => $movement->created_at?->toISOString(),
            'updatedAt' => $movement->updated_at?->toISOString(),
        ];
    }
}
