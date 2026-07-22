<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'qode_id',
    'visited_at',
    'referrer',
    'user_agent',
    'device',
])]
class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    public function qode(): BelongsTo
    {
        return $this->belongsTo(Qode::class);
    }
}
