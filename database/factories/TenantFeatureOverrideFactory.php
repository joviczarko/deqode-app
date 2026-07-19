<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantFeatureOverride;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantFeatureOverride>
 */
class TenantFeatureOverrideFactory extends Factory
{
    protected $model = TenantFeatureOverride::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'package_id' => null,
            'price_monthly_cents' => null,
            'quota_overrides' => null,
            'feature_overrides' => null,
            'notes' => null,
        ];
    }
}
