<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'is_free' => false,
            'is_active' => true,
            'trial_days' => 14,
            'price_monthly_cents' => 0,
            'quotas' => ['max_qodes' => 10],
            'features' => [],
        ];
    }

    public function freeTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'free',
            'name' => 'Free / Trial',
            'is_free' => true,
            'trial_days' => 14,
            'price_monthly_cents' => 0,
        ]);
    }
}
