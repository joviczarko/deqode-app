<?php

namespace App\Models;

use App\Enums\SignupIntentStatus;
use Database\Factories\SignupIntentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'token',
    'email',
    'status',
    'email_verified_at',
    'completed_at',
    'user_id',
    'attempt_count',
    'ip_address',
    'referrer',
    'expires_at',
])]
class SignupIntent extends Model
{
    /** @use HasFactory<SignupIntentFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'attempt_count' => 1,
    ];

    protected function casts(): array
    {
        return [
            'status' => SignupIntentStatus::class,
            'email_verified_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === SignupIntentStatus::Pending;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null
            && in_array($this->status, [SignupIntentStatus::EmailVerified, SignupIntentStatus::Completed], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === SignupIntentStatus::Completed;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === SignupIntentStatus::Expired;
    }

    public function markEmailVerified(): void
    {
        $this->forceFill([
            'email_verified_at' => now(),
            'status' => SignupIntentStatus::EmailVerified,
        ])->save();
    }

    public function markCompleted(User $user): void
    {
        $this->forceFill([
            'status' => SignupIntentStatus::Completed,
            'completed_at' => now(),
            'user_id' => $user->id,
        ])->save();
    }

    public function markExpired(): void
    {
        if ($this->status !== SignupIntentStatus::Completed) {
            $this->forceFill(['status' => SignupIntentStatus::Expired])->save();
        }
    }
}
