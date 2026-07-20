<?php

namespace App\Filament\App\Resources\Qodes\Schemas;

use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Collection;
use App\Models\Qode;
use App\QodeModules\ModuleRegistry;
use App\QodeModules\RedirectDestination;
use App\Support\QodeUrlBuilder;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
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
                Group::make([
                    TextInput::make('settings.title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->default('Untitled')
                        ->extraInputAttributes([
                            'style' => 'font-size: 1.5rem; font-weight: 600; line-height: 1.3;',
                        ])
                        ->columnSpanFull(),
                    RichEditor::make('settings.body')
                        ->label('Body')
                        // settings is a JSON cast; without this Filament stores TipTap JSON, not HTML.
                        ->json(false)
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'strike', 'link'],
                            ['h2', 'h3'],
                            ['bulletList', 'orderedList'],
                            ['undo', 'redo'],
                        ])
                        ->fileAttachments(false)
                        ->columnSpanFull(),
                ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->visible(fn (Get $get): bool => self::moduleType($get) === QodeType::Content),
                Group::make([
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
                            TextInput::make('name')
                                ->label('Internal name')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Only visible in your panel.'),
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
                    Section::make('QR code')
                        ->description(fn (?Qode $record): ?string => filled($record?->slug)
                            ? 'Code: '.$record->slug
                            : null)
                        ->schema([
                            TextEntry::make('public_url')
                                ->label('URL')
                                ->state(fn (?Qode $record): ?string => self::publicUrl($record))
                                ->placeholder('Save to generate the public URL.')
                                ->copyable()
                                ->copyMessage('URL copied')
                                ->columnSpanFull(),
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

    private static function publicUrl(?Qode $record): ?string
    {
        if ($record === null || blank($record->slug)) {
            return null;
        }

        $record->loadMissing('domain');

        return app(QodeUrlBuilder::class)->forQode($record);
    }

    private static function qrUrl(?Qode $record, string $format): ?string
    {
        if ($record === null || blank($record->slug)) {
            return null;
        }

        return route('qodes.qr', ['qode' => $record, 'format' => $format]);
    }
}
