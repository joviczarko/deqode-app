<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Qode;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'qode_id' => Qode::factory(),
            'payload' => [
                'email' => fake()->safeEmail(),
                'message' => fake()->sentence(),
            ],
        ];
    }
}
