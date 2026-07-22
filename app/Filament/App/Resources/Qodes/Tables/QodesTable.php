<?php

namespace App\Filament\App\Resources\Qodes\Tables;

use App\Support\QodeUrlBuilder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->copyable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('collection.name')->label('Collection'),
                TextColumn::make('visits_count')
                    ->counts('visits')
                    ->label('Scans')
                    ->sortable(),
                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->separator(','),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('collection_id')
                    ->label('Collection')
                    ->relationship('collection', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->url(fn ($record): string => app(QodeUrlBuilder::class)->forQode($record))
                    ->openUrlInNewTab(),
                Action::make('qr')
                    ->label('QR')
                    ->url(fn ($record): string => route('qodes.qr', ['qode' => $record, 'format' => 'svg']))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
