<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            // Totales por moneda
            $table->json('totals')
                ->nullable()
                ->after('sale_type');

            // Eliminamos columnas viejas
            if (Schema::hasColumn('sales', 'total_amount')) {
                $table->dropColumn('total_amount');
            }

            if (Schema::hasColumn('sales', 'subtotal_amount')) {
                $table->dropColumn('subtotal_amount');
            }

            if (Schema::hasColumn('sales', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->tinyInteger('currency')->default(1);

            $table->dropColumn('totals');
        });
    }
};
