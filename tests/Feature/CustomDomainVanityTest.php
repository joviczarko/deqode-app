<?php

use App\Actions\ClaimCustomDomain;
use App\Actions\CreateQode;
use App\Actions\RecordVisit;
use App\Actions\VerifyCustomDomain;
use App\Enums\DomainStatus;
use App\Enums\QodeType;
use App\Http\Controllers\QodeResolveController;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\Tenant;
use App\QodeModules\ModuleRegistry;
use App\QodeModules\RedirectDestination;
use App\Support\DnsTxtLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

function growthTenant(): array
{
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $package = Package::factory()->create([
        'quotas' => ['max_qodes' => 250, 'max_scans' => 100000],
        'features' => [
            'custom_domains' => true,
            'custom_slugs' => true,
            'platform_domain_choice' => true,
        ],
    ]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $package->id,
    ]);
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    return [$tenant, $collection];
}

it('does not serve qodes on an unverified custom host', function () {
    [$tenant, $collection] = growthTenant();

    $domain = Domain::factory()->customPending()->create([
        'hostname' => 'pending.brand.test',
        'tenant_id' => $tenant->id,
    ]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Hidden',
        'collection_id' => $collection->id,
        'domain_id' => Domain::defaultPlatform()->id,
        'type' => QodeType::Content->value,
        'settings' => ['body' => '<p>Hi</p>'],
    ]);

    // Point the qode at the pending custom domain after create (create requires verified).
    $qode->forceFill(['domain_id' => $domain->id])->save();

    $request = Request::create('http://pending.brand.test/'.$qode->slug, 'GET', server: [
        'HTTP_HOST' => 'pending.brand.test',
    ]);

    expect(fn () => app(QodeResolveController::class)(
        $request,
        $qode->slug,
        app(ModuleRegistry::class),
        app(RecordVisit::class),
        app(RedirectDestination::class),
    ))->toThrow(NotFoundHttpException::class);
});

it('verifies a custom domain via txt and then serves on that host', function () {
    [$tenant, $collection] = growthTenant();

    $domain = app(ClaimCustomDomain::class)->handle($tenant, 'qr.brand.test');

    expect($domain->status)->toBe(DomainStatus::Pending)
        ->and($domain->verification_token)->not->toBeEmpty();

    $this->mock(DnsTxtLookup::class, function ($mock) use ($domain): void {
        $mock->shouldReceive('texts')
            ->with('qr.brand.test')
            ->andReturn(['deqode-verify='.$domain->verification_token]);
    });

    $verified = app(VerifyCustomDomain::class)->handle($tenant, $domain->fresh());

    expect($verified->status)->toBe(DomainStatus::Verified);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Brand page',
        'collection_id' => $collection->id,
        'domain_id' => $verified->id,
        'type' => QodeType::Content->value,
        'settings' => ['body' => '<p>On custom host</p>'],
    ]);

    $request = Request::create('http://qr.brand.test/'.$qode->slug, 'GET', server: [
        'HTTP_HOST' => 'qr.brand.test',
    ]);

    $response = app(QodeResolveController::class)(
        $request,
        $qode->slug,
        app(ModuleRegistry::class),
        app(RecordVisit::class),
        app(RedirectDestination::class),
    );

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toContain('On custom host');
});

it('rejects vanity slugs when the package feature is off', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $package = Package::factory()->freeTrial()->create();
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $package->id,
    ]);
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    expect(fn () => app(CreateQode::class)->handle($tenant, [
        'name' => 'No vanity',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'slug' => 'mycustom',
        'settings' => ['body' => '<p>x</p>'],
    ]))->toThrow(ValidationException::class);
});

it('allows vanity slugs when the package feature is on', function () {
    [$tenant, $collection] = growthTenant();

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Vanity',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'slug' => 'summerpromo',
        'settings' => ['body' => '<p>Promo</p>'],
    ]);

    expect($qode->slug)->toBe('summerpromo');

    $this->get('/r/summerpromo')
        ->assertSuccessful()
        ->assertSee('Promo', false);
});

it('prevents a second tenant from claiming the same hostname', function () {
    [$tenant] = growthTenant();
    $other = Tenant::factory()->create();
    $package = Package::factory()->create([
        'features' => [
            'custom_domains' => true,
            'custom_slugs' => true,
            'platform_domain_choice' => true,
        ],
    ]);
    Subscription::factory()->create([
        'tenant_id' => $other->id,
        'package_id' => $package->id,
    ]);

    app(ClaimCustomDomain::class)->handle($tenant, 'shared.brand.test');

    expect(fn () => app(ClaimCustomDomain::class)->handle($other, 'shared.brand.test'))
        ->toThrow(ValidationException::class);
});
