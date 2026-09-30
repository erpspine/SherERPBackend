<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerDiemVoucher extends Model
{
    protected $fillable = ['job_card_id', 'driver_key', 'snapshot'];

    public function jobCard(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function number(): string
    {
        return 'PD-'.$this->created_at->format('Y').'-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
