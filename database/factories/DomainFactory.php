<?php

namespace Database\Factories;

use App\Enums\DomainStatus;
use App\Enums\DomainType;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Domain>
 */
class DomainFactory extends Factory
{
    protected $model = Domain::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hostname' => fake()->unique()->domainName(),
            'type' => DomainType::Platform,
            'tenant_id' => null,
            'status' => DomainStatus::Active,
            'is_default' => false,
            'verification_token' => null,
            'verified_at' => null,
        ];
    }

    public function defaultPlatform(): static
    {
        return $this->state(fn (array $attributes) => [
            'hostname' => config('deqode.platform_domain', 'deqode.test'),
            'type' => DomainType::Platform,
            'is_default' => true,
            'status' => DomainStatus::Active,
        ]);
    }

    public function customPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DomainType::Custom,
            'status' => DomainStatus::Pending,
            'is_default' => false,
            'verification_token' => 'testtoken123',
            'verified_at' => null,
        ]);
    }
}
