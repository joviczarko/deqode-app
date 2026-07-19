<?php

namespace App\Http\Controllers;

use App\Actions\RecordVisit;
use App\Enums\DomainStatus;
use App\Enums\QodeStatus;
use App\Models\Domain;
use App\Models\Qode;
use App\QodeModules\ModuleRegistry;
use App\QodeModules\RedirectDestination;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QodeResolveController extends Controller
{
    public function __invoke(
        Request $request,
        string $slug,
        ModuleRegistry $registry,
        RecordVisit $recordVisit,
        RedirectDestination $redirect,
    ): Response {
        $domain = $this->resolveDomain($request);

        if ($domain === null) {
            abort(404);
        }

        $qode = Qode::withoutGlobalScopes()
            ->where('domain_id', $domain->id)
            ->where('slug', $slug)
            ->first();

        if ($qode === null || $qode->status !== QodeStatus::Active) {
            abort(404);
        }

        $recordVisit->handle($qode, $request);

        $redirectUrl = $redirect->urlOrNull($qode);

        if ($redirectUrl !== null) {
            return redirect()->away($redirectUrl, 302);
        }

        return $registry->get($qode->type)->render($qode, $request);
    }

    private function resolveDomain(Request $request): ?Domain
    {
        $prefix = trim((string) config('deqode.scan_path_prefix', ''), '/');

        // Local single-host mode: /r/{slug} always resolves against the default platform domain.
        if ($prefix !== '') {
            return Domain::defaultPlatform();
        }

        $domain = Domain::query()
            ->where('hostname', $request->getHost())
            ->where('status', DomainStatus::Active)
            ->first();

        return $domain;
    }
}
