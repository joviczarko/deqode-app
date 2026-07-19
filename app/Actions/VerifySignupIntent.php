<?php

namespace App\Actions;

use App\Models\SignupIntent;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerifySignupIntent
{
    public function handle(string $token): SignupIntent
    {
        $intent = SignupIntent::query()->where('token', $token)->firstOrFail();

        if ($intent->isCompleted()) {
            throw ValidationException::withMessages([
                'token' => 'Registration is already complete. Please sign in.',
            ]);
        }

        if ($intent->isExpired()) {
            $intent->markExpired();

            throw ValidationException::withMessages([
                'token' => 'This verification link has expired. Please start registration again.',
            ]);
        }

        if (User::query()->where('email', $intent->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists.',
            ]);
        }

        if (! $intent->isEmailVerified()) {
            $intent->markEmailVerified();
        }

        return $intent->fresh();
    }

    public function findVerifiedForSession(?int $intentId): ?SignupIntent
    {
        if ($intentId === null) {
            return null;
        }

        $intent = SignupIntent::query()->find($intentId);

        if ($intent === null || ! $intent->isEmailVerified() || $intent->isCompleted() || $intent->isExpired()) {
            return null;
        }

        return $intent;
    }
}
