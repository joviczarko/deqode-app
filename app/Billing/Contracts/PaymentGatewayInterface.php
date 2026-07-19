<?php

namespace App\Billing\Contracts;

use App\Billing\CheckoutRedirect;
use App\Models\CheckoutSession;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;

interface PaymentGatewayInterface
{
    public function name(): string;

    public function startCheckout(
        Tenant $tenant,
        Package $package,
        User $user,
        int $amountCents,
    ): CheckoutRedirect;

    public function findPendingSession(string $token): ?CheckoutSession;
}
