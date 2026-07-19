<?php

namespace App\Filament\App\Resources\Files\Pages;

use App\Filament\App\Resources\Files\FileResource;
use Filament\Resources\Pages\ManageRecords;

class ManageFiles extends ManageRecords
{
    protected static string $resource = FileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FileResource::createUploadAction(),
        ];
    }
}
