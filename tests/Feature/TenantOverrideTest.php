<?php

use App\Actions\UpdateTenantOverrides;
use App\Billing\EffectiveEntitlements;
use App\Enums\SubscriptionStatus;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies quota and price overrides to effective limits', function () {
    $free = Package::factory()->freeTrial()->create();
    $starter = Package::factory()->starter()->create();
    $tenant = Tenant::factory()->create();

    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $starter->id,
        'status' => SubscriptionStatus::Active,
    ]);

    app(UpdateTenantOverrides::class)->handle($tenant, [
        'package_id' => null,
        'price_monthly_cents' => 1500,
        'quota_overrides' => [
            'max_qodes' => 999,
        ],
        'feature_overrides' => [
            'custom_domains' => true,
        ],
        'notes' => 'VIP account',
    ]);

    $tenant->refresh()->load(['featureOverride', 'currentSubscription.package']);

    $effective = app(EffectiveEntitlements::class)->for($tenant);

    expect($effective['package']->id)->toBe($starter->id)
        ->and($effective['price_monthly_cents'])->toBe(1500)
        ->and($effective['quotas']['max_qodes'])->toBe(999)
        ->and($effective['features']['custom_domains'])->toBeTrue()
        ->and($effective['features']['custom_slugs'])->toBeFalse();

    expect(app(EffectiveEntitlements::class)->quota($tenant, 'max_qodes'))->toBe(999);
});

it('can force a different package via override', function () {
    $free = Package::factory()->freeTrial()->create();
    $starter = Package::factory()->starter()->create();
    $tenant = Tenant::factory()->create();

    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $free->id,
        'status' => SubscriptionStatus::Trial,
    ]);

    app(UpdateTenantOverrides::class)->handle($tenant, [
        'package_id' => $starter->id,
        'price_monthly_cents' => null,
        'quota_overrides' => null,
        'feature_overrides' => null,
    ]);

    $tenant->refresh()->load(['featureOverride', 'currentSubscription.package']);
    $effective = app(EffectiveEntitlements::class)->for($tenant);

    expect($effective['package']->slug)->toBe('starter')
        ->and($effective['quotas']['max_qodes'])->toBe(50)
        ->and($effective['price_monthly_cents'])->toBe(1900);
});
