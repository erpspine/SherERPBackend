<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'region',
    'status',
])]
class Park extends Model
{
    public function rates(): HasMany
    {
        return $this->hasMany(ParkRate::class);
    }

    public function concessionRates(): HasMany
    {
        return $this->hasMany(ConcessionRate::class);
    }
}
