<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SafariFuelEntry extends Model
{
    protected $guarded = ['id'];

    public function safariAllocation()
    {
        return $this->belongsTo(SafariAllocation::class);
    }
    public function leaseAllocation()
    {
        return $this->belongsTo(LeaseAllocation::class);
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
