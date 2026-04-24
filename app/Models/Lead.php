<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lead_api_key_id',
    'booking_ref',
    'client_company',
    'agent_contact',
    'agent_email',
    'agent_phone',
    'client_country',
    'start_date',
    'end_date',
    'route_parks',
    'pax_adults',
    'pax_children',
    'no_of_vehicles',
    'special_requirements',
    'booking_status',
    'source',
    'quotation_sent_by',
    'quotation_sent_at',
    'pi_sent_by',
    'pi_sent_at',
])]
class Lead extends Model
{
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class);
    }

    public function fuelRequisitions(): HasMany
    {
        return $this->hasMany(FuelRequisition::class);
    }

    public function quotationSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quotation_sent_by');
    }

    public function piSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pi_sent_by');
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(LeadApiKey::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'pax_adults' => 'integer',
            'pax_children' => 'integer',
            'no_of_vehicles' => 'integer',
            'quotation_sent_by' => 'integer',
            'quotation_sent_at' => 'datetime',
            'pi_sent_by' => 'integer',
            'pi_sent_at' => 'datetime',
        ];
    }
}
