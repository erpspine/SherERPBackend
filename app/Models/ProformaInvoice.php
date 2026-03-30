<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quotation_id',
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
class ProformaInvoice extends Model
{
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Quotation::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Lead::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(\App\Models\ProformaInvoiceLineItem::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'day_sections' => 'array',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }
}
