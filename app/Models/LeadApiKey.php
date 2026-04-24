<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LeadApiKey extends Model
{
    protected $fillable = [
        'name',
        'key',
        'website_url',
        'active',
        'last_used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Generate a new API key.
     */
    public static function generateKey(): string
    {
        return 'lead_' . Str::random(40);
    }

    /**
     * Find by plain key and check if active.
     */
    public static function findByKey(string $plainKey): ?self
    {
        $key = static::where('key', hash('sha256', $plainKey))
            ->where('active', true)
            ->first();

        if ($key) {
            $key->update(['last_used_at' => now()]);
        }

        return $key;
    }
}
