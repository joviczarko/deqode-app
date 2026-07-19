<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Actions\UpdateTenantOverrides;
use App\Billing\EffectiveEntitlements;
use App\Billing\PackageCatalog;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\PackageFormComponents;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editOverrides')
                ->label('Overrides')
                ->icon('heroicon-o-adjustments-horizontal')
                ->fillForm(function (): array {
                    /** @var Tenant $tenant */
                    $tenant = $this->getRecord();
                    $override = $tenant->featureOverride;

                    $featureOverrides = [];

                    foreach (PackageCatalog::featureKeys() as $key) {
                        if ($override?->feature_overrides === null || ! array_key_exists($key, $override->feature_overrides)) {
                            $featureOverrides[$key] = 'inherit';

                            continue;
                        }

                        $featureOverrides[$key] = $override->feature_overrides[$key] ? '1' : '0';
                    }

                    return [
                        'package_id' => $override?->package_id,
                        'price_monthly_cents' => $override?->price_monthly_cents,
                        'quota_overrides' => $override?->quota_overrides ?? [],
                        'feature_overrides' => $featureOverrides,
                        'notes' => $override?->notes,
                    ];
                })
                ->schema([
                    ...PackageFormComponents::overrideSections(),
                    Textarea::make('notes')->rows(2),
                ])
                ->action(function (array $data, UpdateTenantOverrides $action, EffectiveEntitlements $entitlements): void {
                    /** @var Tenant $tenant */
                    $tenant = $this->getRecord();

                    $featureOverrides = [];

                    foreach ($data['feature_overrides'] ?? [] as $key => $value) {
                        if ($value === 'inherit' || $value === null || $value === '') {
                            continue;
                        }

                        $featureOverrides[$key] = $value;
                    }

                    $action->handle($tenant, [
                        'package_id' => $data['package_id'] ?: null,
                        'price_monthly_cents' => $data['price_monthly_cents'] !== null && $data['price_monthly_cents'] !== ''
                            ? (int) $data['price_monthly_cents']
                            : null,
                        'quota_overrides' => PackageCatalog::filterQuotaOverrides($data['quota_overrides'] ?? null),
                        'feature_overrides' => PackageCatalog::filterFeatureOverrides($featureOverrides),
                        'notes' => $data['notes'] ?? null,
                    ]);

                    $tenant->refresh()->load(['featureOverride', 'currentSubscription.package']);
                    $effective = $entitlements->for($tenant);

                    Notification::make()
                        ->title('Overrides saved')
                        ->body('Effective max_qodes: '.($effective['quotas']['max_qodes'] ?? 'n/a').'; price: '.$effective['price_monthly_cents'].' cents')
                        ->success()
                        ->send();
                }),
            Action::make('impersonate')
                ->label('Impersonate')
                ->icon('heroicon-o-user')
                ->color('warning')
                ->requiresConfirmation()
                ->url(function (): ?string {
                    /** @var Tenant $tenant */
                    $tenant = $this->getRecord();
                    $user = $tenant->users()->where('is_super_admin', false)->first();

                    return $user ? route('admin.impersonate', $user) : null;
                })
                ->visible(function (): bool {
                    /** @var Tenant $tenant */
                    $tenant = $this->getRecord();

                    return $tenant->users()->where('is_super_admin', false)->exists();
                }),
            EditAction::make(),
        ];
    }
}
