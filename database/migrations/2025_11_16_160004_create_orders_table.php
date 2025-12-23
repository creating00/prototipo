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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            //$table->foreignId('client_id')->constrained('clients');
            $table->morphs('customer'); // customer_type + customer_id

            $table->foreignId('user_id')->nullable()->constrained('users');

            // 0 = borrador / 1 = confirmado / 2 = cancelado / 3 = convertido en venta
            $table->unsignedTinyInteger('status')->default(0);

            // 1 = backoffice / 2 = ecommerce
            $table->unsignedTinyInteger('source')->default(1);

            // Si se convierte en venta, referencia a la venta creada
            $table->foreignId('sale_id')->nullable()->constrained('sales');

            $table->decimal('total_amount', 10, 2);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
