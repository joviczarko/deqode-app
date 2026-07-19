<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Models\Invoice;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Invoices';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable(),
                TextColumn::make('package.name')->label('Package'),
                TextColumn::make('status')->badge(),
                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state, Invoice $record): string => number_format($state / 100, 2).' '.$record->currency),
                TextColumn::make('gateway'),
                TextColumn::make('paid_at')->dateTime()->placeholder('—'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('id', 'desc');
    }
}
