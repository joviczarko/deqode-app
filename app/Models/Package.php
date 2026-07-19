<?php

namespace App\Models;

use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'is_free',
    'is_active',
    'trial_days',
    'price_monthly_cents',
    'quotas',
    'features',
])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_free' => false,
        'is_active' => true,
        'trial_days' => 0,
        'price_monthly_cents' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'trial_days' => 'integer',
            'price_monthly_cents' => 'integer',
            'quotas' => 'array',
            'features' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public static function freeTrial(): ?self
    {
        return static::query()
            ->where('slug', 'free')
            ->where('is_active', true)
            ->first();
    }
}
