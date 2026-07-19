<?php

namespace App\QodeModules\Contracts;

use App\Enums\QodeType;
use App\Models\Qode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface QodeModule
{
    public function type(): QodeType;

    public function label(): string;

    /**
     * @return array<string, mixed>
     */
    public function defaultSettings(): array;

    public function render(Qode $qode, Request $request): Response;
}
