<?php

namespace App\Http\Controllers;

use App\Actions\CaptureLead;
use App\Enums\QodeStatus;
use App\Models\Domain;
use App\Models\Qode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CaptureLeadController extends Controller
{
    public function __invoke(
        Request $request,
        string $slug,
        CaptureLead $captureLead,
    ): RedirectResponse {
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

        try {
            $captureLead->handle($qode, $request->all());
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(url()->previous());
        }

        return redirect()
            ->to(url()->previous())
            ->with('lead_submitted', true);
    }

    private function resolveDomain(Request $request): ?Domain
    {
        $prefix = trim((string) config('deqode.scan_path_prefix', ''), '/');

        if ($prefix !== '' && $request->is($prefix.'/*')) {
            return Domain::defaultPlatform();
        }

        $domain = Domain::query()
            ->where('hostname', $request->getHost())
            ->first();

        if ($domain === null || ! $domain->isServable()) {
            return null;
        }

        return $domain;
    }
}
