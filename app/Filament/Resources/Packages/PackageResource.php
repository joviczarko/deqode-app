<?php

namespace App\Filament\Resources\Packages;

use App\Enums\PackageStatus;
use App\Filament\Resources\Packages\Pages\ManagePackages;
use App\Filament\Support\PackageFormComponents;
use App\Models\Package;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required(),
                PackageFormComponents::statusSelect(),
                TextInput::make('trial_days')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->visible(fn ($get): bool => $get('status') === PackageStatus::Trial->value),
                TextInput::make('price_monthly_cents')
                    ->label('Price (cents / month)')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->disabled(fn ($get): bool => $get('status') === PackageStatus::Trial->value)
                    ->dehydrated(),
                ...PackageFormComponents::entitlementSections(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('is_free')->boolean()->label('Free'),
                TextColumn::make('trial_days')->numeric()->sortable(),
                TextColumn::make('price_monthly_cents')
                    ->label('Price (cents)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quotas.max_qodes')->label('Max Qodes'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePackages::route('/'),
        ];
    }
}
