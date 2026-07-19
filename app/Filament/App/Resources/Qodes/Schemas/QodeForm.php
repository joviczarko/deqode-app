<?php

namespace App\Filament\App\Resources\Qodes\Schemas;

use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Qode;
use App\QodeModules\ModuleRegistry;
use App\QodeModules\RedirectDestination;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
                    ->label('Module')
                    ->options(fn (ModuleRegistry $registry): array => $registry->options())
                    ->required()
                    ->live()
                    ->default(QodeType::Content->value)
                    ->helperText('Module content stays available even while a redirect is active.'),
                Select::make('status')
                    ->options(collect(QodeStatus::cases())->mapWithKeys(
                        fn (QodeStatus $status) => [$status->value => ucfirst($status->value)]
                    ))
                    ->required()
                    ->default(QodeStatus::Active->value),
                Section::make('Redirect')
                    ->description('Optional campaign override. Scans use a bare 302 and skip the module page.')
                    ->schema([
                        Select::make('settings.redirect.to')
                            ->label('Redirect to')
                            ->options(fn (RedirectDestination $redirect): array => $redirect->modeOptions())
                            ->required()
                            ->live()
                            ->native(false)
                            ->default(RedirectDestination::MODE_NONE),
                        TextInput::make('settings.redirect.url')
                            ->label('Destination URL')
                            ->url()
                            ->required(fn (Get $get): bool => $get('settings.redirect.to') === RedirectDestination::MODE_URL)
                            ->default('https://example.com')
                            ->visible(fn (Get $get): bool => $get('settings.redirect.to') === RedirectDestination::MODE_URL)
                            ->dehydrated(fn (Get $get): bool => $get('settings.redirect.to') === RedirectDestination::MODE_URL),
                        Select::make('settings.redirect.target_qode_id')
                            ->label('Destination Qode')
                            ->searchable()
                            ->helperText('Only Qodes that are not themselves redirecting.')
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
                            ->required(fn (Get $get): bool => $get('settings.redirect.to') === RedirectDestination::MODE_QODE)
                            ->visible(fn (Get $get): bool => $get('settings.redirect.to') === RedirectDestination::MODE_QODE)
                            ->dehydrated(fn (Get $get): bool => $get('settings.redirect.to') === RedirectDestination::MODE_QODE),
                    ])
                    ->columns(1),
                TextInput::make('slug')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Assigned automatically via Sqids after create.')
                    ->visibleOn('edit'),
            ]);
    }
}
