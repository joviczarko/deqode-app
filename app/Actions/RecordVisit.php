<?php

namespace App\Actions;

use App\Billing\EffectiveEntitlements;
use App\Jobs\PersistVisit;
use App\Models\Qode;
use App\Models\Visit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class RecordVisit
{
    public function __construct(
        private EffectiveEntitlements $entitlements,
    ) {}

    /**
     * @return array{recorded: bool, over_soft: bool, over_hard: bool, count: int, max_scans: int|null}
     */
    public function handle(Qode $qode, Request $request): array
    {
        $qode->loadMissing('tenant');

        $maxScans = $this->maxScansFor($qode);

        $count = Visit::withoutGlobalScopes()
            ->where('tenant_id', $qode->tenant_id)
            ->count();

        $softLimit = $maxScans !== null ? (int) floor($maxScans * 0.8) : null;
        $overSoft = $softLimit !== null && $count >= $softLimit;
        $overHard = $maxScans !== null && $count >= $maxScans;

        if ($overHard) {
            return [
                'recorded' => false,
                'over_soft' => true,
                'over_hard' => true,
                'count' => $count,
                'max_scans' => $maxScans,
            ];
        }

        PersistVisit::dispatch([
            'tenant_id' => $qode->tenant_id,
            'qode_id' => $qode->id,
            'visited_at' => now()->toIso8601String(),
            'referrer' => $request->headers->get('referer'),
            'user_agent' => $request->userAgent(),
        ]);

        return [
            'recorded' => true,
            'over_soft' => $overSoft,
            'over_hard' => false,
            'count' => $count + 1,
            'max_scans' => $maxScans,
        ];
    }

    private function maxScansFor(Qode $qode): ?int
    {
        try {
            $maxScans = $this->entitlements->quota($qode->tenant, 'max_scans');
        } catch (ModelNotFoundException) {
            $maxScans = config('packages.quotas.max_scans.default');
        }

        return is_numeric($maxScans) ? (int) $maxScans : null;
    }
}
