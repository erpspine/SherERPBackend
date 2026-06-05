<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdometerLog extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'safari_allocation_id',
        'user_id',
        'client_id',
        'entry_type',
        'location',
        'odometer_reading',
        'liters',
        'unit_price',
        'station',
        'notes',
        'photo_path',
        'recorded_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'odometer_reading' => 'integer',
        'liters' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function safariAllocation(): BelongsTo
    {
        return $this->belongsTo(SafariAllocation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
