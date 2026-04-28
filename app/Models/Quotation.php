<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quotation_number',
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
    'sent_by_id',
    'sent_at',
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

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sent_by_id');
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
            'sent_at'      => 'datetime',
        ];
    }
}
