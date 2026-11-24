<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerDiemPaymentLine extends Model
{
    protected $fillable = [
        'per_diem_voucher_id', 'stage', 'amount', 'requested_amount', 'fuel_deduction', 'deduction_reason',
        'status', 'payment_reference', 'payment_batch_reference', 'approved_by',
        'approved_at', 'approval_reason', 'exported_at', 'payment_proof_path', 'paid_by', 'paid_at',
    ];

    public function voucher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PerDiemVoucher::class, 'per_diem_voucher_id');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requested_amount' => 'decimal:2',
            'fuel_deduction' => 'decimal:2',
            'approved_at' => 'datetime',
            'exported_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
