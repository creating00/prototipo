<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Relación con el usuario que registró el gasto
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Relación con la sucursal
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Relación con el tipo de gasto (ej: luz, agua, internet)
            $table->foreignId('expense_type_id')
                ->constrained('expense_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Monto del gasto
            $table->decimal('amount', 12, 2);

            // Moneda (enum CurrencyType, default 1)
            $table->tinyInteger('currency')->default(1);

            // Tipo de pago (enum PaymentType, default 1)
            $table->tinyInteger('payment_type')->default(1);

            // Información adicional (ej: número de factura)
            $table->string('reference')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
