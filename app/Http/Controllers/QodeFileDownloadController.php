<?php

namespace App\Http\Controllers;

use App\Enums\DomainStatus;
use App\Enums\QodeStatus;
use App\Enums\QodeType;
use App\Models\Domain;
use App\Models\Qode;
use App\QodeModules\Modules\FileDownloadModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QodeFileDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        string $slug,
        FileDownloadModule $module,
    ): StreamedResponse {
        $domain = $this->resolveDomain($request);

        if ($domain === null) {
            abort(404);
        }

        $qode = Qode::withoutGlobalScopes()
            ->where('domain_id', $domain->id)
            ->where('slug', $slug)
            ->first();

        if (
            $qode === null
            || $qode->status !== QodeStatus::Active
            || $qode->type !== QodeType::FileDownload
        ) {
            abort(404);
        }

        $file = $module->resolveFile($qode);

        if ($file === null || ! Storage::disk($file->disk)->exists($file->path)) {
            abort(404);
        }

        $downloadName = filled($qode->settings['download_name'] ?? null)
            ? (string) $qode->settings['download_name']
            : $file->original_name;

        return Storage::disk($file->disk)->download($file->path, $downloadName, [
            'Content-Type' => $file->mime ?: 'application/octet-stream',
        ]);
    }

    private function resolveDomain(Request $request): ?Domain
    {
        $prefix = trim((string) config('deqode.scan_path_prefix', ''), '/');

        if ($prefix !== '') {
            return Domain::defaultPlatform();
        }

        return Domain::query()
            ->where('hostname', $request->getHost())
            ->where('status', DomainStatus::Active)
            ->first();
    }
}
