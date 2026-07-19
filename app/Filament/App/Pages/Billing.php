<?php

namespace App\Filament\App\Pages;

use App\Actions\StartCheckout;
use App\Billing\EffectiveEntitlements;
use App\Billing\PackageCatalog;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class Billing extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.app.pages.billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 90;

    /**
     * @var array{package: Package, price_monthly_cents: int, quotas: array<string, mixed>, features: array<string, mixed>}|null
     */
    public ?array $entitlements = null;

    /**
     * @var Collection<int, Package>
     */
    public Collection $packages;

    public function mount(EffectiveEntitlements $entitlements): void
    {
        /** @var User $user */
        $user = auth()->user();
        $tenant = $user->tenant()->with(['currentSubscription.package', 'featureOverride'])->firstOrFail();

        $this->entitlements = $entitlements->for($tenant);
        $this->packages = PackageCatalog::forCheckout();
    }

    public function checkout(int $packageId, StartCheckout $action): void
    {
        /** @var User $user */
        $user = auth()->user();
        $package = Package::query()->whereKey($packageId)->where('is_active', true)->firstOrFail();

        try {
            $redirect = $action->handle($user->tenant, $package, $user);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Checkout failed')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Unable to start checkout.')
                ->danger()
                ->send();

            return;
        }

        $this->redirect($redirect->redirectUrl);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->where('tenant_id', auth()->user()?->tenant_id)
                    ->latest('id')
            )
            ->columns([
                TextColumn::make('number')->searchable(),
                TextColumn::make('package.name')->label('Package'),
                TextColumn::make('status')->badge(),
                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state, Invoice $record): string => number_format($state / 100, 2).' '.$record->currency),
                TextColumn::make('paid_at')->dateTime()->placeholder('—'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->paginated([10]);
    }

    public function getTitle(): string
    {
        return 'Billing';
    }

    public function currentPackageName(): string
    {
        return $this->entitlements['package']->name ?? 'None';
    }

    public function currentPriceLabel(): string
    {
        $cents = $this->entitlements['price_monthly_cents'] ?? 0;

        return number_format($cents / 100, 2).' USD / month';
    }

    public function currentMaxQodes(): mixed
    {
        return $this->entitlements['quotas']['max_qodes'] ?? '—';
    }

    public function isOnTrial(): bool
    {
        /** @var User $user */
        $user = auth()->user();
        $subscription = $user->tenant?->currentSubscription;

        return $subscription?->status?->value === 'trial';
    }

    public function packagePriceLabel(Package $package): string
    {
        /** @var User $user */
        $user = auth()->user();
        $override = $user->tenant?->featureOverride;
        $cents = app(EffectiveEntitlements::class)->priceMonthlyCents($package, $override);

        return number_format($cents / 100, 2).' USD / month';
    }
}
