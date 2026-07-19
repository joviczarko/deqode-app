<?php

namespace App\Filament\App\Auth;

use App\Actions\CompleteSignup;
use App\Actions\VerifySignupIntent;
use App\Models\SignupIntent;
use App\Models\User;
use App\Support\OptionalPassword;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class RegisterComplete extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?SignupIntent $intent = null;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $user = Filament::auth()->user();
            if ($user instanceof User) {
                redirect()->intended(filament()->getUrl());
            }
        }

        $this->intent = app(VerifySignupIntent::class)->findVerifiedForSession(
            session('verified_signup_intent_id')
        );

        if ($this->intent === null) {
            $this->redirect(RegisterStart::getUrl());

            return;
        }

        $this->form->fill();
    }

    public function complete(): void
    {
        if ($this->intent === null) {
            $this->redirect(RegisterStart::getUrl());

            return;
        }

        $data = $this->form->getState();

        app(CompleteSignup::class)->handle($this->intent, $data);

        $this->redirect(filament()->getUrl());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->getIntentFormComponents());
    }

    /**
     * @return array<Component>
     */
    protected function getIntentFormComponents(): array
    {
        return [
            Placeholder::make('locked_email')
                ->label('Email')
                ->content($this->intent?->email ?? ''),
            TextInput::make('name')
                ->label('Your name')
                ->required()
                ->minLength(2)
                ->maxLength(255)
                ->autofocus(),
            TextInput::make('tenant_name')
                ->label('Business / workspace name')
                ->required()
                ->minLength(2)
                ->maxLength(255),
            ...OptionalPassword::formComponents(),
        ];
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
        return 'Finish registration';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Finish registration';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString(
            'Already registered? '.$this->loginAction->toHtml()
        );
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('complete')
                ->label('Create account')
                ->submit('complete'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }

    public function getFormContentComponent(): Form
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('complete')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true): string
    {
        return route('filament.app.register.complete', $parameters, $isAbsolute);
    }
}
