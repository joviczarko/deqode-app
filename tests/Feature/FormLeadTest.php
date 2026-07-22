<?php

use App\Actions\CreateQode;
use App\Enums\QodeType;
use App\Filament\App\Resources\Leads\Pages\ListLeads;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function formQode(): array
{
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Feedback',
        'collection_id' => $collection->id,
        'type' => QodeType::Form->value,
        'settings' => [
            'fields' => [
                [
                    'key' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                ],
                [
                    'key' => 'message',
                    'label' => 'Message',
                    'type' => 'textarea',
                    'required' => false,
                ],
            ],
        ],
    ]);

    return [$tenant, $qode];
}

it('shows the public form fields', function () {
    [, $qode] = formQode();

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('Feedback', false)
        ->assertSee('Email', false)
        ->assertSee('Message', false)
        ->assertSee('data-deqode-module="form"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="message"', false);
});

it('rejects invalid form submissions', function () {
    [, $qode] = formQode();

    $this->from('/r/'.$qode->slug)
        ->post('/r/'.$qode->slug.'/leads', [
            'email' => 'not-an-email',
            'message' => 'Hello',
        ])
        ->assertRedirect('/r/'.$qode->slug)
        ->assertSessionHasErrors('email');

    expect(Lead::withoutGlobalScopes()->count())->toBe(0);
});

it('stores a lead from a valid public submission', function () {
    [$tenant, $qode] = formQode();

    $this->from('/r/'.$qode->slug)
        ->post('/r/'.$qode->slug.'/leads', [
            'email' => 'buyer@example.com',
            'message' => 'Great bottle',
        ])
        ->assertRedirect('/r/'.$qode->slug)
        ->assertSessionHas('lead_submitted', true);

    $lead = Lead::withoutGlobalScopes()->first();

    expect($lead)->not->toBeNull()
        ->and($lead->tenant_id)->toBe($tenant->id)
        ->and($lead->qode_id)->toBe($qode->id)
        ->and($lead->payload)->toMatchArray([
            'email' => 'buyer@example.com',
            'message' => 'Great bottle',
        ]);

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('data-deqode-lead-success="1"', false);
});

it('lists leads in the tenant panel and exports csv', function () {
    [$tenant, $qode] = formQode();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Lead::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'qode_id' => $qode->id,
        'payload' => [
            'email' => 'buyer@example.com',
            'message' => 'Hello',
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords(Lead::all())
        ->assertSee('buyer@example.com', false)
        ->callAction('exportCsv')
        ->assertFileDownloaded();
});
