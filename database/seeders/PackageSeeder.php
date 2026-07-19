<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        Package::query()->updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free / Trial',
                'is_free' => true,
                'is_active' => true,
                'trial_days' => 14,
                'price_monthly_cents' => 0,
                'quotas' => [
                    'max_qodes' => 10,
                ],
                'features' => [
                    'custom_domains' => false,
                    'custom_slugs' => false,
                ],
            ],
        );
    }
}
