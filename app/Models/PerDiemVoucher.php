<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerDiemVoucher extends Model
{
    protected $fillable = [
        'job_card_id',
        'driver_key',
        'snapshot',
        'requested_for_approval_at',
        'payment_status',
        'payment_reference',
        'payment_batch_reference',
        'approved_by',
        'approved_at',
        'exported_at',
        'paid_at',
        'confirmed_at',
    ];

    public function jobCard(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function paymentLines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PerDiemPaymentLine::class);
    }

    public function number(): string
    {
        return 'PD-'.$this->created_at->format('Y').'-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'requested_for_approval_at' => 'datetime',
            'approved_at' => 'datetime',
            'exported_at' => 'datetime',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }
}
