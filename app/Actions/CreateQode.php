<?php

namespace App\Actions;

use App\Billing\EffectiveEntitlements;
use App\Enums\DomainStatus;
use App\Enums\DomainType;
use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Qode;
use App\Models\Tenant;
use App\QodeModules\ModuleRegistry;
use App\QodeModules\RedirectDestination;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateQode
{
    public function __construct(
        private ModuleRegistry $modules,
        private RedirectDestination $redirect,
        private EffectiveEntitlements $entitlements,
    ) {}

    /**
     * @param  array{name: string, collection_id: int, type?: string, status?: string, settings?: array<string, mixed>|null, domain_id?: int|null, slug?: string|null}  $data
     */
    public function handle(Tenant $tenant, array $data): Qode
    {
        $type = QodeType::from($data['type'] ?? $this->modules->defaultType()->value);
        $module = $this->modules->get($type);

        $collection = Collection::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereKey($data['collection_id'])
            ->firstOrFail();

        $domain = $this->resolveDomain($tenant, $data['domain_id'] ?? null);
        $slug = $this->resolveSlug($tenant, $domain, $data['slug'] ?? null);

        return Qode::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'collection_id' => $collection->id,
            'domain_id' => $domain->id,
            'name' => $data['name'],
            'slug' => $slug,
            'type' => $type,
            'status' => QodeStatus::from($data['status'] ?? QodeStatus::Active->value),
            'settings' => array_replace_recursive(
                $this->redirect->defaults(),
                $module->defaultSettings(),
                $data['settings'] ?? [],
            ),
        ]);
    }

    private function resolveDomain(Tenant $tenant, mixed $domainId): Domain
    {
        if ($domainId === null || $domainId === '') {
            $domain = Domain::defaultPlatform();

            if ($domain === null) {
                throw ValidationException::withMessages([
                    'domain_id' => 'No default platform domain is configured.',
                ]);
            }

            return $domain;
        }

        $domain = Domain::query()->whereKey($domainId)->firstOrFail();
        $features = $this->safeFeatures($tenant);

        if ($domain->type === DomainType::Platform) {
            if (! ($features['platform_domain_choice'] ?? false) && ! $domain->is_default) {
                throw ValidationException::withMessages([
                    'domain_id' => 'Platform domain choice is not enabled for this plan.',
                ]);
            }

            return $domain;
        }

        if (! ($features['custom_domains'] ?? false)) {
            throw ValidationException::withMessages([
                'domain_id' => 'Custom domains are not enabled for this plan.',
            ]);
        }

        if (
            $domain->tenant_id !== $tenant->id
            || ! in_array($domain->status, [DomainStatus::Verified, DomainStatus::Active], true)
        ) {
            throw ValidationException::withMessages([
                'domain_id' => 'Select a verified custom domain for this tenant.',
            ]);
        }

        return $domain;
    }

    private function resolveSlug(Tenant $tenant, Domain $domain, mixed $slug): ?string
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $slug = strtolower(trim((string) $slug));
        $features = $this->safeFeatures($tenant);

        if (! ($features['custom_slugs'] ?? false)) {
            throw ValidationException::withMessages([
                'slug' => 'Custom / vanity slugs are not enabled for this plan.',
            ]);
        }

        $validator = Validator::make(
            ['slug' => $slug],
            [
                'slug' => [
                    'required',
                    'string',
                    'min:3',
                    'max:64',
                    'regex:/^[a-z0-9]+$/',
                    Rule::unique('qodes', 'slug')->where(fn ($query) => $query->where('domain_id', $domain->id)),
                ],
            ],
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $slug;
    }

    /**
     * @return array<string, bool>
     */
    private function safeFeatures(Tenant $tenant): array
    {
        try {
            return $this->entitlements->for($tenant)['features'];
        } catch (ModelNotFoundException) {
            return [
                'custom_domains' => false,
                'custom_slugs' => false,
                'platform_domain_choice' => false,
            ];
        }
    }
}
