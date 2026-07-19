<?php

namespace App\Actions;

use App\Billing\CheckoutRedirect;
use App\Billing\Contracts\PaymentGatewayInterface;
use App\Billing\EffectiveEntitlements;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StartCheckout
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private EffectiveEntitlements $entitlements,
    ) {}

    public function handle(Tenant $tenant, Package $package, User $user): CheckoutRedirect
    {
        if (! $package->isPurchasable()) {
            throw ValidationException::withMessages([
                'package' => 'This package cannot be purchased.',
            ]);
        }

        if ($user->tenant_id !== $tenant->id) {
            throw ValidationException::withMessages([
                'tenant' => 'You cannot checkout for another tenant.',
            ]);
        }

        $override = $tenant->featureOverride;
        $amountCents = $this->entitlements->priceMonthlyCents($package, $override);

        return $this->gateway->startCheckout($tenant, $package, $user, $amountCents);
    }
}
