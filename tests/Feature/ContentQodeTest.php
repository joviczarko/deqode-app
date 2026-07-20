<?php

use App\Actions\CreateQode;
use App\Enums\QodeType;
use App\Filament\App\Resources\Qodes\Pages\EditQode;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use App\QodeModules\RedirectDestination;
use App\Support\QodeUrlBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows content title and rich html body on the public page', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Product page',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'title' => 'Bottle story',
            'body' => '<p>Fresh <strong>ingredients</strong> inside.</p>',
        ],
    ]);

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('Bottle story', false)
        ->assertSee('Fresh', false)
        ->assertSee('<strong>ingredients</strong>', false)
        ->assertSee('data-deqode-module="content"', false);
});

it('renders tiptap json body stored in settings as html', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Legacy tip tap',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'title' => 'TipTap page',
            'body' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Hello from TipTap'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('TipTap page', false)
        ->assertSee('Hello from TipTap', false)
        ->assertDontSee('Array', false);
});

it('shows the public url and content fields on the edit form', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Editable page',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'title' => 'Bottle story',
            'body' => '<p>Hello</p>',
        ],
    ]);

    $publicUrl = app(QodeUrlBuilder::class)->forQode($qode->fresh());

    $this->actingAs($user);

    Livewire::test(EditQode::class, ['record' => $qode->getKey()])
        ->assertFormSet([
            'settings.title' => 'Bottle story',
            'settings.body' => '<p>Hello</p>',
            'type' => QodeType::Content->value,
            'name' => 'Editable page',
        ])
        ->assertSee($publicUrl, false)
        ->assertSee('QR code', false)
        ->assertSee('Code: '.$qode->slug, false)
        ->assertSee('Publish', false)
        ->assertSee('Organize', false)
        ->assertSee('Title', false)
        ->assertSee('Download', false);
});

it('persists body edits from the filament form onto the public page', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Editable page',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => [
            'title' => 'Before',
            'body' => '<p>Old copy</p>',
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(EditQode::class, ['record' => $qode->getKey()])
        ->fillForm([
            'name' => 'Editable page',
            'collection_id' => $collection->id,
            'type' => QodeType::Content->value,
            'status' => $qode->status->value,
            'settings' => [
                'title' => 'After edit',
                'body' => '<p>New <em>landing</em> copy.</p>',
                'redirect' => [
                    'to' => RedirectDestination::MODE_NONE,
                    'url' => 'https://example.com',
                    'target_qode_id' => null,
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $qode->refresh();

    expect($qode->settings['title'])->toBe('After edit')
        ->and($qode->settings['body'])->toContain('landing');

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('After edit', false)
        ->assertSee('landing', false)
        ->assertDontSee('Old copy', false);
});
