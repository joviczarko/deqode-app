<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-filament::section>
            <x-slot name="heading">Total scans</x-slot>
            <p class="text-3xl font-semibold" data-analytics-total="{{ $this->totalVisits }}">{{ number_format($this->totalVisits) }}</p>
            @if ($this->maxScans !== null)
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Quota: {{ number_format($this->totalVisits) }} / {{ number_format($this->maxScans) }}
                </p>
            @endif
            @if ($this->overHard)
                <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">Hard scan limit reached — new scans are not recorded.</p>
            @elseif ($this->overSoft)
                <p class="mt-2 text-sm text-warning-600 dark:text-warning-400">Approaching scan quota (soft limit).</p>
            @endif
        </x-filament::section>

        <x-filament::section class="lg:col-span-2">
            <x-slot name="heading">Last 14 days</x-slot>
            @if ($this->dailySeries->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No scans yet.</p>
            @else
                <ul class="space-y-2 text-sm" data-analytics-series="1">
                    @foreach ($this->dailySeries as $row)
                        <li class="flex justify-between gap-4">
                            <span>{{ $row->date }}</span>
                            <span class="font-medium">{{ number_format($row->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Top Qodes</x-slot>
            @if ($this->topQodes->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No scans yet.</p>
            @else
                <ul class="space-y-2 text-sm" data-analytics-top-qodes="1">
                    @foreach ($this->topQodes as $row)
                        <li class="flex justify-between gap-4">
                            <span>{{ $row->name }}</span>
                            <span class="font-medium">{{ number_format($row->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Devices</x-slot>
            @if ($this->devices->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No scans yet.</p>
            @else
                <ul class="space-y-2 text-sm" data-analytics-devices="1">
                    @foreach ($this->devices as $row)
                        <li class="flex justify-between gap-4">
                            <span>{{ $row->device }}</span>
                            <span class="font-medium">{{ number_format($row->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
