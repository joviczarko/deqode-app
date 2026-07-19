<?php

namespace App\Billing\Gateways;

use App\Billing\CheckoutRedirect;
use App\Billing\Contracts\PaymentGatewayInterface;
use App\Enums\CheckoutSessionStatus;
use App\Models\CheckoutSession;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;

class DemoGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'demo';
    }

    public function startCheckout(
        Tenant $tenant,
        Package $package,
        User $user,
        int $amountCents,
    ): CheckoutRedirect {
        $session = CheckoutSession::query()->create([
            'token' => CheckoutSession::generateToken(),
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'user_id' => $user->id,
            'status' => CheckoutSessionStatus::Pending,
            'amount_cents' => $amountCents,
            'currency' => 'USD',
            'gateway' => $this->name(),
            'expires_at' => now()->addHours(2),
        ]);

        return new CheckoutRedirect(
            token: $session->token,
            redirectUrl: route('billing.demo.checkout', ['token' => $session->token]),
        );
    }

    public function findPendingSession(string $token): ?CheckoutSession
    {
        $session = CheckoutSession::query()
            ->withoutGlobalScopes()
            ->where('token', $token)
            ->where('gateway', $this->name())
            ->first();

        if ($session === null || ! $session->isPending()) {
            return null;
        }

        return $session;
    }
}
