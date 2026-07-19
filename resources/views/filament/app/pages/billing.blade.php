<x-filament-panels::page>
    @if (session('success'))
        <div class="rounded-xl bg-success-50 p-4 text-sm text-success-700 dark:bg-success-400/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="rounded-xl bg-warning-50 p-4 text-sm text-warning-700 dark:bg-warning-400/10 dark:text-warning-400">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('status'))
        <div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-700 dark:bg-white/5 dark:text-gray-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Current plan</x-slot>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Package</dt>
                    <dd class="font-medium">{{ $this->currentPackageName() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Price</dt>
                    <dd class="font-medium">{{ $this->currentPriceLabel() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Max Qodes</dt>
                    <dd class="font-medium">{{ $this->currentMaxQodes() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="font-medium">{{ $this->isOnTrial() ? 'On trial' : 'Active / paid' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Upgrade (Demo checkout)</x-slot>
            <x-slot name="description">Uses the Demo gateway — Success / Fail / Cancel.</x-slot>

            <div class="space-y-3">
                @forelse ($this->packages as $package)
                    <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <div>
                            <div class="font-medium">{{ $package->name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $this->packagePriceLabel($package) }}</div>
                        </div>
                        <x-filament::button wire:click="checkout({{ $package->id }})" size="sm">
                            Checkout
                        </x-filament::button>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No paid packages are available yet.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Invoices</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
