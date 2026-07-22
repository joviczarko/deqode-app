<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qode_id')->constrained()->cascadeOnDelete();
            $table->timestamp('visited_at');
            $table->string('referrer')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 32)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'visited_at']);
            $table->index(['qode_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
