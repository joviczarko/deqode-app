<?php

namespace Database\Factories;

use App\Enums\CheckoutSessionStatus;
use App\Models\CheckoutSession;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckoutSession>
 */
class CheckoutSessionFactory extends Factory
{
    protected $model = CheckoutSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => CheckoutSession::generateToken(),
            'tenant_id' => Tenant::factory(),
            'package_id' => Package::factory(),
            'user_id' => User::factory(),
            'status' => CheckoutSessionStatus::Pending,
            'amount_cents' => 1900,
            'currency' => 'USD',
            'gateway' => 'demo',
            'expires_at' => now()->addHours(2),
        ];
    }
}
