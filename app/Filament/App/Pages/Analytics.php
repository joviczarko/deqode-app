<?php

namespace App\Filament\App\Pages;

use App\Billing\EffectiveEntitlements;
use App\Models\User;
use App\Models\Visit;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

class Analytics extends Page
{
    protected string $view = 'filament.app.pages.analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Qodes';

    protected static ?int $navigationSort = 6;

    public int $totalVisits = 0;

    public ?int $maxScans = null;

    public bool $overSoft = false;

    public bool $overHard = false;

    /**
     * @var Collection<int, object{date: string, total: int}>
     */
    public Collection $dailySeries;

    /**
     * @var Collection<int, object{qode_id: int, name: string, total: int}>
     */
    public Collection $topQodes;

    /**
     * @var Collection<int, object{device: string, total: int}>
     */
    public Collection $devices;

    public function mount(EffectiveEntitlements $entitlements): void
    {
        /** @var User $user */
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->maxScans = $entitlements->quota($user->tenant, 'max_scans');
        $this->maxScans = is_numeric($this->maxScans) ? (int) $this->maxScans : null;

        $this->totalVisits = Visit::query()->where('tenant_id', $tenantId)->count();

        $softLimit = $this->maxScans !== null ? (int) floor($this->maxScans * 0.8) : null;
        $this->overSoft = $softLimit !== null && $this->totalVisits >= $softLimit;
        $this->overHard = $this->maxScans !== null && $this->totalVisits >= $this->maxScans;

        $from = Carbon::now()->subDays(13)->startOfDay();

        $this->dailySeries = Visit::query()
            ->where('tenant_id', $tenantId)
            ->where('visited_at', '>=', $from)
            ->selectRaw('date(visited_at) as date, count(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $this->topQodes = Visit::query()
            ->where('visits.tenant_id', $tenantId)
            ->join('qodes', 'qodes.id', '=', 'visits.qode_id')
            ->selectRaw('visits.qode_id, qodes.name, count(*) as total')
            ->groupBy('visits.qode_id', 'qodes.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $this->devices = Visit::query()
            ->where('tenant_id', $tenantId)
            ->selectRaw('coalesce(device, ?) as device, count(*) as total', ['unknown'])
            ->groupBy('device')
            ->orderByDesc('total')
            ->get();
    }
}
