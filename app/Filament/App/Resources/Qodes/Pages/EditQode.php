<?php

namespace App\Filament\App\Resources\Qodes\Pages;

use App\Enums\QodeType;
use App\Filament\App\Resources\Qodes\QodeResource;
use App\Models\Qode;
use App\QodeModules\ModuleRegistry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQode extends EditRecord
{
    protected static string $resource = QodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('qr')
                ->label('Download QR')
                ->url(fn (): string => route('qodes.qr', $this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Qode $record */
        $record = $this->getRecord();
        $newType = QodeType::from($data['type']);

        if ($record->type !== $newType) {
            $data['settings'] = app(ModuleRegistry::class)->get($newType)->defaultSettings();
        }

        return $data;
    }
}
