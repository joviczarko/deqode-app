<?php

namespace App\Filament\App\Auth;

use App\Actions\CreateSignupIntent;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class RegisterStart extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $user = Filament::auth()->user();
            if ($user instanceof User) {
                redirect()->intended(filament()->getUrl());
            }
        }

        if (session()->has('verified_signup_intent_id')) {
            $this->redirect(RegisterComplete::getUrl());
        }

        $this->form->fill();
    }

    public function start(): void
    {
        try {
            $this->rateLimit(10);
        } catch (TooManyRequestsException) {
            Notification::make()
                ->title('Too many attempts')
                ->body('Please wait a moment and try again.')
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        app(CreateSignupIntent::class)->handle([
            'email' => $data['email'],
            'referrer' => request()->headers->get('referer'),
        ], request()->ip());

        session()->flash('signup_intent_email', strtolower($data['email']));

        $this->redirect(SignupNotice::getUrl());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255)
                ->autocomplete('email')
                ->autofocus(),
        ]);
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label('Sign in')
            ->url(filament()->getLoginUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return 'Create your DeQode account';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Create your DeQode account';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString(
            'Already have an account? '.$this->loginAction->toHtml()
        );
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('start')
                ->label('Continue')
                ->submit('start'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            RenderHook::make(PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE),
            $this->getFormContentComponent(),
            RenderHook::make(PanelsRenderHook::AUTH_REGISTER_FORM_AFTER),
        ]);
    }

    public function getFormContentComponent(): Form
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('start')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true): string
    {
        return filament()->getRegistrationUrl($parameters, $isAbsolute);
    }
}
