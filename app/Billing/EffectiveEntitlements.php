<?php

namespace App\Billing;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantFeatureOverride;

class EffectiveEntitlements
{
    /**
     * @return array{package: Package, price_monthly_cents: int, quotas: array<string, mixed>, features: array<string, mixed>}
     */
    public function for(Tenant $tenant): array
    {
        $subscription = $tenant->currentSubscription()->with('package')->first();
        $override = $tenant->featureOverride;

        $package = $override?->package ?? $subscription?->package;

        if ($package === null) {
            $package = Package::freeTrial() ?? Package::query()->checkout()->firstOrFail();
        }

        return [
            'package' => $package,
            'price_monthly_cents' => $this->priceMonthlyCents($package, $override),
            'quotas' => $this->quotas($package, $override),
            'features' => $this->features($package, $override),
        ];
    }

    public function quota(Tenant $tenant, string $key, mixed $default = null): mixed
    {
        return $this->for($tenant)['quotas'][$key] ?? $default;
    }

    public function priceMonthlyCents(Package $package, ?TenantFeatureOverride $override = null): int
    {
        if ($override?->price_monthly_cents !== null) {
            return (int) $override->price_monthly_cents;
        }

        return (int) $package->price_monthly_cents;
    }

    /**
     * @return array<string, mixed>
     */
    public function quotas(Package $package, ?TenantFeatureOverride $override = null): array
    {
        $base = PackageCatalog::normalizeQuotas($package->quotas);
        $overrides = PackageCatalog::filterQuotaOverrides($override?->quota_overrides) ?? [];

        return array_replace($base, $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    public function features(Package $package, ?TenantFeatureOverride $override = null): array
    {
        $base = PackageCatalog::normalizeFeatures($package->features);
        $overrides = PackageCatalog::filterFeatureOverrides($override?->feature_overrides) ?? [];

        return array_replace($base, $overrides);
    }
}
