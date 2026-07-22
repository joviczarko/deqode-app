<?php

namespace App\QodeModules\Modules;

use App\Actions\CaptureLead;
use App\Enums\QodeType;
use App\Models\Qode;
use App\QodeModules\Contracts\QodeModule;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FormModule implements QodeModule
{
    public function type(): QodeType
    {
        return QodeType::Form;
    }

    public function label(): string
    {
        return QodeType::Form->label();
    }

    public function defaultSettings(): array
    {
        return [
            'fields' => [],
        ];
    }

    public function editFormComponents(): array
    {
        return [
            Repeater::make('settings.fields')
                ->label('Form fields')
                ->schema([
                    TextInput::make('label')
                        ->label('Label')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                            if (filled($get('key'))) {
                                return;
                            }

                            $set('key', Str::slug((string) $state, '_'));
                        }),
                    TextInput::make('key')
                        ->label('Key')
                        ->required()
                        ->maxLength(64)
                        ->alphaDash()
                        ->helperText('Used in the lead payload.'),
                    Select::make('type')
                        ->label('Type')
                        ->options([
                            'text' => 'Text',
                            'email' => 'Email',
                            'textarea' => 'Textarea',
                        ])
                        ->required()
                        ->default('text'),
                    Checkbox::make('required')
                        ->label('Required')
                        ->default(false),
                ])
                ->defaultItems(0)
                ->addActionLabel('Add field')
                ->reorderable()
                ->columnSpanFull(),
        ];
    }

    public function editSidebarComponents(): array
    {
        return [];
    }

    public function render(Qode $qode, Request $request): Response
    {
        $qode->loadMissing('tenant');

        $title = (string) ($qode->name ?: 'Form');
        $fields = app(CaptureLead::class)->fields($qode);
        $action = $this->submitUrl($qode);

        return response()->view('modules.form', [
            'qode' => $qode,
            'title' => $title,
            'fields' => $fields,
            'action' => $action,
            'submitted' => $request->session()->get('lead_submitted', false),
        ]);
    }

    private function submitUrl(Qode $qode): string
    {
        $prefix = trim((string) config('deqode.scan_path_prefix', ''), '/');

        if ($prefix !== '') {
            return url('/'.$prefix.'/'.$qode->slug.'/leads');
        }

        return url('/'.$qode->slug.'/leads');
    }
}
