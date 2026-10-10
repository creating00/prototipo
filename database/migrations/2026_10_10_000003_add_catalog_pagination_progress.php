<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_catalogs', function (Blueprint $table) {
            $table->json('pagination')->nullable();
            $table->timestamp('last_activity_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('provider_catalogs', fn (Blueprint $table) => $table->dropColumn(['pagination', 'last_activity_at']));
    }
};
