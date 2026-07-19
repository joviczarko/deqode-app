<?php

use App\Actions\StoreTenantFile;
use App\Models\File;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates a file record with a tenant-prefixed s3 path', function () {
    Storage::fake('s3');

    $tenant = Tenant::factory()->create();
    $upload = UploadedFile::fake()->create('manual.pdf', 120, 'application/pdf');

    $file = app(StoreTenantFile::class)->handle($tenant, $upload, 's3');

    expect($file)->toBeInstanceOf(File::class)
        ->and($file->tenant_id)->toBe($tenant->id)
        ->and($file->disk)->toBe('s3')
        ->and($file->original_name)->toBe('manual.pdf')
        ->and($file->path)->toStartWith($tenant->id.'/')
        ->and($file->path)->toContain('-manual.pdf')
        ->and($file->size)->toBeGreaterThan(0);

    Storage::disk('s3')->assertExists($file->path);
});

it('deletes the object from storage when the file record is deleted', function () {
    Storage::fake('s3');

    $tenant = Tenant::factory()->create();
    $file = app(StoreTenantFile::class)->handle(
        $tenant,
        UploadedFile::fake()->create('delete-me.txt', 10, 'text/plain'),
        's3',
    );

    $path = $file->path;
    $file->delete();

    Storage::disk('s3')->assertMissing($path);
    expect(File::withoutGlobalScopes()->whereKey($file->id)->exists())->toBeFalse();
});
