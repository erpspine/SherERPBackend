<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quotation_id',
    'day_title',
    'day_description',
    'item',
    'description',
    'unit',
    'qty',
    'rate',
    'total',
])]
class QuotationLineItem extends Model
{
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Quotation::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty'   => 'decimal:2',
            'rate'  => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }
}
