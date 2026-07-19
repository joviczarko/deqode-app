<?php

use App\Actions\CreateQode;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attaches categories to a qode', function () {
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);
    $category = Category::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Packaging',
    ]);

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Bottle label',
        'collection_id' => $collection->id,
    ]);

    $qode->categories()->attach($category);

    expect($qode->fresh()->categories)->toHaveCount(1)
        ->and($qode->categories->first()->is($category))->toBeTrue()
        ->and($category->fresh()->qodes)->toHaveCount(1);
});

it('scopes categories to the owning tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $categoryA = Category::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'A']);
    Category::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'B']);

    $user = User::factory()->create(['tenant_id' => $tenantA->id]);

    $this->actingAs($user);

    expect(Category::query()->pluck('id')->all())->toBe([$categoryA->id]);
});
