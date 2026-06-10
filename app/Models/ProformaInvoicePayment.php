<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'proforma_invoice_id',
    'date',
    'amount',
    'method',
    'reference',
    'notes',
])]
class ProformaInvoicePayment extends Model
{
    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProformaInvoice::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
