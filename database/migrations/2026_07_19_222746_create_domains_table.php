<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('hostname')->unique();
            $table->string('type')->index();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['type', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
