<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // 1. Eliminamos el índice único simple anterior
            $table->dropUnique(['document']);

            // 2. Creamos el nuevo índice compuesto
            $table->unique(['document', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Revertimos los cambios en caso de rollback
            $table->dropUnique(['document', 'branch_id']);
            $table->unique('document');
        });
    }
};
