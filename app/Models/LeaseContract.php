<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_name',
    'lease_type',
    'start_date',
    'end_date',
    'duration_days',
    'monthly_rate',
    'notes',
    'status',
])]
class LeaseContract extends Model
{
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'lease_contract_vehicle');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(LeaseAllocation::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_days' => 'integer',
            'monthly_rate' => 'decimal:2',
        ];
    }
}
