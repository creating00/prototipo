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
        Schema::create('price_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('user_id')->constrained('users'); // Usuario que modificó
            $table->foreignId('product_id')->constrained('products');

            // El precio que estaba definido en product_branch_prices
            $table->decimal('original_price', 10, 2);
            // El nuevo precio ingresado manualmente
            $table->decimal('modified_price', 10, 2);

            // Opcional: Vincularlo a la venta si el cambio ya se concretó
            $table->foreignId('sale_id')->nullable()->constrained('sales');

            $table->string('reason')->nullable(); // Por qué se cambió
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_modifications');
    }
};
