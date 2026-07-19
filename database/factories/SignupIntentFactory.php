<?php

namespace Database\Factories;

use App\Enums\SignupIntentStatus;
use App\Models\SignupIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SignupIntent>
 */
class SignupIntentFactory extends Factory
{
    protected $model = SignupIntent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => SignupIntent::generateToken(),
            'email' => fake()->unique()->safeEmail(),
            'status' => SignupIntentStatus::Pending,
            'attempt_count' => 1,
            'ip_address' => fake()->ipv4(),
            'expires_at' => now()->addHours(48),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SignupIntentStatus::EmailVerified,
            'email_verified_at' => now(),
        ]);
    }
}
