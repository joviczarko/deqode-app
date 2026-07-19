<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalName = fake()->word().'.pdf';

        return [
            'tenant_id' => Tenant::factory(),
            'disk' => 's3',
            'path' => fn (array $attributes) => $attributes['tenant_id'].'/'.(string) Str::uuid7().'-doc.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(1000, 500_000),
            'original_name' => $originalName,
        ];
    }
}
