<?php

namespace App\Providers;

use App\Billing\Contracts\PaymentGatewayInterface;
use App\Billing\Gateways\DemoGateway;
use App\Models\Category;
use App\Models\Collection;
use App\Models\File;
use App\Models\Lead;
use App\Models\Qode;
use App\Models\Tenant;
use App\Policies\CategoryPolicy;
use App\Policies\CollectionPolicy;
use App\Policies\FilePolicy;
use App\Policies\LeadPolicy;
use App\Policies\QodePolicy;
use App\Policies\TenantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use RuntimeException;

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
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(File::class, FilePolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Qode::class, QodePolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);

        $this->ensureS3Configuration();
    }

    /**
     * Media uploads expect a real S3 disk. Empty AWS_* with FILESYSTEM_DISK=s3 fails at boot.
     */
    private function ensureS3Configuration(): void
    {
        if ($this->app->environment('testing')) {
            return;
        }

        if (config('filesystems.default') !== 's3') {
            return;
        }

        foreach (['key', 'secret', 'region', 'bucket'] as $required) {
            if (blank(config("filesystems.disks.s3.{$required}"))) {
                throw new RuntimeException(
                    'FILESYSTEM_DISK=s3 but AWS credentials are incomplete. Set AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, and AWS_BUCKET.'
                );
            }
        }
    }
}
