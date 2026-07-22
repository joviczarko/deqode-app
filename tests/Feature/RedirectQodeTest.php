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

it('redirects to another qode that is not redirecting', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $content = app(CreateQode::class)->handle($tenant, [
        'name' => 'Hello',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'body' => '<p>Hi</p>',
        ],
    ]);

    $printed = app(CreateQode::class)->handle($tenant, [
        'name' => 'Parked content',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'body' => '<p>Still here</p>',
            'redirect' => [
                'to' => RedirectDestination::MODE_QODE,
                'target_qode_id' => $content->id,
            ],
        ],
    ]);

    $targetUrl = app(QodeUrlBuilder::class)->forQode($content->fresh());

    $this->get('/r/'.$printed->slug)
        ->assertRedirect($targetUrl)
        ->assertStatus(302);

    expect($printed->fresh()->name)->toBe('Parked content')
        ->and($printed->fresh()->type)->toBe(QodeType::Content);
});

it('rejects targets that are themselves redirecting', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $outer = app(CreateQode::class)->handle($tenant, [
        'name' => 'Outer',
        'collection_id' => $collection->id,
        'settings' => [
            'redirect' => [
                'to' => RedirectDestination::MODE_URL,
                'url' => 'https://example.com',
            ],
        ],
    ]);

    $inner = app(CreateQode::class)->handle($tenant, [
        'name' => 'Inner',
        'collection_id' => $collection->id,
        'settings' => [
            'redirect' => [
                'to' => RedirectDestination::MODE_URL,
                'url' => 'https://example.com/elsewhere',
            ],
        ],
    ]);

    expect(fn () => app(RedirectDestination::class)->validateForSave($outer, [
        'to' => RedirectDestination::MODE_QODE,
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
    ]);

    expect(fn () => app(RedirectDestination::class)->validateForSave($qode, [
        'to' => RedirectDestination::MODE_QODE,
        'target_qode_id' => $qode->id,
    ]))->toThrow(ValidationException::class);
});

it('returns 404 when a stored target later starts redirecting', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $content = app(CreateQode::class)->handle($tenant, [
        'name' => 'Was content',
        'collection_id' => $collection->id,
    ]);

    $printed = app(CreateQode::class)->handle($tenant, [
        'name' => 'Points at content',
        'collection_id' => $collection->id,
        'settings' => [
            'redirect' => [
                'to' => RedirectDestination::MODE_QODE,
                'target_qode_id' => $content->id,
            ],
        ],
    ]);

    $content->forceFill([
        'settings' => array_replace_recursive($content->settings, [
            'redirect' => [
                'to' => RedirectDestination::MODE_URL,
                'url' => 'https://example.com/hijack',
            ],
        ]),
    ])->save();

    $this->get('/r/'.$printed->slug)->assertNotFound();
});

it('keeps module content while redirect is on and restores it when redirect is off', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Winter story',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'body' => '<p>Keep me</p>',
        ],
    ]);

    $qode->forceFill([
        'settings' => array_replace_recursive($qode->settings, [
            'redirect' => [
                'to' => RedirectDestination::MODE_URL,
                'url' => 'https://example.com/promo',
            ],
        ]),
    ])->save();

    $qode->refresh();

    expect($qode->type)->toBe(QodeType::Content)
        ->and($qode->name)->toBe('Winter story')
        ->and($qode->settings['body'])->toBe('<p>Keep me</p>')
        ->and($qode->settings['redirect']['to'])->toBe(RedirectDestination::MODE_URL);

    $this->get('/r/'.$qode->slug)
        ->assertRedirect('https://example.com/promo');

    $qode->forceFill([
        'settings' => array_replace_recursive($qode->settings, [
            'redirect' => [
                'to' => RedirectDestination::MODE_NONE,
            ],
        ]),
    ])->save();

    $this->get('/r/'.$qode->fresh()->slug)
        ->assertSuccessful()
        ->assertSee('Winter story', false)
        ->assertSee('Keep me', false);
});

it('excludes redirecting qodes from searchable destination options', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $content = app(CreateQode::class)->handle($tenant, [
        'name' => 'Safe target',
        'collection_id' => $collection->id,
    ]);

    $redirecting = app(CreateQode::class)->handle($tenant, [
        'name' => 'Not a target',
        'collection_id' => $collection->id,
        'settings' => [
            'redirect' => [
                'to' => RedirectDestination::MODE_URL,
                'url' => 'https://example.com',
            ],
        ],
    ]);

    $options = app(RedirectDestination::class)->searchableOptions((int) $tenant->id, null, '');

    expect($options)->toHaveKey($content->id)
        ->and($options)->not->toHaveKey($redirecting->id);
});
