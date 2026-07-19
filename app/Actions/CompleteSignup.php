<?php

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Mail\WelcomeMail;
use App\Models\Collection;
use App\Models\Package;
use App\Models\SignupIntent;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\OptionalPassword;
use App\Support\ResolvedPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CompleteSignup
{
    /**
     * @param  array{name: string, tenant_name: string, use_custom_password?: bool, password?: string}  $data
     */
    public function handle(SignupIntent $intent, array $data): User
    {
        if (! $intent->isEmailVerified() || $intent->isCompleted() || $intent->isExpired()) {
            throw ValidationException::withMessages([
                'token' => 'This signup intent is not ready to complete.',
            ]);
        }

        if (User::query()->where('email', $intent->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists.',
            ]);
        }

        $package = Package::freeTrial();

        if ($package === null) {
            throw new RuntimeException('Free/Trial package is not seeded.');
        }

        $password = OptionalPassword::resolve($data);

        $user = DB::transaction(function () use ($data, $intent, $password, $package): User {
            $tenant = Tenant::query()->create([
                'name' => $data['tenant_name'],
                'slug' => $this->uniqueSlug($data['tenant_name']),
                'status' => TenantStatus::Active,
            ]);

            Collection::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => 'General',
                'is_default' => true,
            ]);

            $trialDays = max(1, (int) $package->trial_days);

            Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'package_id' => $package->id,
                'status' => SubscriptionStatus::Trial,
                'trial_ends_at' => now()->addDays($trialDays),
                'starts_at' => now(),
            ]);

            $user = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $intent->email,
                'password' => $password->plain,
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]);

            $intent->markCompleted($user);

            return $user;
        });

        $this->sendWelcome($user, $password);

        Auth::login($user);

        session()->forget(['verified_signup_intent_id', 'signup_intent_email']);

        return $user;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'tenant';
        $slug = $base;
        $suffix = 1;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function sendWelcome(User $user, ResolvedPassword $password): void
    {
        Mail::to($user->email)->queue(new WelcomeMail(
            $user,
            $password->includeInEmail() ? $password->plain : null,
        ));
    }
}
