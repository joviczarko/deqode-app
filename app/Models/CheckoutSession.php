<?php

namespace App\Models;

use App\Enums\CheckoutSessionStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\CheckoutSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'token',
    'tenant_id',
    'package_id',
    'user_id',
    'status',
    'amount_cents',
    'currency',
    'gateway',
    'expires_at',
    'completed_at',
])]
class CheckoutSession extends Model
{
    /** @use HasFactory<CheckoutSessionFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'currency' => 'USD',
    ];

    protected function casts(): array
    {
        return [
            'status' => CheckoutSessionStatus::class,
            'amount_cents' => 'integer',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === CheckoutSessionStatus::Pending
            && $this->expires_at->isFuture();
    }
}
