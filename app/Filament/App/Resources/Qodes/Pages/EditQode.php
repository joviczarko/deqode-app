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
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EditQode extends EditRecord
{
    protected static string $resource = QodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function headerUrl(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('header_public_url')
                    ->hiddenLabel()
                    ->default(fn (): string => app(QodeUrlBuilder::class)->forQode($this->getRecord()))
                    ->disabled()
                    ->dehydrated(false)
                    ->copyable(copyMessage: 'URL copied')
                    ->suffixAction(
                        Action::make('openHeaderPublicUrl')
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->url(fn (): string => app(QodeUrlBuilder::class)->forQode($this->getRecord()))
                            ->openUrlInNewTab(),
                    )
                    ->extraAttributes([
                        'style' => 'min-width: 18rem; max-width: 28rem;',
                    ]),
            ]);
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
