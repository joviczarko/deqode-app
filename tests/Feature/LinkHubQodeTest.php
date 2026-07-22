<?php

use App\Actions\CreateQode;
use App\Enums\QodeType;
use App\Filament\App\Resources\Qodes\Pages\EditQode;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use App\QodeModules\RedirectDestination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders link hub labels and urls on the public page', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Campaign links',
        'collection_id' => $collection->id,
        'type' => QodeType::LinkHub->value,
        'settings' => [
            'links' => [
                ['label' => 'Shop', 'url' => 'https://shop.example.com'],
                ['label' => 'Manual', 'url' => 'https://docs.example.com/manual'],
            ],
        ],
    ]);

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('Campaign links', false)
        ->assertSee('Shop', false)
        ->assertSee('https://shop.example.com', false)
        ->assertSee('Manual', false)
        ->assertSee('https://docs.example.com/manual', false)
        ->assertSee('data-deqode-module="link_hub"', false)
        ->assertSee('data-deqode-wrapper="1"', false);
});

it('rejects link hub rows without a valid url on the filament form', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Links',
        'collection_id' => $collection->id,
        'type' => QodeType::LinkHub->value,
        'settings' => [
            'links' => [
                ['label' => 'Shop', 'url' => 'https://shop.example.com'],
            ],
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(EditQode::class, ['record' => $qode->getKey()])
        ->fillForm([
            'name' => 'Links',
            'collection_id' => $collection->id,
            'type' => QodeType::LinkHub->value,
            'status' => $qode->status->value,
            'settings' => [
                'links' => [
                    ['label' => 'Broken', 'url' => 'not-a-url'],
                ],
                'redirect' => [
                    'to' => RedirectDestination::MODE_NONE,
                    'url' => 'https://example.com',
                    'target_qode_id' => null,
                ],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors(['settings.links.0.url']);
});

it('persists link hub edits from the filament form onto the public page', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Before',
        'collection_id' => $collection->id,
        'type' => QodeType::LinkHub->value,
        'settings' => [
            'links' => [
                ['label' => 'Old', 'url' => 'https://old.example.com'],
            ],
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(EditQode::class, ['record' => $qode->getKey()])
        ->fillForm([
            'name' => 'After edit',
            'collection_id' => $collection->id,
            'type' => QodeType::LinkHub->value,
            'status' => $qode->status->value,
            'settings' => [
                'links' => [
                    ['label' => 'New shop', 'url' => 'https://new.example.com'],
                ],
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

    expect($qode->name)->toBe('After edit')
        ->and($qode->settings['links'][0]['label'])->toBe('New shop')
        ->and($qode->settings['links'][0]['url'])->toBe('https://new.example.com');

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('After edit', false)
        ->assertSee('New shop', false)
        ->assertSee('https://new.example.com', false)
        ->assertDontSee('Old', false);
});
