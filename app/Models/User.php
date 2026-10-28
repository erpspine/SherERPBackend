<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'tin', 'nida', 'bank_name', 'bank_account_number', 'languages_spoken', 'work_experience', 'driving_started_at', 'driver_license', 'tour_guide_license', 'contract_type', 'contract_start_date', 'contract_end_date', 'contract_expiry_notified_at', 'role', 'status', 'blacklist_reason', 'receive_notifications', 'last_login_at', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public function assignedVehicles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vehicle::class, 'assigned_driver_id');
    }

    protected string $guard_name = 'web';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'receive_notifications' => 'boolean',
            'last_login_at' => 'datetime',
            'driving_started_at' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'contract_expiry_notified_at' => 'datetime',
        ];
    }

    protected function getDefaultGuardName(): string
    {
        return $this->guard_name;
    }
}
