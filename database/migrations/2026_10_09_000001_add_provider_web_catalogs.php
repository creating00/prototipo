<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->text('catalog_url')->nullable();
        });
        Schema::create('provider_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('token');
            $table->string('status');
            $table->text('source_url');
            $table->text('message')->nullable();
            $table->json('products')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_catalogs');
        Schema::table('providers', fn (Blueprint $table) => $table->dropColumn('catalog_url'));
    }
};
