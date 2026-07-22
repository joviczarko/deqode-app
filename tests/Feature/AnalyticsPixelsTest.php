<?php

use App\Actions\CreateQode;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Tenant;
use App\QodeModules\RedirectDestination;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('injects ga4 and meta pixel tags on html qode pages', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create([
        'analytics_settings' => [
            'ga4_measurement_id' => 'G-TEST123',
            'meta_pixel_id' => '999888777',
        ],
    ]);
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Tracked page',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => ['body' => '<p>Hello</p>'],
    ]);

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('data-deqode-ga4="G-TEST123"', false)
        ->assertSee('data-deqode-meta-pixel="999888777"', false);
});

it('does not inject pixels on bare redirects', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create([
        'analytics_settings' => [
            'ga4_measurement_id' => 'G-TEST123',
            'meta_pixel_id' => '999888777',
        ],
    ]);
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Redirecting',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'body' => '<p>Hidden</p>',
            'redirect' => [
                'to' => RedirectDestination::MODE_URL,
                'url' => 'https://example.com/out',
                'target_qode_id' => null,
            ],
        ],
    ]);

    $this->get('/r/'.$qode->slug)
        ->assertRedirect('https://example.com/out')
        ->assertDontSee('data-deqode-ga4', false)
        ->assertDontSee('G-TEST123', false);
});
