<?php

namespace App\QodeModules\Contracts;

use App\Enums\QodeType;
use App\Models\Qode;
use Filament\Schemas\Components\Component;
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

    /**
     * Main canvas fields rendered below the Qode name on create/edit.
     *
     * @return array<Component>
     */
    public function editFormComponents(): array;

    /**
     * Optional sidebar fields rendered after Organize on create/edit.
     *
     * @return array<Component>
     */
    public function editSidebarComponents(): array;

    public function render(Qode $qode, Request $request): Response;
}
