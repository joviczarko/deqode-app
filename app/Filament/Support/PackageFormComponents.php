<?php

namespace App\Filament\Support;

use App\Billing\PackageCatalog;
use App\Enums\PackageStatus;
use App\Models\Package;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

final class PackageFormComponents
{
    /**
     * Fixed quota + feature fields for package create/edit.
     *
     * @return array<int, Section>
     */
    public static function entitlementSections(): array
    {
        $quotaFields = [];

        foreach (PackageCatalog::quotaDefinitions() as $key => $definition) {
            $quotaFields[] = TextInput::make('quotas.'.$key)
                ->label($definition['label'])
                ->numeric()
                ->required()
                ->default($definition['default'] ?? null);
        }

        $featureFields = [];

        foreach (PackageCatalog::featureDefinitions() as $key => $definition) {
            $featureFields[] = Toggle::make('features.'.$key)
                ->label($definition['label'])
                ->default((bool) ($definition['default'] ?? false));
        }

        return [
            Section::make('Quotas')
                ->description('Numeric limits for this package.')
                ->schema($quotaFields)
                ->columns(2),
            Section::make('Features')
                ->description('Boolean capabilities for this package.')
                ->schema($featureFields)
                ->columns(2),
        ];
    }

    /**
     * Override fields: empty / Inherit means keep package default.
     *
     * @return array<int, Section|Select|TextInput>
     */
    public static function overrideSections(): array
    {
        $quotaFields = [];

        foreach (PackageCatalog::quotaDefinitions() as $key => $definition) {
            $quotaFields[] = TextInput::make('quota_overrides.'.$key)
                ->label($definition['label'])
                ->numeric()
                ->nullable()
                ->placeholder('Inherit from package');
        }

        $featureFields = [];

        foreach (PackageCatalog::featureDefinitions() as $key => $definition) {
            $featureFields[] = Select::make('feature_overrides.'.$key)
                ->label($definition['label'])
                ->options([
                    'inherit' => 'Inherit from package',
                    '1' => 'Enabled',
                    '0' => 'Disabled',
                ])
                ->default('inherit')
                ->native(false);
        }

        return [
            Select::make('package_id')
                ->label('Force package')
                ->options(fn () => PackageCatalog::scopeAssignable(
                    Package::query()
                )->pluck('name', 'id'))
                ->placeholder('Use subscription package')
                ->nullable(),
            TextInput::make('price_monthly_cents')
                ->label('Price override (cents / month)')
                ->numeric()
                ->nullable()
                ->helperText('Leave empty to use package price.'),
            Section::make('Quota overrides')
                ->schema($quotaFields)
                ->columns(2),
            Section::make('Feature overrides')
                ->schema($featureFields)
                ->columns(2),
        ];
    }

    public static function statusSelect(): Select
    {
        return Select::make('status')
            ->options(collect(PackageStatus::cases())->mapWithKeys(
                fn (PackageStatus $status) => [$status->value => $status->label()]
            ))
            ->required()
            ->native(false)
            ->helperText('Controls who can see / buy this package.');
    }
}
