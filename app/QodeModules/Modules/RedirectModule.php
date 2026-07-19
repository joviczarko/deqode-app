<?php

namespace App\QodeModules\Modules;

use App\Enums\QodeType;
use App\Models\Qode;
use App\QodeModules\Contracts\QodeModule;
use App\QodeModules\RedirectDestination;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectModule implements QodeModule
{
    public function __construct(
        private RedirectDestination $destination,
    ) {}

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
        return $this->destination->defaults();
    }

    public function render(Qode $qode, Request $request): Response
    {
        return redirect()->away($this->destination->urlFor($qode), 302);
    }
}
