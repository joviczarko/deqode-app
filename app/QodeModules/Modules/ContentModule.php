<?php

namespace App\QodeModules\Modules;

use App\Enums\QodeType;
use App\Models\Qode;
use App\QodeModules\Contracts\QodeModule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
            'title' => 'Untitled',
            'body' => '',
        ];
    }

    public function render(Qode $qode, Request $request): Response
    {
        $title = (string) ($qode->settings['title'] ?? $qode->name);

        // Chunk 1a stub — full Pico wrapper arrives in 1c / 2b.
        return response(
            "Content Qode stub: {$title}",
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }
}
