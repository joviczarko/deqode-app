<?php

namespace App\Filament\App\Resources\Qodes\Pages;

use App\Enums\QodeType;
use App\Filament\App\Resources\Qodes\QodeResource;
use App\Models\Domain;
use App\QodeModules\ModuleRegistry;
use App\QodeModules\RedirectDestination;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateQode extends CreateRecord
{
    protected static string $resource = QodeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $type = QodeType::from($data['type'] ?? QodeType::Content->value);
        $redirect = app(RedirectDestination::class);
        $defaults = array_replace_recursive(
            $redirect->defaults(),
            app(ModuleRegistry::class)->get($type)->defaultSettings(),
        );

        if (blank($data['domain_id'] ?? null)) {
            $domain = Domain::defaultPlatform();

            if ($domain === null) {
                throw ValidationException::withMessages([
                    'name' => 'No default platform domain is seeded.',
                ]);
            }

            $data['domain_id'] = $domain->id;
        }

        $data['tenant_id'] = auth()->user()?->tenant_id;
        $data['settings'] = array_replace_recursive($defaults, $data['settings'] ?? []);
        $data['settings']['redirect'] = $redirect->validateForSave(null, $data['settings']['redirect'] ?? []);

        if (blank($data['slug'] ?? null)) {
            unset($data['slug']);
        }

        return $data;
    }
}
