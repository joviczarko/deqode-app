<?php

namespace App\QodeModules\Modules;

use App\Enums\QodeType;
use App\Models\Qode;
use App\QodeModules\Contracts\QodeModule;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LinkHubModule implements QodeModule
{
    public function type(): QodeType
    {
        return QodeType::LinkHub;
    }

    public function label(): string
    {
        return QodeType::LinkHub->label();
    }

    public function defaultSettings(): array
    {
        return [
            'links' => [],
        ];
    }

    public function editFormComponents(): array
    {
        return [
            Repeater::make('settings.links')
                ->label('Links')
                ->schema([
                    TextInput::make('label')
                        ->label('Label')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('url')
                        ->label('URL')
                        ->url()
                        ->required()
                        ->maxLength(2048),
                ])
                ->defaultItems(0)
                ->addActionLabel('Add link')
                ->reorderable()
                ->columnSpanFull(),
        ];
    }

    public function editSidebarComponents(): array
    {
        return [];
    }

    public function render(Qode $qode, Request $request): Response
    {
        $qode->loadMissing('tenant');

        $title = (string) ($qode->name ?: 'Links');
        $links = $this->normalizedLinks($qode->settings['links'] ?? []);

        return response()->view('modules.link_hub', [
            'qode' => $qode,
            'title' => $title,
            'links' => $links,
        ]);
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function normalizedLinks(mixed $links): array
    {
        if (! is_array($links)) {
            return [];
        }

        $normalized = [];

        foreach ($links as $link) {
            if (! is_array($link)) {
                continue;
            }

            $label = trim((string) ($link['label'] ?? ''));
            $url = trim((string) ($link['url'] ?? ''));

            if ($label === '' || $url === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'url' => $url,
            ];
        }

        return $normalized;
    }
}
