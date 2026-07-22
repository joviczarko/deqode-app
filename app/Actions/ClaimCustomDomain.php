<?php

namespace App\Actions;

use App\Billing\EffectiveEntitlements;
use App\Enums\DomainStatus;
use App\Enums\DomainType;
use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClaimCustomDomain
{
    public function __construct(
        private EffectiveEntitlements $entitlements,
    ) {}

    public function handle(Tenant $tenant, string $hostname): Domain
    {
        try {
            $features = $this->entitlements->for($tenant)['features'];
        } catch (ModelNotFoundException) {
            $features = ['custom_domains' => false];
        }

        if (! ($features['custom_domains'] ?? false)) {
            throw ValidationException::withMessages([
                'hostname' => 'Custom domains are not enabled for this plan.',
            ]);
        }

        $hostname = strtolower(trim($hostname));

        if ($hostname === '' || ! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $hostname)) {
            throw ValidationException::withMessages([
                'hostname' => 'Enter a valid hostname.',
            ]);
        }

        $existing = Domain::query()->where('hostname', $hostname)->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'hostname' => 'This hostname is already claimed.',
            ]);
        }

        return Domain::query()->create([
            'hostname' => $hostname,
            'type' => DomainType::Custom,
            'tenant_id' => $tenant->id,
            'status' => DomainStatus::Pending,
            'is_default' => false,
            'verification_token' => Str::lower(Str::random(32)),
        ]);
    }
}
