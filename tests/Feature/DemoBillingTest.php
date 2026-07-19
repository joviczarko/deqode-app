<?php

use App\Actions\CompleteDemoCheckout;
use App\Actions\StartCheckout;
use App\Enums\CheckoutResult;
use App\Enums\CheckoutSessionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Package::factory()->freeTrial()->create();
    $this->starter = Package::factory()->starter()->create();
});

it('activates paid subscription and creates invoice on demo success', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::freeTrial()->id,
        'status' => SubscriptionStatus::Trial,
    ]);

    $redirect = app(StartCheckout::class)->handle($tenant, $this->starter, $user);

    $result = app(CompleteDemoCheckout::class)->handle($redirect->token, CheckoutResult::Success);

    expect($result['invoice'])->toBeInstanceOf(Invoice::class)
        ->and($result['invoice']->status)->toBe(InvoiceStatus::Paid)
        ->and($result['payment']->status)->toBe(PaymentStatus::Paid);

    $subscription = Subscription::query()->where('tenant_id', $tenant->id)->latest('id')->first();

    expect($subscription->package_id)->toBe($this->starter->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);

    expect(PaymentLog::query()->where('event', 'payment.succeeded')->exists())->toBeTrue();
});

it('records failed invoice without changing plan on demo fail', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $free = Package::freeTrial();

    $subscription = Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $free->id,
        'status' => SubscriptionStatus::Trial,
    ]);

    $redirect = app(StartCheckout::class)->handle($tenant, $this->starter, $user);
    $result = app(CompleteDemoCheckout::class)->handle($redirect->token, CheckoutResult::Fail);

    expect($result['invoice']->status)->toBe(InvoiceStatus::Failed)
        ->and($result['payment']->status)->toBe(PaymentStatus::Failed);

    $subscription->refresh();

    expect($subscription->package_id)->toBe($free->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Trial);

    expect(PaymentLog::query()->where('event', 'payment.failed')->exists())->toBeTrue();
});

it('cancels checkout without creating invoice or payment', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $redirect = app(StartCheckout::class)->handle($tenant, $this->starter, $user);
    $result = app(CompleteDemoCheckout::class)->handle($redirect->token, CheckoutResult::Cancel);

    expect($result['invoice'])->toBeNull()
        ->and($result['payment'])->toBeNull()
        ->and($result['session']->status)->toBe(CheckoutSessionStatus::Cancelled);

    expect(Invoice::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

it('completes demo checkout over http for the tenant user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::freeTrial()->id,
        'status' => SubscriptionStatus::Trial,
    ]);

    $this->actingAs($user);

    $redirect = app(StartCheckout::class)->handle($tenant, $this->starter, $user);

    $this->get(route('billing.demo.checkout', ['token' => $redirect->token]))
        ->assertSuccessful()
        ->assertSee('Demo gateway');

    $this->post(route('billing.demo.complete'), [
        'token' => $redirect->token,
        'result' => 'success',
    ])->assertRedirect();

    expect(Invoice::query()->where('tenant_id', $tenant->id)->where('status', InvoiceStatus::Paid)->exists())->toBeTrue();
});
