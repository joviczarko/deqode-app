<?php

namespace App\Jobs;

use App\Models\Visit;
use App\Support\UserAgentDevice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PersistVisit implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{tenant_id: int, qode_id: int, visited_at: string, referrer: ?string, user_agent: ?string}  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(): void
    {
        Visit::withoutGlobalScopes()->create([
            'tenant_id' => $this->payload['tenant_id'],
            'qode_id' => $this->payload['qode_id'],
            'visited_at' => $this->payload['visited_at'],
            'referrer' => $this->payload['referrer'],
            'user_agent' => $this->payload['user_agent'],
            'device' => UserAgentDevice::detect($this->payload['user_agent'] ?? null),
        ]);
    }
}
