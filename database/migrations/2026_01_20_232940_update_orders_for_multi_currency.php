<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // 1. Agregamos totals
            $table->json('totals')
                ->nullable()
                ->after('sale_id');

            // 2. Eliminamos columnas obsoletas
            if (Schema::hasColumn('orders', 'total_amount')) {
                $table->dropColumn('total_amount');
            }

            if (Schema::hasColumn('orders', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // Restauramos columnas antiguas por rollback
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->tinyInteger('currency')->default(1);

            $table->dropColumn('totals');
        });
    }
};
