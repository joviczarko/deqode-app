<?php

namespace App\Filament\App\Resources\Qodes\Pages;

use App\Enums\QodeType;
use App\Filament\App\Resources\Qodes\QodeResource;
use App\Models\Qode;
use App\QodeModules\ModuleRegistry;
use App\QodeModules\RedirectDestination;
use App\Support\QodeUrlBuilder;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;

class EditQode extends EditRecord
{
    protected static string $resource = QodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copyLink')
                ->label('Copy link')
                ->icon(Heroicon::OutlinedClipboardDocument)
                ->color('gray')
                ->alpineClickHandler(function (): string {
                    $url = Js::from(app(QodeUrlBuilder::class)->forQode($this->getRecord()));

                    return <<<JS
                        window.navigator.clipboard.writeText({$url})
                        \$tooltip('Copied', {
                            theme: \$store.theme,
                            timeout: 1500,
                        })
                        JS;
                }),
            Action::make('open')
                ->label('Open')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => app(QodeUrlBuilder::class)->forQode($this->getRecord()))
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
        $redirect = app(RedirectDestination::class);
        $defaults = array_replace_recursive(
            $redirect->defaults(),
            app(ModuleRegistry::class)->get($newType)->defaultSettings(),
        );
        $incoming = is_array($data['settings'] ?? null) ? $data['settings'] : [];

        $data['settings'] = array_replace_recursive($defaults, $record->settings ?? [], $incoming);
        $data['settings']['redirect'] = $redirect->validateForSave($record, $data['settings']['redirect'] ?? []);

        return $data;
    }
}
