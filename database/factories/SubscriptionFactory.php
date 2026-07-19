<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'package_id' => Package::factory(),
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays(14),
            'starts_at' => now(),
        ];
    }
}
