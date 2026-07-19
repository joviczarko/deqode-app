<?php

namespace Database\Factories;

use App\Models\PaymentLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentLog>
 */
class PaymentLogFactory extends Factory
{
    protected $model = PaymentLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'payment_id' => null,
            'invoice_id' => null,
            'gateway' => 'demo',
            'event' => 'payment.succeeded',
            'payload' => [],
        ];
    }
}
