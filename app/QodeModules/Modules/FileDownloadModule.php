<?php

namespace App\QodeModules\Modules;

use App\Enums\QodeType;
use App\Models\File;
use App\Models\Qode;
use App\QodeModules\Contracts\QodeModule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FileDownloadModule implements QodeModule
{
    public function type(): QodeType
    {
        return QodeType::FileDownload;
    }

    public function label(): string
    {
        return QodeType::FileDownload->label();
    }

    public function defaultSettings(): array
    {
        return [
            'file_id' => null,
            'download_name' => null,
        ];
    }

    public function editFormComponents(): array
    {
        return [
            Select::make('settings.file_id')
                ->label('File')
                ->options(fn (): array => File::query()
                    ->orderByDesc('id')
                    ->pluck('original_name', 'id')
                    ->all())
                ->searchable()
                ->required()
                ->native(false),
            TextInput::make('settings.download_name')
                ->label('Download name')
                ->helperText('Optional override for the downloaded filename.')
                ->maxLength(255),
        ];
    }

    public function editSidebarComponents(): array
    {
        return [];
    }

    public function render(Qode $qode, Request $request): Response
    {
        $qode->loadMissing('tenant');

        $file = $this->resolveFile($qode);

        if ($file === null) {
            abort(404);
        }

        $title = (string) ($qode->name ?: 'Download');
        $downloadName = filled($qode->settings['download_name'] ?? null)
            ? (string) $qode->settings['download_name']
            : $file->original_name;

        return response()->view('modules.file_download', [
            'qode' => $qode,
            'title' => $title,
            'file' => $file,
            'downloadName' => $downloadName,
            'downloadUrl' => $this->downloadUrl($qode),
        ]);
    }

    public function resolveFile(Qode $qode): ?File
    {
        $fileId = $qode->settings['file_id'] ?? null;

        if (! filled($fileId)) {
            return null;
        }

        return File::withoutGlobalScopes()
            ->where('tenant_id', $qode->tenant_id)
            ->whereKey($fileId)
            ->first();
    }

    private function downloadUrl(Qode $qode): string
    {
        $prefix = trim((string) config('deqode.scan_path_prefix', ''), '/');

        if ($prefix !== '') {
            return url('/'.$prefix.'/'.$qode->slug.'/download');
        }

        return url('/'.$qode->slug.'/download');
    }
}
