<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'tenant_id',
    'disk',
    'path',
    'mime',
    'size',
    'original_name',
])]
class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'size' => 0,
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (File $file): void {
            Storage::disk($file->disk)->delete($file->path);
        });
    }
}
