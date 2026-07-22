<?php

namespace App\QodeModules\Modules;

use App\Enums\QodeType;
use App\Models\Qode;
use App\QodeModules\Contracts\QodeModule;
use Filament\Forms\Components\RichEditor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tiptap\Editor;

class ContentModule implements QodeModule
{
    public function type(): QodeType
    {
        return QodeType::Content;
    }

    public function label(): string
    {
        return QodeType::Content->label();
    }

    public function defaultSettings(): array
    {
        return [
            'body' => '',
        ];
    }

    public function editFormComponents(): array
    {
        return [
            RichEditor::make('settings.body')
                ->label('Body')
                // settings is a JSON cast; without this Filament stores TipTap JSON, not HTML.
                ->json(false)
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'strike', 'link'],
                    ['h2', 'h3'],
                    ['bulletList', 'orderedList'],
                    ['undo', 'redo'],
                ])
                ->fileAttachments(false)
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

        $title = (string) ($qode->name ?: ($qode->settings['title'] ?? 'Untitled'));
        $body = $this->bodyToHtml($qode->settings['body'] ?? '');

        return response()->view('modules.content', [
            'qode' => $qode,
            'title' => $title,
            'body' => $body,
        ]);
    }

    /**
     * Filament may have stored TipTap JSON while settings is a JSON cast.
     * Newer saves use HTML via RichEditor::json(false).
     */
    private function bodyToHtml(mixed $body): string
    {
        if (is_string($body)) {
            return $body;
        }

        if (! is_array($body) || $body === []) {
            return '';
        }

        return (new Editor)->setContent($body)->getHtml();
    }
}
