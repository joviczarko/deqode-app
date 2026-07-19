<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->constrained()->restrictOnDelete();
            $table->foreignId('domain_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('type')->index();
            $table->string('status')->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'slug']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'collection_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qodes');
    }
};
