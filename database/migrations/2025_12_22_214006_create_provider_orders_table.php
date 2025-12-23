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
        Schema::create('provider_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('provider_id')->constrained();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('received_date')->nullable();

            $table->tinyInteger('status')->default(1);
            // pending, partial, received, cancelled
            $table->timestamps();
        });

        Schema::create('provider_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_order_id')->constrained();
            $table->foreignId('provider_product_id')->constrained();

            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->tinyInteger('currency');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_orders');
    }
};
