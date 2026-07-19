<?php

namespace App\Models;

use App\Billing\PackageCatalog;
use App\Enums\PackageStatus;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'status',
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
        'status' => 'active',
        'is_free' => false,
        'is_active' => true,
        'trial_days' => 0,
        'price_monthly_cents' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => PackageStatus::class,
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'trial_days' => 'integer',
            'price_monthly_cents' => 'integer',
            'quotas' => 'array',
            'features' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Package $package): void {
            $package->quotas = PackageCatalog::normalizeQuotas($package->quotas);
            $package->features = PackageCatalog::normalizeFeatures($package->features);
            $package->is_active = $package->status !== PackageStatus::Hidden;
            $package->is_free = $package->status === PackageStatus::Trial || (int) $package->price_monthly_cents === 0;
        });
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopePurchasable(Builder $query): Builder
    {
        return $query
            ->where('is_free', false)
            ->whereIn('status', [PackageStatus::Active, PackageStatus::UpgradeOnly]);
    }

    public function scopeCheckout(Builder $query): Builder
    {
        return $query
            ->where('is_free', false)
            ->where('status', PackageStatus::Active);
    }

    public function isPurchasable(): bool
    {
        return ! $this->is_free && $this->status->isPurchasable();
    }

    public static function freeTrial(): ?self
    {
        return static::query()
            ->where('status', PackageStatus::Trial)
            ->orderBy('id')
            ->first()
            ?? static::query()->where('slug', 'free')->first();
    }
}
