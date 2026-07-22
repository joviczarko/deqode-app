<?php

namespace Database\Seeders;

use App\Enums\PackageStatus;
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
                'status' => PackageStatus::Trial,
                'is_free' => true,
                'is_active' => true,
                'trial_days' => 14,
                'price_monthly_cents' => 0,
                'quotas' => [
                    'max_qodes' => 10,
                    'max_scans' => 1000,
                ],
                'features' => [
                    'custom_domains' => false,
                    'custom_slugs' => false,
                    'platform_domain_choice' => false,
                ],
            ],
        );

        Package::query()->updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'status' => PackageStatus::Active,
                'is_free' => false,
                'is_active' => true,
                'trial_days' => 0,
                'price_monthly_cents' => 1900,
                'quotas' => [
                    'max_qodes' => 50,
                    'max_scans' => 10000,
                ],
                'features' => [
                    'custom_domains' => false,
                    'custom_slugs' => false,
                    'platform_domain_choice' => false,
                ],
            ],
        );

        Package::query()->updateOrCreate(
            ['slug' => 'growth'],
            [
                'name' => 'Growth',
                'status' => PackageStatus::Active,
                'is_free' => false,
                'is_active' => true,
                'trial_days' => 0,
                'price_monthly_cents' => 4900,
                'quotas' => [
                    'max_qodes' => 250,
                    'max_scans' => 100000,
                ],
                'features' => [
                    'custom_domains' => true,
                    'custom_slugs' => true,
                    'platform_domain_choice' => true,
                ],
            ],
        );
    }
}
