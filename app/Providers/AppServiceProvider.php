<?php

namespace App\Providers;

use App\Billing\Contracts\PaymentGatewayInterface;
use App\Billing\Gateways\DemoGateway;
use App\Models\Collection;
use App\Models\Qode;
use App\Models\Tenant;
use App\Policies\CollectionPolicy;
use App\Policies\QodePolicy;
use App\Policies\TenantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function (): PaymentGatewayInterface {
            return match (config('billing.provider')) {
                'demo' => new DemoGateway,
                default => throw new InvalidArgumentException('Unsupported billing provider: '.config('billing.provider')),
            };
        });
    }

    public function boot(): void
    {
        Gate::policy(Collection::class, CollectionPolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Qode::class, QodePolicy::class);
    }
}
