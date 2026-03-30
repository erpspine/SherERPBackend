<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'proforma_invoice_id',
    'invoice_no',
    'quickbooks_ref',
    'client',
    'issue_date',
    'due_date',
    'total',
    'status',
    'notes',
])]
class Invoice extends Model
{
    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProformaInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\InvoicePayment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'total' => 'decimal:2',
        ];
    }
}
