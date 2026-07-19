<?php

namespace App\Actions;

use App\Enums\SignupIntentStatus;
use App\Mail\SignupIntentVerificationMail;
use App\Models\SignupIntent;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CreateSignupIntent
{
    /**
     * @param  array{email: string, referrer?: string|null}  $data
     */
    public function handle(array $data, ?string $ipAddress = null): SignupIntent
    {
        $email = strtolower(trim($data['email']));

        $this->assertEmailAvailable($email);
        $this->assertWithinRateLimits($email, $ipAddress);

        $intent = SignupIntent::query()->create([
            'token' => SignupIntent::generateToken(),
            'email' => $email,
            'status' => SignupIntentStatus::Pending,
            'attempt_count' => $this->nextAttemptCount($email),
            'ip_address' => $ipAddress,
            'referrer' => $data['referrer'] ?? null,
            'expires_at' => now()->addHours((int) config('signup.intent_ttl_hours', 48)),
        ]);

        Mail::to($intent->email)->queue(new SignupIntentVerificationMail($intent));

        return $intent;
    }

    private function assertEmailAvailable(string $email): void
    {
        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists.',
            ]);
        }
    }

    private function assertWithinRateLimits(string $email, ?string $ipAddress): void
    {
        $maxPerEmail = (int) config('signup.max_intents_per_email_per_day', 5);
        $emailCount = SignupIntent::query()
            ->where('email', $email)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($emailCount >= $maxPerEmail) {
            throw ValidationException::withMessages([
                'email' => 'Too many signup attempts for this email. Try again later.',
            ]);
        }

        if ($ipAddress === null) {
            return;
        }

        $maxPerIp = (int) config('signup.max_intents_per_ip_per_hour', 10);
        $ipCount = SignupIntent::query()
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($ipCount >= $maxPerIp) {
            throw ValidationException::withMessages([
                'email' => 'Too many signup attempts. Try again later.',
            ]);
        }
    }

    private function nextAttemptCount(string $email): int
    {
        $latest = SignupIntent::query()
            ->where('email', $email)
            ->latest('id')
            ->value('attempt_count');

        return ($latest ?? 0) + 1;
    }
}
