<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('provider_products', function (Blueprint $table) {
            // 1. Eliminar la clave foránea primero
            $table->dropForeign(['branch_id']);

            // 2. Ahora sí podemos borrar el índice único
            $table->dropUnique('idx_branch_prod_prov_unique');

            // 3. Cambiamos a nullable y volvemos a crear la foránea
            $table->foreignId('branch_id')->nullable()->change();
            $table->foreign('branch_id')->references('id')->on('branches');

            // 4. Nueva restricción global
            $table->unique(['product_id', 'provider_id'], 'idx_prod_prov_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_products', function (Blueprint $table) {
            $table->dropUnique('idx_prod_prov_unique');

            $table->dropForeign(['branch_id']);
            $table->foreignId('branch_id')->nullable(false)->change();
            $table->foreign('branch_id')->references('id')->on('branches');

            $table->unique(['branch_id', 'product_id', 'provider_id'], 'idx_branch_prod_prov_unique');
        });
    }
};
