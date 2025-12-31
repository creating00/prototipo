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
        Schema::table('sales', function (Blueprint $table) {

            $table->decimal('subtotal_amount', 10, 2)
                ->after('remaining_balance');
            // Total original sin descuento (suma de sale_items)

            $table->foreignId('discount_id')
                ->nullable()
                ->after('subtotal_amount')
                ->constrained('discounts')
                ->nullOnDelete();
            // Regla de descuento aplicada (si hubo)

            $table->decimal('discount_amount', 10, 2)
                ->default(0)
                ->after('discount_id');
            // Monto del descuento aplicado (copiado desde discounts)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            //
        });
    }
};
