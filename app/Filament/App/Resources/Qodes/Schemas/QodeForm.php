<?php

namespace App\Filament\App\Resources\Qodes\Schemas;

use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Qode;
use App\QodeModules\ModuleRegistry;
use App\QodeModules\RedirectDestination;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class QodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('collection_id')
                    ->label('Collection')
                    ->options(fn () => Collection::query()->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Select::make('categories')
                    ->label('Categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Select::make('type')
                    ->options(fn (ModuleRegistry $registry): array => $registry->options())
                    ->required()
                    ->live()
                    ->default(QodeType::Redirect->value)
                    ->helperText('Switching type keeps prior module settings so you can reactivate Content later.'),
                Select::make('status')
                    ->options(collect(QodeStatus::cases())->mapWithKeys(
                        fn (QodeStatus $status) => [$status->value => ucfirst($status->value)]
                    ))
                    ->required()
                    ->default(QodeStatus::Active->value),
                Radio::make('settings.destination')
                    ->label('Redirect to')
                    ->options([
                        RedirectDestination::MODE_URL => 'External URL',
                        RedirectDestination::MODE_QODE => 'Another Qode',
                    ])
                    ->default(RedirectDestination::MODE_URL)
                    ->live()
                    ->required(fn (Get $get): bool => $get('type') === QodeType::Redirect->value)
                    ->visible(fn (Get $get): bool => $get('type') === QodeType::Redirect->value)
                    ->dehydrated(fn (Get $get): bool => $get('type') === QodeType::Redirect->value),
                TextInput::make('settings.url')
                    ->label('Destination URL')
                    ->url()
                    ->required(fn (Get $get): bool => $get('type') === QodeType::Redirect->value
                        && $get('settings.destination') === RedirectDestination::MODE_URL)
                    ->default('https://example.com')
                    ->visible(fn (Get $get): bool => $get('type') === QodeType::Redirect->value
                        && $get('settings.destination') === RedirectDestination::MODE_URL)
                    ->dehydrated(fn (Get $get): bool => $get('type') === QodeType::Redirect->value
                        && $get('settings.destination') === RedirectDestination::MODE_URL),
                Select::make('settings.target_qode_id')
                    ->label('Destination Qode')
                    ->searchable()
                    ->helperText('Only non-redirect Qodes. Redirect-to-redirect is blocked to prevent cascades and loops.')
                    ->getSearchResultsUsing(function (string $search, ?Qode $record, RedirectDestination $destination): array {
                        $tenantId = (int) ($record?->tenant_id ?? auth()->user()?->tenant_id ?? 0);

                        return $destination->searchableOptions(
                            $tenantId,
                            $record?->id,
                            $search,
                        );
                    })
                    ->getOptionLabelUsing(function (mixed $value, ?Qode $record, RedirectDestination $destination): ?string {
                        $tenantId = (int) ($record?->tenant_id ?? auth()->user()?->tenant_id ?? 0);

                        return $destination->optionLabel($tenantId, $record?->id, $value);
                    })
                    ->required(fn (Get $get): bool => $get('type') === QodeType::Redirect->value
                        && $get('settings.destination') === RedirectDestination::MODE_QODE)
                    ->visible(fn (Get $get): bool => $get('type') === QodeType::Redirect->value
                        && $get('settings.destination') === RedirectDestination::MODE_QODE)
                    ->dehydrated(fn (Get $get): bool => $get('type') === QodeType::Redirect->value
                        && $get('settings.destination') === RedirectDestination::MODE_QODE),
                TextInput::make('slug')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Assigned automatically via Sqids after create.')
                    ->visibleOn('edit'),
            ]);
    }
}
