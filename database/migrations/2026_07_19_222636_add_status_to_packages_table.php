<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('status')->default('active')->after('name')->index();
        });

        DB::table('packages')->orderBy('id')->each(function (object $package): void {
            $status = match (true) {
                (bool) $package->is_free => 'trial',
                ! (bool) $package->is_active => 'hidden',
                default => 'active',
            };

            DB::table('packages')->where('id', $package->id)->update(['status' => $status]);
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
