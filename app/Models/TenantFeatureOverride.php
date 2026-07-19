<?php

namespace App\Models;

use Database\Factories\TenantFeatureOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'package_id',
    'price_monthly_cents',
    'quota_overrides',
    'feature_overrides',
    'notes',
])]
class TenantFeatureOverride extends Model
{
    /** @use HasFactory<TenantFeatureOverrideFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_monthly_cents' => 'integer',
            'quota_overrides' => 'array',
            'feature_overrides' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
