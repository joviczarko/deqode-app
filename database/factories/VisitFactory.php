<?php

namespace Database\Factories;

use App\Models\Qode;
use App\Models\Tenant;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'qode_id' => Qode::factory(),
            'visited_at' => now(),
            'referrer' => fake()->optional()->url(),
            'user_agent' => fake()->userAgent(),
            'device' => fake()->randomElement(['desktop', 'mobile', 'tablet', 'bot', 'unknown']),
        ];
    }
}
