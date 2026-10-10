<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_catalog_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->string('provider_name');
            $table->uuid('query_token')->unique();
            $table->text('source_url');
            $table->string('status');
            $table->text('message')->nullable();
            $table->unsignedInteger('product_count');
            $table->timestamp('queried_at');
            $table->timestamp('processed_at');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['provider_id', 'processed_at']);
        });
        Schema::create('provider_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('provider_catalog_snapshots')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('name')->nullable();
            $table->text('code')->nullable();
            $table->json('data');
            $table->timestamps();
            $table->unique(['snapshot_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_catalog_items');
        Schema::dropIfExists('provider_catalog_snapshots');
    }
};
