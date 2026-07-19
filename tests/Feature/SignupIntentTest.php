<?php

use App\Actions\CompleteSignup;
use App\Actions\CreateSignupIntent;
use App\Actions\VerifySignupIntent;
use App\Enums\SubscriptionStatus;
use App\Mail\SignupIntentVerificationMail;
use App\Mail\WelcomeMail;
use App\Models\Collection;
use App\Models\Package;
use App\Models\SignupIntent;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    Package::factory()->freeTrial()->create();
});

it('completes signup intent happy path with tenant collection and free trial', function () {
    Mail::fake();

    $intent = app(CreateSignupIntent::class)->handle([
        'email' => 'owner@example.com',
    ], '127.0.0.1');

    expect($intent)->toBeInstanceOf(SignupIntent::class)
        ->and($intent->email)->toBe('owner@example.com');

    Mail::assertQueued(SignupIntentVerificationMail::class);

    $verified = app(VerifySignupIntent::class)->handle($intent->token);

    expect($verified->isEmailVerified())->toBeTrue();

    $user = app(CompleteSignup::class)->handle($verified, [
        'name' => 'Owner Person',
        'tenant_name' => 'Acme Labels',
    ]);

    expect($user->email)->toBe('owner@example.com')
        ->and($user->tenant_id)->toBeGreaterThanOrEqual(4000)
        ->and($user->tenant->name)->toBe('Acme Labels');

    $general = Collection::withoutGlobalScopes()
        ->where('tenant_id', $user->tenant_id)
        ->where('is_default', true)
        ->first();

    expect($general)->not->toBeNull()
        ->and($general->name)->toBe('General');

    $subscription = Subscription::query()
        ->where('tenant_id', $user->tenant_id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatus::Trial)
        ->and($subscription->package->slug)->toBe('free');

    Mail::assertQueued(WelcomeMail::class);
});

it('verifies signup via signed url and stores session for completion', function () {
    Mail::fake();

    $intent = SignupIntent::factory()->create([
        'email' => 'verify-me@example.com',
    ]);

    $url = URL::temporarySignedRoute(
        'signup.verify',
        $intent->expires_at,
        ['token' => $intent->token],
    );

    $this->get($url)
        ->assertRedirect(route('filament.app.register.complete'));

    expect(session('verified_signup_intent_id'))->toBe($intent->id);

    $intent->refresh();
    expect($intent->isEmailVerified())->toBeTrue();
});
