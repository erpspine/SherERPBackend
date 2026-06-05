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
        'fuel_log_id',
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
        'closed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'odometer_reading' => 'integer',
        'liters' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'recorded_at' => 'datetime',
        'closed_at' => 'datetime',
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

    /**
     * The Fuel log that opened the tank cycle this reading belongs to.
     * Null on Fuel logs (a Fuel log opens its own cycle) and on orphan
     * readings captured before the first ever fuel-up on the trip.
     */
    public function fuelLog(): BelongsTo
    {
        return $this->belongsTo(self::class, 'fuel_log_id');
    }
}
