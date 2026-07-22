<?php

namespace App\Filament\App\Pages;

use App\Actions\ClaimCustomDomain;
use App\Actions\VerifyCustomDomain;
use App\Enums\DomainType;
use App\Models\Domain;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class TenantSettings extends Page
{
    protected string $view = 'filament.app.pages.tenant-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected static ?int $navigationSort = 100;

    public ?string $ga4_measurement_id = null;

    public ?string $meta_pixel_id = null;

    public ?string $hostname = null;

    /**
     * @var Collection<int, Domain>
     */
    public Collection $customDomains;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $tenant = $user->tenant()->firstOrFail();
        $settings = $tenant->analytics_settings ?? [];

        $this->ga4_measurement_id = $settings['ga4_measurement_id'] ?? null;
        $this->meta_pixel_id = $settings['meta_pixel_id'] ?? null;
        $this->refreshDomains();
    }

    public function saveAnalytics(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $tenant = $user->tenant()->firstOrFail();

        $tenant->forceFill([
            'analytics_settings' => [
                'ga4_measurement_id' => filled($this->ga4_measurement_id) ? trim($this->ga4_measurement_id) : null,
                'meta_pixel_id' => filled($this->meta_pixel_id) ? trim($this->meta_pixel_id) : null,
            ],
        ])->save();

        Notification::make()->title('Analytics settings saved')->success()->send();
    }

    public function claimDomain(ClaimCustomDomain $action): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $action->handle($user->tenant, (string) $this->hostname);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Could not claim domain')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Invalid hostname.')
                ->danger()
                ->send();

            return;
        }

        $this->hostname = null;
        $this->refreshDomains();

        Notification::make()->title('Domain claimed — add the TXT record, then verify')->success()->send();
    }

    public function verifyDomain(int $domainId, VerifyCustomDomain $action): void
    {
        /** @var User $user */
        $user = auth()->user();
        $domain = Domain::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('type', DomainType::Custom)
            ->whereKey($domainId)
            ->firstOrFail();

        try {
            $action->handle($user->tenant, $domain);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Verification failed')
                ->body(collect($exception->errors())->flatten()->first() ?? 'TXT record missing.')
                ->danger()
                ->send();

            return;
        }

        $this->refreshDomains();

        Notification::make()->title('Domain verified')->success()->send();
    }

    private function refreshDomains(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->customDomains = Domain::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('type', DomainType::Custom)
            ->orderByDesc('id')
            ->get();
    }
}
