<?php

namespace App\Filament\App\Resources\Leads\Tables;

use App\Models\Lead;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('qode.name')->label('Qode')->searchable()->sortable(),
                TextColumn::make('payload_summary')
                    ->label('Payload')
                    ->getStateUsing(function (Lead $record): string {
                        $payload = $record->payload;

                        if (! is_array($payload) || $payload === []) {
                            return '';
                        }

                        return collect($payload)
                            ->map(fn (mixed $value, string $key): string => $key.': '.(is_scalar($value) ? (string) $value : json_encode($value)))
                            ->implode(' · ');
                    })
                    ->wrap()
                    ->limit(80),
                TextColumn::make('created_at')->dateTime()->sortable()->label('Submitted'),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
