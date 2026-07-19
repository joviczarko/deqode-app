<?php

namespace App\Filament\App\Resources\Files;

use App\Actions\StoreTenantFile;
use App\Filament\App\Resources\Files\Pages\ManageFiles;
use App\Models\File;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperClip;

    protected static ?string $recordTitleAttribute = 'original_name';

    protected static ?string $navigationLabel = 'Files';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('upload')
                    ->label('File')
                    ->required()
                    ->storeFiles(false)
                    ->maxSize(10240),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')->searchable()->sortable()->label('Name'),
                TextColumn::make('mime')->label('Type')->toggleable(),
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 1024, 1).' KB'),
                TextColumn::make('path')->toggleable(isToggledHiddenByDefault: true)->copyable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
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
            'index' => ManageFiles::route('/'),
        ];
    }

    public static function createUploadAction(): CreateAction
    {
        return CreateAction::make()
            ->createAnother(false)
            ->using(function (array $data): File {
                $upload = Arr::first(Arr::wrap($data['upload'] ?? null));

                if (! $upload instanceof TemporaryUploadedFile) {
                    throw new InvalidArgumentException('A file upload is required.');
                }

                /** @var Tenant $tenant */
                $tenant = auth()->user()->tenant;

                return app(StoreTenantFile::class)->handle($tenant, $upload);
            });
    }
}
