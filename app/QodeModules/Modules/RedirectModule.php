<?php

namespace App\QodeModules\Modules;

use App\Enums\QodeType;
use App\Models\Qode;
use App\QodeModules\Contracts\QodeModule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectModule implements QodeModule
{
    public function type(): QodeType
    {
        return QodeType::Redirect;
    }

    public function label(): string
    {
        return QodeType::Redirect->label();
    }

    public function defaultSettings(): array
    {
        return [
            'url' => 'https://example.com',
            'status_code' => 302,
        ];
    }

    public function render(Qode $qode, Request $request): Response
    {
        $url = (string) ($qode->settings['url'] ?? 'https://example.com');
        $status = (int) ($qode->settings['status_code'] ?? 302);

        // Chunk 1a stub: resolve works; full redirect behavior ships in 2a.
        return response(
            "Redirect Qode stub → {$url} ({$status})",
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }
}
