<?php

namespace App\Providers;

use App\Models\Collection;
use App\Models\Tenant;
use App\Policies\CollectionPolicy;
use App\Policies\TenantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Collection::class, CollectionPolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);
    }
}
