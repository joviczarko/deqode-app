<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => Invoice::factory(),
            'status' => PaymentStatus::Paid,
            'amount_cents' => 1900,
            'currency' => 'USD',
            'gateway' => 'demo',
            'gateway_reference' => 'demo-'.fake()->unique()->uuid(),
            'paid_at' => now(),
        ];
    }
}
