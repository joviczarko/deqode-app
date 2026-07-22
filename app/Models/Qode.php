<?php

namespace App\Models;

use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Concerns\BelongsToTenant;
use App\Support\SqidsEncoder;
use Database\Factories\QodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id',
    'collection_id',
    'domain_id',
    'name',
    'slug',
    'type',
    'status',
    'settings',
])]
class Qode extends Model
{
    /** @use HasFactory<QodeFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'type' => QodeType::class,
            'status' => QodeStatus::class,
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Qode $qode): void {
            if (filled($qode->slug)) {
                return;
            }

            $qode->forceFill([
                'slug' => app(SqidsEncoder::class)->encode($qode->id),
            ])->saveQuietly();
        });
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function isActive(): bool
    {
        return $this->status === QodeStatus::Active;
    }
}
