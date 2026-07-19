<?php

namespace App\Actions;

use App\Billing\PackageCatalog;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantFeatureOverride;

class UpdateTenantOverrides
{
    /**
     * @param  array{
     *     package_id?: int|null,
     *     price_monthly_cents?: int|null,
     *     quota_overrides?: array<string, mixed>|null,
     *     feature_overrides?: array<string, mixed>|null,
     *     notes?: string|null
     * }  $data
     */
    public function handle(Tenant $tenant, array $data): TenantFeatureOverride
    {
        if (isset($data['package_id']) && $data['package_id'] !== null) {
            PackageCatalog::scopeAssignable(
                Package::query()->whereKey($data['package_id'])
            )->firstOrFail();
        }

        return TenantFeatureOverride::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'package_id' => $data['package_id'] ?? null,
                'price_monthly_cents' => $data['price_monthly_cents'] ?? null,
                'quota_overrides' => PackageCatalog::filterQuotaOverrides($data['quota_overrides'] ?? null),
                'feature_overrides' => PackageCatalog::filterFeatureOverrides($data['feature_overrides'] ?? null),
                'notes' => $data['notes'] ?? null,
            ],
        );
    }
}
