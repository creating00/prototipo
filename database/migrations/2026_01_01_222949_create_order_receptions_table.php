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
        Schema::create('order_receptions', function (Blueprint $table) {
            $table->id();

            // Relación con el pedido original
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Usuario que físicamente recibe el pedido
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // 1 = Recibido Completo / 2 = Recibido con Observaciones / 3 = Rechazado
            $table->unsignedTinyInteger('status')->default(1);

            // Fecha y hora de recepción
            $table->timestamp('received_at');

            // Espacio para registrar novedades o discrepancias en la entrega
            $table->text('observation')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_receptions');
    }
};
