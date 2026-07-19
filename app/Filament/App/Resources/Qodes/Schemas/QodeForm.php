<?php

namespace App\Filament\App\Resources\Qodes\Schemas;

use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\QodeModules\ModuleRegistry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                Select::make('type')
                    ->options(fn (ModuleRegistry $registry): array => $registry->options())
                    ->required()
                    ->live()
                    ->default(QodeType::Redirect->value)
                    ->afterStateUpdated(function (?string $state, ?string $old, Set $set, Get $get): void {
                        if ($old === null || $state === $old || ! $get('id')) {
                            return;
                        }

                        Notification::make()
                            ->title('Type change will wipe module settings')
                            ->body('Saving a different type replaces settings for the new module defaults.')
                            ->warning()
                            ->send();
                    }),
                Select::make('status')
                    ->options(collect(QodeStatus::cases())->mapWithKeys(
                        fn (QodeStatus $status) => [$status->value => ucfirst($status->value)]
                    ))
                    ->required()
                    ->default(QodeStatus::Active->value),
                TextInput::make('slug')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Assigned automatically via Sqids after create.')
                    ->visibleOn('edit'),
            ]);
    }
}
