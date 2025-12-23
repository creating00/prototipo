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
        Schema::create('product_provider_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('provider_product_id')->constrained('provider_products');

            $table->decimal('cost_price', 10, 2);
            $table->tinyInteger('currency'); // CurrencyType enum

            $table->date('effective_date');
            $table->date('end_date')->nullable();

            $table->timestamps();

            // Un precio por fecha de inicio por proveedor-producto
            // $table->unique(
            //     ['provider_product_id', 'effective_date'],
            //     'uniq_provider_prod_effdate'
            // );

            $table->unique(
                ['branch_id', 'provider_product_id', 'effective_date'],
                'uniq_branch_prov_prod_effdate'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_provider_prices');
    }
};
