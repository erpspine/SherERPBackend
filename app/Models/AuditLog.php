<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'method',
        'path',
        'route_name',
        'ip_address',
        'user_agent',
        'status_code',
        'route_parameters',
        'request_data',
    ];

    protected $casts = [
        'route_parameters' => 'array',
        'request_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
