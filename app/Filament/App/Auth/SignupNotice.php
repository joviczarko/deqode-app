<?php

namespace App\Filament\App\Auth;

use App\Mail\SignupIntentVerificationMail;
use App\Models\SignupIntent;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class SignupNotice extends SimplePage
{
    public ?string $email = null;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $user = Filament::auth()->user();
            if ($user instanceof User) {
                redirect()->intended(filament()->getUrl());
            }
        }

        $this->email = session('signup_intent_email');
    }

    public function resend(): void
    {
        if (! is_string($this->email) || $this->email === '') {
            Notification::make()
                ->title('Email not found')
                ->body('Start registration again.')
                ->warning()
                ->send();

            $this->redirect(RegisterStart::getUrl());

            return;
        }

        $intent = SignupIntent::query()
            ->where('email', strtolower($this->email))
            ->whereIn('status', ['pending', 'email_verified'])
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($intent !== null) {
            Mail::to($intent->email)->queue(new SignupIntentVerificationMail($intent));
        }

        Notification::make()
            ->title('Email sent')
            ->body('If a signup is in progress, we sent a new verification link.')
            ->success()
            ->send();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Check your email';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Check your email';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $email = e($this->email ?? 'your inbox');

        return new HtmlString(
            "We sent a verification link to <strong>{$email}</strong>."
        );
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Actions::make([
                Action::make('resend')
                    ->label('Resend verification email')
                    ->action('resend'),
            ])->fullWidth(),
            Actions::make([
                Action::make('back')
                    ->link()
                    ->label('Back to registration')
                    ->url(RegisterStart::getUrl()),
            ])->alignment('center'),
        ]);
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true): string
    {
        return route('filament.app.signup.notice', $parameters, $isAbsolute);
    }
}
