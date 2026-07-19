<?php

namespace App\Support;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

final class OptionalPassword
{
    public const string ToggleField = 'use_custom_password';

    /**
     * @return array<Component>
     */
    public static function formComponents(?string $autoHint = null): array
    {
        $autoHint ??= 'We will generate a secure password and email it to you.';

        return [
            Checkbox::make(self::ToggleField)
                ->label('I want to enter my password')
                ->live(),
            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->confirmed()
                ->rule(Password::min(8)->letters()->numbers())
                ->visible(fn (Get $get): bool => self::usesCustomPasswordFromState($get))
                ->required(fn (Get $get): bool => self::usesCustomPasswordFromState($get))
                ->autocomplete('new-password'),
            TextInput::make('password_confirmation')
                ->label('Confirm password')
                ->password()
                ->revealable()
                ->visible(fn (Get $get): bool => self::usesCustomPasswordFromState($get))
                ->required(fn (Get $get): bool => self::usesCustomPasswordFromState($get))
                ->autocomplete('new-password'),
            Placeholder::make('optional_password_auto_hint')
                ->hiddenLabel()
                ->content($autoHint)
                ->visible(fn (Get $get): bool => ! self::usesCustomPasswordFromState($get)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function usesCustomPassword(array $data): bool
    {
        return (bool) ($data[self::ToggleField] ?? false);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function resolve(array $data): ResolvedPassword
    {
        if (self::usesCustomPassword($data)) {
            return new ResolvedPassword(
                plain: (string) ($data['password'] ?? ''),
                isCustom: true,
            );
        }

        return new ResolvedPassword(
            plain: SecurePasswordGenerator::generate(),
            isCustom: false,
        );
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function validationRules(bool $useCustomPassword): array
    {
        if (! $useCustomPassword) {
            return [
                self::ToggleField => ['sometimes', 'boolean'],
            ];
        }

        return [
            self::ToggleField => ['sometimes', 'boolean'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'password_confirmation' => ['required'],
        ];
    }

    private static function usesCustomPasswordFromState(Get $get): bool
    {
        return (bool) $get(self::ToggleField);
    }
}
