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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('internal_number')->unique();

            //$table->foreignId('client_id')->constrained('clients');
            $table->morphs('customer');
            $table->foreignId('user_id')->nullable()->constrained('users');

            // 1 = Venta / 2 = Reparación (Usando SaleType Enum)
            $table->unsignedTinyInteger('sale_type')->default(1)->comment('1=Venta, 2=Reparación');
            $table->unsignedTinyInteger('repair_type')->nullable()->comment('1=Pantalla, 2=Batería, 3=Limpieza, 4=Otros');

            // 1 = pagado / 2 = pendiente / 3 = cancelado
            $table->unsignedTinyInteger('status')->default(2)->comment('1=Pagado, 2=Pendiente, 3=Cancelado');

            $table->decimal('amount_received', 10, 2)->default(0);
            $table->decimal('change_returned', 10, 2)->default(0);
            $table->decimal('remaining_balance', 10, 2)->default(0);

            $table->decimal('total_amount', 10, 2);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
            //$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
