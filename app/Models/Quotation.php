<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lead_id',
    'client',
    'attention',
    'quote_date',
    'notes',
    'day_sections',
    'subtotal',
    'tax',
    'total',
    'status',
])]
class Quotation extends Model
{
    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Lead::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(\App\Models\QuotationLineItem::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quote_date'   => 'date',
            'day_sections' => 'array',
            'subtotal'     => 'decimal:2',
            'tax'          => 'decimal:2',
            'total'        => 'decimal:2',
        ];
    }
}
