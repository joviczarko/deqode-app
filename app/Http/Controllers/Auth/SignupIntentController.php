<?php

namespace App\Http\Controllers\Auth;

use App\Actions\VerifySignupIntent;
use App\Filament\App\Auth\RegisterComplete;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SignupIntentController extends Controller
{
    public function verify(Request $request, string $token, VerifySignupIntent $action): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This verification link is invalid or has expired.');
        }

        try {
            $intent = $action->handle($token);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('filament.app.auth.register')
                ->withErrors($exception->errors());
        }

        session([
            'verified_signup_intent_id' => $intent->id,
            'signup_intent_email' => $intent->email,
        ]);

        return redirect()
            ->to(RegisterComplete::getUrl())
            ->with('success', 'Email confirmed. Finish creating your account.');
    }
}
