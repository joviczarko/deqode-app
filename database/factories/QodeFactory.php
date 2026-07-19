<?php

namespace Database\Factories;

use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Qode;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Qode>
 */
class QodeFactory extends Factory
{
    protected $model = Qode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'collection_id' => fn (array $attributes) => Collection::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'domain_id' => Domain::factory(),
            'name' => fake()->words(3, true),
            'type' => QodeType::Redirect,
            'status' => QodeStatus::Active,
            'settings' => [
                'url' => 'https://example.com',
                'status_code' => 302,
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QodeStatus::Inactive,
        ]);
    }
}
