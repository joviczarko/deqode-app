<?php

use App\Actions\CreateQode;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use App\Support\QodeUrlBuilder;
use App\Support\SqidsEncoder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('round-trips default slug encoding for the same id and config', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Round trip',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
    ]);

    expect($qode->slug)->toBe(app(SqidsEncoder::class)->encode($qode->id));
});

it('builds the local scan url with path prefix', function () {
    $domain = Domain::factory()->defaultPlatform()->create();
    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'URL check',
        'collection_id' => $collection->id,
    ]);

    $qode->load('domain');
    $url = app(QodeUrlBuilder::class)->forDomainAndSlug($qode->domain, (string) $qode->slug);

    expect($url)->toContain('deqode.test')
        ->and($url)->toContain('/r/')
        ->and($url)->toContain($qode->slug);
});

it('downloads a qr svg for the tenant owner', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'QR check',
        'collection_id' => $collection->id,
    ]);

    $this->actingAs($user)
        ->get(route('qodes.qr', $qode))
        ->assertSuccessful()
        ->assertHeader('content-type', 'image/svg+xml');
});
