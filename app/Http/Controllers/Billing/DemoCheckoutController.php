<?php

namespace App\Http\Controllers\Billing;

use App\Actions\CompleteDemoCheckout;
use App\Billing\Contracts\PaymentGatewayInterface;
use App\Enums\CheckoutResult;
use App\Filament\App\Pages\Billing;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DemoCheckoutController extends Controller
{
    public function show(Request $request, string $token, PaymentGatewayInterface $gateway): View|RedirectResponse
    {
        $this->assertDemoProviderEnabled();

        $session = $gateway->findPendingSession($token);

        if ($session === null) {
            abort(404, 'Checkout session not found or expired.');
        }

        $user = $request->user();

        if (! $user instanceof User || $user->tenant_id !== $session->tenant_id) {
            abort(403);
        }

        $session->load(['package', 'tenant']);

        return view('billing.demo-checkout', [
            'session' => $session,
            'billingUrl' => Billing::getUrl(panel: 'app'),
        ]);
    }

    public function complete(Request $request, CompleteDemoCheckout $action): RedirectResponse
    {
        $this->assertDemoProviderEnabled();

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'result' => ['required', 'in:success,fail,cancel'],
        ]);

        $session = app(PaymentGatewayInterface::class)->findPendingSession($validated['token']);

        if ($session === null) {
            abort(404, 'Checkout session not found or expired.');
        }

        $user = $request->user();

        if (! $user instanceof User || $user->tenant_id !== $session->tenant_id) {
            abort(403);
        }

        try {
            $action->handle($validated['token'], CheckoutResult::from($validated['result']));
        } catch (ValidationException $exception) {
            return redirect()
                ->to(Billing::getUrl(panel: 'app'))
                ->withErrors($exception->errors());
        }

        $billingUrl = Billing::getUrl(panel: 'app');

        return match ($validated['result']) {
            'success' => redirect()->to($billingUrl)->with('success', 'Demo payment succeeded. Plan activated.'),
            'fail' => redirect()->to($billingUrl)->with('warning', 'Demo payment failed. Plan unchanged.'),
            default => redirect()->to($billingUrl)->with('status', 'Checkout cancelled.'),
        };
    }

    private function assertDemoProviderEnabled(): void
    {
        if (config('billing.provider') !== 'demo') {
            abort(404);
        }
    }
}
