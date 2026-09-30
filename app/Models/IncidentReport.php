<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'case_reference',
    'safari_allocation_id',
    'performance_data',
    'performance_revision',
    'incident_date',
    'vehicle_id',
    'driver_user_id',
    'driver_name',
    'driver_email',
    'driver_phone',
    'lead_id',
    'lease_allocation_id',
    'report_type',
    'description',
    'action_taken',
    'photos',
    'status',
    'closing_remarks',
])]
class IncidentReport extends Model
{
    public function attachments(): HasMany
    {
        return $this->hasMany(IncidentAttachment::class)->latest('id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function leaseAllocation(): BelongsTo
    {
        return $this->belongsTo(LeaseAllocation::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'photos' => 'array',
            'performance_data' => 'array',
            'performance_revision' => 'integer',
        ];
    }
}
