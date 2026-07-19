<?php

namespace App\Actions;

use App\Models\File;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StoreTenantFile
{
    public function handle(Tenant $tenant, UploadedFile $upload, ?string $disk = null): File
    {
        $disk ??= (string) config('filesystems.default');

        $safeName = Str::slug((string) pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = $upload->getClientOriginalExtension();
        $filename = (string) Str::uuid7()
            .($safeName !== '' ? '-'.$safeName : '')
            .($extension !== '' ? '.'.$extension : '');

        $path = $upload->storeAs((string) $tenant->id, $filename, [
            'disk' => $disk,
        ]);

        if ($path === false) {
            throw new RuntimeException('Failed to store uploaded file on disk ['.$disk.'].');
        }

        $storage = Storage::disk($disk);

        return File::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'disk' => $disk,
            'path' => $path,
            'mime' => $upload->getClientMimeType() ?: $storage->mimeType($path),
            'size' => $upload->getSize() ?: $storage->size($path),
            'original_name' => $upload->getClientOriginalName(),
        ]);
    }
}
