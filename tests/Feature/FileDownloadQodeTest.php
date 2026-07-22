<?php

use App\Actions\CreateQode;
use App\Actions\StoreTenantFile;
use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('shows a download page and serves the file for an active qode', function () {
    Storage::fake('s3');
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);
    $file = app(StoreTenantFile::class)->handle(
        $tenant,
        UploadedFile::fake()->create('catalog.pdf', 40, 'application/pdf'),
        's3',
    );

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Catalog',
        'collection_id' => $collection->id,
        'type' => QodeType::FileDownload->value,
        'settings' => [
            'file_id' => $file->id,
            'download_name' => 'product-catalog.pdf',
        ],
    ]);

    $this->get('/r/'.$qode->slug)
        ->assertSuccessful()
        ->assertSee('Catalog', false)
        ->assertSee('product-catalog.pdf', false)
        ->assertSee('data-deqode-module="file_download"', false)
        ->assertSee('/r/'.$qode->slug.'/download', false);

    $this->get('/r/'.$qode->slug.'/download')
        ->assertSuccessful()
        ->assertDownload('product-catalog.pdf');
});

it('rejects downloads when the file belongs to another tenant', function () {
    Storage::fake('s3');
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);
    $foreignFile = app(StoreTenantFile::class)->handle(
        $otherTenant,
        UploadedFile::fake()->create('secret.pdf', 20, 'application/pdf'),
        's3',
    );

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Bad file',
        'collection_id' => $collection->id,
        'type' => QodeType::FileDownload->value,
        'settings' => [
            'file_id' => $foreignFile->id,
        ],
    ]);

    $this->get('/r/'.$qode->slug)->assertNotFound();
    $this->get('/r/'.$qode->slug.'/download')->assertNotFound();
});

it('returns 404 for inactive file download qodes', function () {
    Storage::fake('s3');
    Domain::factory()->defaultPlatform()->create();

    $tenant = Tenant::factory()->create();
    $collection = Collection::factory()->create(['tenant_id' => $tenant->id]);
    $file = app(StoreTenantFile::class)->handle(
        $tenant,
        UploadedFile::fake()->create('menu.pdf', 15, 'application/pdf'),
        's3',
    );

    $qode = app(CreateQode::class)->handle($tenant, [
        'name' => 'Menu',
        'collection_id' => $collection->id,
        'type' => QodeType::FileDownload->value,
        'status' => QodeStatus::Inactive->value,
        'settings' => [
            'file_id' => $file->id,
        ],
    ]);

    $this->get('/r/'.$qode->slug)->assertNotFound();
    $this->get('/r/'.$qode->slug.'/download')->assertNotFound();
});
