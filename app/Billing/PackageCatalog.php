<?php

namespace App\Billing;

use App\Enums\PackageStatus;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PackageCatalog
{
    /**
     * @return array<string, array{label: string, type: string, default: mixed}>
     */
    public static function quotaDefinitions(): array
    {
        return config('packages.quotas', []);
    }

    /**
     * @return array<string, array{label: string, default: bool}>
     */
    public static function featureDefinitions(): array
    {
        return config('packages.features', []);
    }

    /**
     * @return list<string>
     */
    public static function quotaKeys(): array
    {
        return array_keys(self::quotaDefinitions());
    }

    /**
     * @return list<string>
     */
    public static function featureKeys(): array
    {
        return array_keys(self::featureDefinitions());
    }

    /**
     * @param  array<string, mixed>|null  $quotas
     * @return array<string, int|null>
     */
    public static function normalizeQuotas(?array $quotas): array
    {
        $normalized = [];

        foreach (self::quotaDefinitions() as $key => $definition) {
            if ($quotas === null || ! array_key_exists($key, $quotas) || $quotas[$key] === null || $quotas[$key] === '') {
                $normalized[$key] = $definition['default'] ?? null;

                continue;
            }

            $normalized[$key] = is_numeric($quotas[$key]) ? (int) $quotas[$key] : $quotas[$key];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $features
     * @return array<string, bool>
     */
    public static function normalizeFeatures(?array $features): array
    {
        $normalized = [];

        foreach (self::featureDefinitions() as $key => $definition) {
            if ($features === null || ! array_key_exists($key, $features) || $features[$key] === null || $features[$key] === '') {
                $normalized[$key] = (bool) ($definition['default'] ?? false);

                continue;
            }

            $normalized[$key] = filter_var($features[$key], FILTER_VALIDATE_BOOLEAN);
        }

        return $normalized;
    }

    /**
     * Keep only known keys; drop empty / inherit values from override payloads.
     *
     * @param  array<string, mixed>|null  $quotas
     * @return array<string, int>|null
     */
    public static function filterQuotaOverrides(?array $quotas): ?array
    {
        if ($quotas === null) {
            return null;
        }

        $filtered = [];

        foreach (self::quotaKeys() as $key) {
            if (! array_key_exists($key, $quotas) || $quotas[$key] === null || $quotas[$key] === '') {
                continue;
            }

            $filtered[$key] = (int) $quotas[$key];
        }

        return $filtered === [] ? null : $filtered;
    }

    /**
     * @param  array<string, mixed>|null  $features
     * @return array<string, bool>|null
     */
    public static function filterFeatureOverrides(?array $features): ?array
    {
        if ($features === null) {
            return null;
        }

        $filtered = [];

        foreach (self::featureKeys() as $key) {
            if (! array_key_exists($key, $features) || $features[$key] === null || $features[$key] === '') {
                continue;
            }

            $filtered[$key] = filter_var($features[$key], FILTER_VALIDATE_BOOLEAN);
        }

        return $filtered === [] ? null : $filtered;
    }

    /**
     * @return Collection<int, Package>
     */
    public static function forCheckout(): Collection
    {
        return Package::query()
            ->where('is_free', false)
            ->where('status', PackageStatus::Active)
            ->orderBy('price_monthly_cents')
            ->get();
    }

    /**
     * @param  Builder<Package>  $query
     * @return Builder<Package>
     */
    public static function scopeAssignable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PackageStatus::Trial,
            PackageStatus::Active,
            PackageStatus::Legacy,
            PackageStatus::UpgradeOnly,
        ]);
    }
}
