<?php

namespace App\Filament\App\Resources\Leads\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('qode.name')->label('Qode'),
                TextEntry::make('created_at')->dateTime()->label('Submitted'),
                TextEntry::make('payload')
                    ->label('Payload')
                    ->formatStateUsing(fn (mixed $state): string => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}'
                        : '')
                    ->columnSpanFull(),
            ]);
    }
}
