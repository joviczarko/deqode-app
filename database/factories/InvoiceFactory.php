<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => null,
            'package_id' => Package::factory(),
            'number' => 'INV-'.fake()->unique()->numerify('####-####'),
            'status' => InvoiceStatus::Paid,
            'amount_cents' => 1900,
            'currency' => 'USD',
            'gateway' => 'demo',
            'paid_at' => now(),
        ];
    }
}
