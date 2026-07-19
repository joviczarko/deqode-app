<?php

use App\Actions\CreateQode;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Tenant;
use App\QodeModules\RedirectDestination;
use App\Support\QodeUrlBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('redirects to another non-redirect qode public url', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $content = app(CreateQode::class)->handle($tenant, [
        'name' => 'Landing',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'title' => 'Hello',
            'body' => '<p>Hi</p>',
        ],
    ]);

    $redirect = app(CreateQode::class)->handle($tenant, [
        'name' => 'Printed label',
        'collection_id' => $collection->id,
        'type' => QodeType::Redirect->value,
        'settings' => [
            'destination' => RedirectDestination::MODE_QODE,
            'target_qode_id' => $content->id,
        ],
    ]);

    $targetUrl = app(QodeUrlBuilder::class)->forQode($content->fresh());

    $this->get('/r/'.$redirect->slug)
        ->assertRedirect($targetUrl)
        ->assertStatus(302);
});

it('rejects redirect targets that are themselves redirects', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $outer = app(CreateQode::class)->handle($tenant, [
        'name' => 'Outer',
        'collection_id' => $collection->id,
        'type' => QodeType::Redirect->value,
        'settings' => [
            'destination' => RedirectDestination::MODE_URL,
            'url' => 'https://example.com',
        ],
    ]);

    $inner = app(CreateQode::class)->handle($tenant, [
        'name' => 'Inner',
        'collection_id' => $collection->id,
        'type' => QodeType::Redirect->value,
        'settings' => [
            'destination' => RedirectDestination::MODE_URL,
            'url' => 'https://example.com/elsewhere',
        ],
    ]);

    expect(fn () => app(RedirectDestination::class)->validateForSave($outer, [
        'destination' => RedirectDestination::MODE_QODE,
        'target_qode_id' => $inner->id,
    ]))->toThrow(ValidationException::class);
});

it('rejects self-referential redirect targets', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Loop risk',
        'collection_id' => $collection->id,
        'type' => QodeType::Redirect->value,
        'settings' => [
            'destination' => RedirectDestination::MODE_URL,
            'url' => 'https://example.com',
        ],
    ]);

    expect(fn () => app(RedirectDestination::class)->validateForSave($qode, [
        'destination' => RedirectDestination::MODE_QODE,
        'target_qode_id' => $qode->id,
    ]))->toThrow(ValidationException::class);
});

it('returns 404 when a stored target qode is a redirect cascade', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $content = app(CreateQode::class)->handle($tenant, [
        'name' => 'Was content',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
    ]);

    $redirect = app(CreateQode::class)->handle($tenant, [
        'name' => 'Points at content',
        'collection_id' => $collection->id,
        'type' => QodeType::Redirect->value,
        'settings' => [
            'destination' => RedirectDestination::MODE_QODE,
            'target_qode_id' => $content->id,
        ],
    ]);

    $content->forceFill([
        'type' => QodeType::Redirect,
        'settings' => [
            'destination' => RedirectDestination::MODE_URL,
            'url' => 'https://example.com/hijack',
        ],
    ])->save();

    $this->get('/r/'.$redirect->slug)->assertNotFound();
});

it('preserves content settings when switching type to redirect', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Seasonal',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'title' => 'Winter story',
            'body' => '<p>Keep me</p>',
        ],
    ]);

    $qode->forceFill([
        'type' => QodeType::Redirect,
        'settings' => array_merge($qode->settings, [
            'destination' => RedirectDestination::MODE_URL,
            'url' => 'https://example.com/promo',
            'target_qode_id' => null,
        ]),
    ])->save();

    $qode->refresh();

    expect($qode->type)->toBe(QodeType::Redirect)
        ->and($qode->settings['title'])->toBe('Winter story')
        ->and($qode->settings['body'])->toBe('<p>Keep me</p>')
        ->and($qode->settings['url'])->toBe('https://example.com/promo');

    $qode->forceFill([
        'type' => QodeType::Content,
    ])->save();

    $qode->refresh();

    expect($qode->type)->toBe(QodeType::Content)
        ->and($qode->settings['title'])->toBe('Winter story')
        ->and($qode->settings['body'])->toBe('<p>Keep me</p>');

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('Winter story', false)
        ->assertSee('Keep me', false);
});

it('excludes redirect qodes from searchable destination options', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $content = app(CreateQode::class)->handle($tenant, [
        'name' => 'Safe target',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
    ]);

    $redirect = app(CreateQode::class)->handle($tenant, [
        'name' => 'Not a target',
        'collection_id' => $collection->id,
        'type' => QodeType::Redirect->value,
    ]);

    $options = app(RedirectDestination::class)->searchableOptions((int) $tenant->id, null, '');

    expect($options)->toHaveKey($content->id)
        ->and($options)->not->toHaveKey($redirect->id);
});
