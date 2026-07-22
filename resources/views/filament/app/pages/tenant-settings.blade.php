<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">External analytics</x-slot>
            <x-slot name="description">Injected on HTML Qode pages only — not on bare redirects.</x-slot>

            <form wire:submit="saveAnalytics" class="space-y-4">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="ga4_measurement_id"
                        placeholder="G-XXXXXXXX"
                    />
                </x-filament::input.wrapper>
                <p class="text-sm text-gray-500 dark:text-gray-400">GA4 Measurement ID</p>

                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="meta_pixel_id"
                        placeholder="Meta Pixel ID"
                    />
                </x-filament::input.wrapper>
                <p class="text-sm text-gray-500 dark:text-gray-400">Meta Pixel ID</p>

                <x-filament::button type="submit">Save analytics</x-filament::button>
            </form>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Custom domains</x-slot>
            <x-slot name="description">Claim a hostname, publish TXT <code>deqode-verify=…</code>, then verify.</x-slot>

            <form wire:submit="claimDomain" class="mb-6 space-y-3">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="hostname"
                        placeholder="qr.brand.com"
                    />
                </x-filament::input.wrapper>
                <x-filament::button type="submit">Claim domain</x-filament::button>
            </form>

            <ul class="space-y-3 text-sm">
                @forelse ($this->customDomains as $domain)
                    <li class="rounded-xl border border-gray-200 p-4 dark:border-white/10" data-domain-status="{{ $domain->status->value }}">
                        <div class="font-medium">{{ $domain->hostname }}</div>
                        <div class="text-gray-500 dark:text-gray-400">Status: {{ $domain->status->value }}</div>
                        @if ($domain->status === \App\Enums\DomainStatus::Pending)
                            <div class="mt-2 break-all text-xs">TXT: deqode-verify={{ $domain->verification_token }}</div>
                            <x-filament::button size="sm" class="mt-3" wire:click="verifyDomain({{ $domain->id }})">
                                Verify DNS
                            </x-filament::button>
                        @endif
                    </li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">No custom domains yet.</li>
                @endforelse
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
