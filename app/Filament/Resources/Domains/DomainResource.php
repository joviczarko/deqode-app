<?php

namespace App\Filament\Resources\Domains;

use App\Enums\DomainStatus;
use App\Enums\DomainType;
use App\Filament\Resources\Domains\Pages\ManageDomains;
use App\Models\Domain;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Domains';

    protected static ?string $recordTitleAttribute = 'hostname';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('hostname')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('type')
                    ->options(collect(DomainType::cases())->mapWithKeys(
                        fn (DomainType $type) => [$type->value => ucfirst($type->value)]
                    ))
                    ->required()
                    ->native(false)
                    ->default(DomainType::Platform->value),
                Select::make('status')
                    ->options(collect(DomainStatus::cases())->mapWithKeys(
                        fn (DomainStatus $status) => [$status->value => ucfirst($status->value)]
                    ))
                    ->required()
                    ->native(false)
                    ->default(DomainStatus::Active->value),
                Toggle::make('is_default')
                    ->label('Default platform domain'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hostname')->searchable()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                IconColumn::make('is_default')->boolean()->label('Default'),
                TextColumn::make('tenant_id')->label('Tenant')->toggleable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageDomains::route('/'),
        ];
    }
}
