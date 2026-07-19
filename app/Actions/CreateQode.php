<?php

namespace App\Actions;

use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Qode;
use App\Models\Tenant;
use App\QodeModules\ModuleRegistry;
use Illuminate\Validation\ValidationException;

class CreateQode
{
    public function __construct(
        private ModuleRegistry $modules,
    ) {}

    /**
     * @param  array{name: string, collection_id: int, type?: string, status?: string, settings?: array<string, mixed>|null, domain_id?: int|null}  $data
     */
    public function handle(Tenant $tenant, array $data): Qode
    {
        $type = QodeType::from($data['type'] ?? $this->modules->defaultType()->value);
        $module = $this->modules->get($type);

        $collection = Collection::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereKey($data['collection_id'])
            ->firstOrFail();

        $domain = isset($data['domain_id'])
            ? Domain::query()->whereKey($data['domain_id'])->firstOrFail()
            : Domain::defaultPlatform();

        if ($domain === null) {
            throw ValidationException::withMessages([
                'domain_id' => 'No default platform domain is configured.',
            ]);
        }

        return Qode::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'collection_id' => $collection->id,
            'domain_id' => $domain->id,
            'name' => $data['name'],
            'type' => $type,
            'status' => QodeStatus::from($data['status'] ?? QodeStatus::Active->value),
            'settings' => $data['settings'] ?? $module->defaultSettings(),
        ]);
    }
}
