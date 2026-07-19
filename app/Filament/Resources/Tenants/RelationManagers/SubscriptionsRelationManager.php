<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'Subscription';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('package.name')->label('Package'),
                TextColumn::make('status')->badge(),
                TextColumn::make('trial_ends_at')->dateTime()->placeholder('—'),
                TextColumn::make('starts_at')->dateTime()->placeholder('—'),
                TextColumn::make('ends_at')->dateTime()->placeholder('—'),
                TextColumn::make('updated_at')->dateTime(),
            ])
            ->defaultSort('id', 'desc')
            ->paginated(false);
    }
}
