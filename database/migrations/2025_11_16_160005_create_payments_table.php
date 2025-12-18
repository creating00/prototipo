<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Polimorfismo → permite pagos a orders, sales, repair_orders, etc.
            $table->morphs('paymentable');

            $table->foreignId('user_id')->constrained('users');

            // Enum PaymentType: efectivo, tarjeta, transferencia, cheque
            $table->unsignedTinyInteger('payment_type');

            $table->decimal('amount', 10, 2);

            $table->timestamps();
            $table->softDeletes();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
