<?php

namespace App\Filament\App\Resources\Qodes;

use App\Filament\App\Resources\Qodes\Pages\CreateQode;
use App\Filament\App\Resources\Qodes\Pages\EditQode;
use App\Filament\App\Resources\Qodes\Pages\ListQodes;
use App\Filament\App\Resources\Qodes\Schemas\QodeForm;
use App\Filament\App\Resources\Qodes\Tables\QodesTable;
use App\Models\Qode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QodeResource extends Resource
{
    protected static ?string $model = Qode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Qodes';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return QodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQodes::route('/'),
            'create' => CreateQode::route('/create'),
            'edit' => EditQode::route('/{record}/edit'),
        ];
    }
}
