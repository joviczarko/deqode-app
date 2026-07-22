<?php

namespace App\Actions;

use App\Enums\DomainStatus;
use App\Enums\DomainType;
use App\Models\Domain;
use App\Models\Tenant;
use App\Support\DnsTxtLookup;
use Illuminate\Validation\ValidationException;

class VerifyCustomDomain
{
    public function __construct(
        private DnsTxtLookup $dns,
    ) {}

    public function handle(Tenant $tenant, Domain $domain): Domain
    {
        if ($domain->tenant_id !== $tenant->id || $domain->type !== DomainType::Custom) {
            throw ValidationException::withMessages([
                'hostname' => 'Domain not found for this tenant.',
            ]);
        }

        if ($domain->status === DomainStatus::Verified || $domain->status === DomainStatus::Active) {
            return $domain;
        }

        $expected = 'deqode-verify='.$domain->verification_token;
        $texts = $this->dns->texts($domain->hostname);

        foreach ($texts as $text) {
            if (trim($text) === $expected || str_contains($text, $expected)) {
                $domain->forceFill([
                    'status' => DomainStatus::Verified,
                    'verified_at' => now(),
                ])->save();

                return $domain->refresh();
            }
        }

        throw ValidationException::withMessages([
            'hostname' => 'TXT record not found. Add '.$expected.' to '.$domain->hostname.'.',
        ]);
    }
}
