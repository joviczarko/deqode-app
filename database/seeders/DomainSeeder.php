<?php

namespace Database\Seeders;

use App\Enums\DomainStatus;
use App\Enums\DomainType;
use App\Models\Domain;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        Domain::query()->updateOrCreate(
            ['hostname' => config('deqode.platform_domain', 'deqode.test')],
            [
                'type' => DomainType::Platform,
                'tenant_id' => null,
                'status' => DomainStatus::Active,
                'is_default' => true,
            ],
        );
    }
}
