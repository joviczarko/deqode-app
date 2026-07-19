<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id()->startingValue(4000);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active')->index();
            $table->json('analytics_settings')->nullable();
            $table->timestamps();
        });

        $this->seedAutoIncrement(4000);
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }

    private function seedAutoIncrement(int $nextId): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE tenants AUTO_INCREMENT = '.$nextId);

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER SEQUENCE tenants_id_seq RESTART WITH '.$nextId);

            return;
        }

        if ($driver === 'sqlite') {
            $placeholderId = $nextId - 1;

            DB::table('tenants')->insert([
                'id' => $placeholderId,
                'name' => '__id_seed__',
                'slug' => '__id_seed__',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('tenants')->where('id', $placeholderId)->delete();
        }
    }
};
