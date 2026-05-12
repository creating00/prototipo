<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Hacer nullable campos existentes
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('expense_type_id')->nullable()->change();

            // Añadir nuevos campos solicitados
            $table->date('date')->after('amount')->comment('Fecha del gasto');
            $table->text('observation')->nullable()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Revertir cambios
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreignId('expense_type_id')->nullable(false)->change();

            $table->dropColumn(['date', 'observation']);
        });
    }
};
