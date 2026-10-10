<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', fn (Blueprint $table) => $table->json('catalog_urls')->nullable());
        Schema::table('provider_catalogs', fn (Blueprint $table) => $table->char('source_key', 64)->nullable());
        DB::table('provider_catalogs')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('provider_catalogs')->where('id', $row->id)->update(['source_key' => hash('sha256', $row->source_url)]);
            }
        });
        // The composite index also supports the provider FK before its old unique index is removed.
        Schema::table('provider_catalogs', fn (Blueprint $table) => $table->unique(['provider_id', 'source_key']));
        Schema::table('provider_catalogs', fn (Blueprint $table) => $table->dropUnique(['provider_id']));
    }

    public function down(): void
    {
        if (DB::table('provider_catalogs')->select('provider_id')->groupBy('provider_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede volver a una consulta por proveedor sin retirar previamente las vistas previas adicionales. El historial procesado se conserva.');
        }
        Schema::table('provider_catalogs', fn (Blueprint $table) => $table->unique('provider_id'));
        Schema::table('provider_catalogs', function (Blueprint $table) {
            $table->dropUnique(['provider_id', 'source_key']);
            $table->dropColumn('source_key');
        });
        Schema::table('providers', fn (Blueprint $table) => $table->dropColumn('catalog_urls'));
    }
};
