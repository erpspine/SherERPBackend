<?php

namespace App\Models;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'inventory_category_id',
    'name',
    'sku',
    'unit',
    'reorder_level',
    'current_stock',
    'description',
    'is_active',
])]
class InventoryItem extends Model
{
    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reorder_level' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
