<?php

use App\Actions\CreateQode;
use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Tenant;
use App\Support\SqidsEncoder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves an active qode by domain and slug', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'General',
        'is_default' => true,
    ]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Bottle label',
        'collection_id' => $collection->id,
        'type' => QodeType::Redirect->value,
    ]);

    expect($qode->slug)->toBe(app(SqidsEncoder::class)->encode($qode->id));

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('Redirect Qode stub', false);
});

it('returns 404 for unknown slug', function () {
    Domain::factory()->defaultPlatform()->create();

    $this->get('/r/doesnotexistslug')->assertNotFound();
});

it('returns 404 for inactive qode', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Inactive one',
        'collection_id' => $collection->id,
        'status' => QodeStatus::Inactive->value,
    ]);

    $this->get('/r/'.$qode->slug)->assertNotFound();
});

it('renders content qodes through the pico wrapper stack', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Product story',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'title' => 'About this bottle',
            'body' => '<p>Ingredients and story.</p>',
        ],
    ]);

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/html; charset=UTF-8')
        ->assertSee('data-deqode-wrapper="1"', false)
        ->assertSee('data-deqode-template="default"', false)
        ->assertSee('data-deqode-module="content"', false)
        ->assertSee('@picocss/pico', false)
        ->assertSee('About this bottle', false)
        ->assertSee('Ingredients and story.', false);
});

it('keeps redirect qodes bare without the html layout', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Campaign redirect',
        'collection_id' => $collection->id,
        'type' => QodeType::Redirect->value,
    ]);

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/plain; charset=UTF-8')
        ->assertSee('Redirect Qode stub', false)
        ->assertDontSee('data-deqode-wrapper', false)
        ->assertDontSee('@picocss/pico', false);
});
