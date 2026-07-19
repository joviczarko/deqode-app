<?php

use App\Models\Collection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scopes collections so a tenant user cannot see another tenants data', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    User::factory()->create(['tenant_id' => $tenantB->id]);

    $collectionA = Collection::withoutGlobalScopes()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'General',
        'is_default' => true,
    ]);

    $collectionB = Collection::withoutGlobalScopes()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'General',
        'is_default' => true,
    ]);

    $this->actingAs($userA);

    $visible = Collection::query()->pluck('id');

    expect($visible)->toContain($collectionA->id)
        ->and($visible)->not->toContain($collectionB->id);

    expect($userA->can('view', $collectionA))->toBeTrue()
        ->and($userA->can('view', $collectionB))->toBeFalse();
});

it('allows the tenant user to open the app panel dashboard', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Collection::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'name' => 'General',
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful();
});

it('allows the seeded superadmin shape to open the admin panel', function () {
    $admin = User::factory()->superAdmin()->create([
        'email' => 'admin@seed.test',
        'password' => 'password',
    ]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});
