<?php

namespace App\Models;

use App\Enums\DomainStatus;
use App\Enums\DomainType;
use Database\Factories\DomainFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'hostname',
    'type',
    'tenant_id',
    'status',
    'is_default',
])]
class Domain extends Model
{
    /** @use HasFactory<DomainFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
        'is_default' => false,
    ];

    protected function casts(): array
    {
        return [
            'type' => DomainType::class,
            'status' => DomainStatus::class,
            'is_default' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function qodes(): HasMany
    {
        return $this->hasMany(Qode::class);
    }

    public static function defaultPlatform(): ?self
    {
        return static::query()
            ->where('type', DomainType::Platform)
            ->where('is_default', true)
            ->where('status', DomainStatus::Active)
            ->first();
    }
}
