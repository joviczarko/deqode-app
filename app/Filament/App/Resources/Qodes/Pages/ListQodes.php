<?php

namespace App\Filament\App\Resources\Qodes\Pages;

use App\Filament\App\Resources\Qodes\QodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQodes extends ListRecords
{
    protected static string $resource = QodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
