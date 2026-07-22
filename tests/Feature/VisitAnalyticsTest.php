<?php

use App\Actions\CreateQode;
use App\Actions\RecordVisit;
use App\Enums\QodeType;
use App\Filament\App\Pages\Analytics;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('records a visit row when a qode is resolved', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $package = Package::factory()->freeTrial()->create();
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $package->id,
    ]);

    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);
    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Tracked',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => ['body' => '<p>Hi</p>'],
    ]);

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful();

    $visit = Visit::withoutGlobalScopes()->first();

    expect($visit)->not->toBeNull()
        ->and($visit->tenant_id)->toBe($tenant->id)
        ->and($visit->qode_id)->toBe($qode->id)
        ->and($visit->device)->not->toBeEmpty();
});

it('stops recording visits once the hard scan quota is reached', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $package = Package::factory()->freeTrial()->create([
        'quotas' => ['max_qodes' => 10, 'max_scans' => 2],
    ]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $package->id,
    ]);

    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);
    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Quota',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => ['body' => '<p>Hi</p>'],
    ]);

    $request = Request::create('/r/'.$qode->slug, 'GET');
    $recorder = app(RecordVisit::class);

    expect($recorder->handle($qode, $request)['recorded'])->toBeTrue()
        ->and($recorder->handle($qode, $request)['recorded'])->toBeTrue()
        ->and($recorder->handle($qode, $request)['recorded'])->toBeFalse()
        ->and($recorder->handle($qode, $request)['over_hard'])->toBeTrue();

    expect(Visit::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count())->toBe(2);

    $this->get('/r/'.$qode->slug)->assertSuccessful();
});

it('shows analytics totals for the tenant', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $package = Package::factory()->freeTrial()->create();
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $package->id,
    ]);

    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);
    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Popular',
        'collection_id' => $collection->id,
        'type' => QodeType::Content->value,
        'settings' => ['body' => '<p>Hi</p>'],
    ]);

    Visit::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'qode_id' => $qode->id,
        'visited_at' => now(),
        'referrer' => null,
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        'device' => 'mobile',
    ]);

    $this->actingAs($user);

    Livewire::test(Analytics::class)
        ->assertSee('Total scans', false)
        ->assertSee('Popular', false)
        ->assertSee('mobile', false)
        ->assertSeeHtml('data-analytics-total="1"');
});
