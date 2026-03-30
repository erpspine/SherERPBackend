<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'park_id',
    'type',
    'category',
    'rate',
])]
class ParkRate extends Model
{
    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'park_id' => 'integer',
            'rate'    => 'decimal:2',
        ];
    }
}
