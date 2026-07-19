<?php

namespace App\Actions;

use App\Billing\Contracts\PaymentGatewayInterface;
use App\Enums\CheckoutResult;
use App\Enums\CheckoutSessionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\CheckoutSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteDemoCheckout
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
    ) {}

    /**
     * @return array{invoice: Invoice|null, payment: Payment|null, session: CheckoutSession}
     */
    public function handle(string $token, CheckoutResult $result): array
    {
        $session = $this->gateway->findPendingSession($token);

        if ($session === null) {
            throw ValidationException::withMessages([
                'token' => 'Checkout session not found or expired.',
            ]);
        }

        return match ($result) {
            CheckoutResult::Success => $this->succeed($session),
            CheckoutResult::Fail => $this->fail($session),
            CheckoutResult::Cancel => $this->cancel($session),
        };
    }

    /**
     * @return array{invoice: Invoice, payment: Payment, session: CheckoutSession}
     */
    private function succeed(CheckoutSession $session): array
    {
        return DB::transaction(function () use ($session): array {
            $subscription = Subscription::query()->updateOrCreate(
                ['tenant_id' => $session->tenant_id],
                [
                    'package_id' => $session->package_id,
                    'status' => SubscriptionStatus::Active,
                    'trial_ends_at' => null,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                ],
            );

            $invoice = Invoice::withoutGlobalScopes()->create([
                'tenant_id' => $session->tenant_id,
                'subscription_id' => $subscription->id,
                'package_id' => $session->package_id,
                'number' => $this->nextInvoiceNumber($session->tenant_id),
                'status' => InvoiceStatus::Paid,
                'amount_cents' => $session->amount_cents,
                'currency' => $session->currency,
                'gateway' => $session->gateway,
                'paid_at' => now(),
                'meta' => ['checkout_session_id' => $session->id],
            ]);

            $payment = Payment::withoutGlobalScopes()->create([
                'tenant_id' => $session->tenant_id,
                'invoice_id' => $invoice->id,
                'status' => PaymentStatus::Paid,
                'amount_cents' => $session->amount_cents,
                'currency' => $session->currency,
                'gateway' => $session->gateway,
                'gateway_reference' => 'demo-'.uniqid(),
                'paid_at' => now(),
            ]);

            $this->log($session, $payment, $invoice, 'payment.succeeded', [
                'result' => CheckoutResult::Success->value,
            ]);

            $session->forceFill([
                'status' => CheckoutSessionStatus::Completed,
                'completed_at' => now(),
            ])->save();

            return [
                'invoice' => $invoice,
                'payment' => $payment,
                'session' => $session->fresh(),
            ];
        });
    }

    /**
     * @return array{invoice: Invoice, payment: Payment, session: CheckoutSession}
     */
    private function fail(CheckoutSession $session): array
    {
        return DB::transaction(function () use ($session): array {
            $invoice = Invoice::withoutGlobalScopes()->create([
                'tenant_id' => $session->tenant_id,
                'subscription_id' => null,
                'package_id' => $session->package_id,
                'number' => $this->nextInvoiceNumber($session->tenant_id),
                'status' => InvoiceStatus::Failed,
                'amount_cents' => $session->amount_cents,
                'currency' => $session->currency,
                'gateway' => $session->gateway,
                'meta' => ['checkout_session_id' => $session->id],
            ]);

            $payment = Payment::withoutGlobalScopes()->create([
                'tenant_id' => $session->tenant_id,
                'invoice_id' => $invoice->id,
                'status' => PaymentStatus::Failed,
                'amount_cents' => $session->amount_cents,
                'currency' => $session->currency,
                'gateway' => $session->gateway,
                'gateway_reference' => 'demo-fail-'.uniqid(),
            ]);

            $this->log($session, $payment, $invoice, 'payment.failed', [
                'result' => CheckoutResult::Fail->value,
            ]);

            $session->forceFill([
                'status' => CheckoutSessionStatus::Failed,
                'completed_at' => now(),
            ])->save();

            return [
                'invoice' => $invoice,
                'payment' => $payment,
                'session' => $session->fresh(),
            ];
        });
    }

    /**
     * @return array{invoice: null, payment: null, session: CheckoutSession}
     */
    private function cancel(CheckoutSession $session): array
    {
        $this->log($session, null, null, 'checkout.cancelled', [
            'result' => CheckoutResult::Cancel->value,
        ]);

        $session->forceFill([
            'status' => CheckoutSessionStatus::Cancelled,
            'completed_at' => now(),
        ])->save();

        return [
            'invoice' => null,
            'payment' => null,
            'session' => $session->fresh(),
        ];
    }

    private function nextInvoiceNumber(int $tenantId): string
    {
        $count = Invoice::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();

        return sprintf('INV-%d-%04d', $tenantId, $count + 1);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function log(
        CheckoutSession $session,
        ?Payment $payment,
        ?Invoice $invoice,
        string $event,
        array $payload,
    ): void {
        PaymentLog::query()->create([
            'tenant_id' => $session->tenant_id,
            'payment_id' => $payment?->id,
            'invoice_id' => $invoice?->id,
            'gateway' => $session->gateway,
            'event' => $event,
            'payload' => array_merge($payload, [
                'checkout_session_id' => $session->id,
                'token' => $session->token,
            ]),
        ]);
    }
}
