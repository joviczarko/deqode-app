<?php

namespace App\Filament\App\Resources\Qodes\Schemas;

use App\Billing\EffectiveEntitlements;
use App\Enums\DomainStatus;
use App\Enums\DomainType;
use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Domain;
use App\Models\Qode;
use App\QodeModules\ModuleRegistry;
use App\QodeModules\RedirectDestination;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Component as LivewireComponent;

class QodeForm
{
    public static function configure(Schema $schema): Schema
    {
        // Mark custom columns so Edit/CreateRecord do not force the default 2-col grid
        // (that squeezed the whole CMS layout into half the page).
        return $schema
            ->columns([
                'default' => 1,
                'lg' => 3,
            ])
            ->components([
                Group::make(fn (Get $get): array => [
                    TextInput::make('name')
                        ->label('Qode name')
                        ->required()
                        ->maxLength(255)
                        ->default('Untitled')
                        ->extraInputAttributes([
                            'style' => 'font-size: 1.5rem; font-weight: 600; line-height: 1.3;',
                        ])
                        ->columnSpanFull(),
                    ...self::moduleFormComponents($get),
                ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Group::make(fn (Get $get): array => [
                    Section::make('Publish')
                        ->schema([
                            Select::make('status')
                                ->options(collect(QodeStatus::cases())->mapWithKeys(
                                    fn (QodeStatus $status) => [$status->value => ucfirst($status->value)]
                                ))
                                ->required()
                                ->native(false)
                                ->default(QodeStatus::Active->value),
                            Select::make('type')
                                ->label('Module')
                                ->options(fn (ModuleRegistry $registry): array => $registry->options())
                                ->required()
                                ->live()
                                ->native(false)
                                ->default(QodeType::Content->value)
                                ->helperText('Module content stays available while a redirect is on.'),
                            Select::make('domain_id')
                                ->label('Domain')
                                ->options(fn (): array => self::domainOptions())
                                ->searchable()
                                ->native(false)
                                ->visible(fn (): bool => self::canChooseDomain())
                                ->required(fn (): bool => self::canChooseDomain()),
                            TextInput::make('slug')
                                ->label('Vanity slug')
                                ->helperText('Letters and digits only. Leave blank for auto Sqids.')
                                ->regex('/^[a-z0-9]+$/')
                                ->minLength(3)
                                ->maxLength(64)
                                ->visible(fn (): bool => self::featureEnabled('custom_slugs'))
                                ->dehydrated(fn (): bool => self::featureEnabled('custom_slugs')),
                            Select::make('settings.redirect.to')
                                ->label('Redirect to')
                                ->options(fn (RedirectDestination $redirect): array => $redirect->modeOptions())
                                ->required()
                                ->live()
                                ->native(false)
                                ->default(RedirectDestination::MODE_NONE)
                                ->helperText('Campaign override. Scans use a bare 302 and skip the module page.'),
                            TextInput::make('settings.redirect.url')
                                ->label('Destination URL')
                                ->url()
                                ->required(fn (Get $get): bool => self::redirectMode($get) === RedirectDestination::MODE_URL)
                                ->default('https://example.com')
                                ->visible(fn (Get $get): bool => self::redirectMode($get) === RedirectDestination::MODE_URL)
                                ->dehydrated(fn (Get $get): bool => self::redirectMode($get) === RedirectDestination::MODE_URL),
                            Select::make('settings.redirect.target_qode_id')
                                ->label('Destination Qode')
                                ->searchable()
                                ->helperText('Only Qodes that are not themselves redirecting.')
                                ->getSearchResultsUsing(function (string $search, ?Qode $record, RedirectDestination $destination): array {
                                    $tenantId = (int) ($record?->tenant_id ?? auth()->user()?->tenant_id ?? 0);

                                    return $destination->searchableOptions(
                                        $tenantId,
                                        $record?->id,
                                        $search,
                                    );
                                })
                                ->getOptionLabelUsing(function (mixed $value, ?Qode $record, RedirectDestination $destination): ?string {
                                    $tenantId = (int) ($record?->tenant_id ?? auth()->user()?->tenant_id ?? 0);

                                    return $destination->optionLabel($tenantId, $record?->id, $value);
                                })
                                ->required(fn (Get $get): bool => self::redirectMode($get) === RedirectDestination::MODE_QODE)
                                ->visible(fn (Get $get): bool => self::redirectMode($get) === RedirectDestination::MODE_QODE)
                                ->dehydrated(fn (Get $get): bool => self::redirectMode($get) === RedirectDestination::MODE_QODE),
                        ]),
                    Section::make('Organize')
                        ->schema([
                            Select::make('collection_id')
                                ->label('Collection')
                                ->options(fn () => Collection::query()->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                            Select::make('categories')
                                ->label('Categories')
                                ->relationship('categories', 'name')
                                ->multiple()
                                ->preload()
                                ->searchable(),
                        ]),
                    ...self::moduleSidebarSections($get),
                    Section::make('QR code')
                        ->description(fn (?Qode $record): ?string => filled($record?->slug)
                            ? 'Code: '.$record->slug
                            : null)
                        ->schema([
                            View::make('filament.app.qodes.qr-preview')
                                ->viewData(function (LivewireComponent $livewire): array {
                                    $record = method_exists($livewire, 'getRecord')
                                        ? $livewire->getRecord()
                                        : null;

                                    return [
                                        'qrUrl' => self::qrUrl(
                                            $record instanceof Qode ? $record : null,
                                            'svg',
                                        ),
                                    ];
                                }),
                            Actions::make([
                                ActionGroup::make([
                                    Action::make('downloadQrSvg')
                                        ->label('SVG')
                                        ->url(fn (?Qode $record): ?string => self::qrUrl($record, 'svg'))
                                        ->openUrlInNewTab(),
                                    Action::make('downloadQrPng')
                                        ->label('PNG')
                                        ->url(fn (?Qode $record): ?string => self::qrUrl($record, 'png'))
                                        ->openUrlInNewTab(),
                                ])
                                    ->label('Download')
                                    ->icon(Heroicon::OutlinedArrowDownTray)
                                    ->button()
                                    ->color('gray'),
                            ])->alignment(Alignment::Center),
                        ])
                        ->visibleOn('edit'),
                ])->columnSpan([
                    'default' => 1,
                    'lg' => 1,
                ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function domainOptions(): array
    {
        $tenantId = auth()->user()?->tenant_id;
        $options = [];

        if (self::featureEnabled('platform_domain_choice')) {
            $options += Domain::query()
                ->where('type', DomainType::Platform)
                ->where('status', DomainStatus::Active)
                ->orderByDesc('is_default')
                ->pluck('hostname', 'id')
                ->all();
        } else {
            $default = Domain::defaultPlatform();

            if ($default !== null) {
                $options[$default->id] = $default->hostname;
            }
        }

        if (self::featureEnabled('custom_domains') && $tenantId !== null) {
            $options += Domain::query()
                ->where('tenant_id', $tenantId)
                ->where('type', DomainType::Custom)
                ->whereIn('status', [DomainStatus::Verified, DomainStatus::Active])
                ->orderBy('hostname')
                ->pluck('hostname', 'id')
                ->all();
        }

        return $options;
    }

    private static function canChooseDomain(): bool
    {
        return self::featureEnabled('platform_domain_choice') || self::featureEnabled('custom_domains');
    }

    private static function featureEnabled(string $key): bool
    {
        $tenant = auth()->user()?->tenant;

        if ($tenant === null) {
            return false;
        }

        try {
            return (bool) (app(EffectiveEntitlements::class)->for($tenant)['features'][$key] ?? false);
        } catch (ModelNotFoundException) {
            return false;
        }
    }

    /**
     * @return array<Component>
     */
    private static function moduleFormComponents(Get $get): array
    {
        $type = self::moduleType($get);

        if ($type === null) {
            return [];
        }

        return app(ModuleRegistry::class)->get($type)->editFormComponents();
    }

    /**
     * @return array<Section>
     */
    private static function moduleSidebarSections(Get $get): array
    {
        $type = self::moduleType($get);

        if ($type === null) {
            return [];
        }

        $components = app(ModuleRegistry::class)->get($type)->editSidebarComponents();

        if ($components === []) {
            return [];
        }

        return [
            Section::make(fn (ModuleRegistry $registry): string => $registry->get($type)->label())
                ->schema($components),
        ];
    }

    private static function moduleType(Get $get): ?QodeType
    {
        $type = $get('type');

        if ($type instanceof QodeType) {
            return $type;
        }

        if (is_string($type) && $type !== '') {
            return QodeType::tryFrom($type);
        }

        return null;
    }

    private static function redirectMode(Get $get): string
    {
        return (string) ($get('settings.redirect.to') ?? RedirectDestination::MODE_NONE);
    }

    private static function qrUrl(?Qode $record, string $format): ?string
    {
        if ($record === null || blank($record->slug)) {
            return null;
        }

        return route('qodes.qr', ['qode' => $record, 'format' => $format]);
    }
}
