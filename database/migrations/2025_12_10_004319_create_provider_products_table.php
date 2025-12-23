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
        Schema::create('provider_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('provider_id')->constrained('providers');

            $table->string('provider_code')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->tinyInteger('status')->default(1); // ProviderProductStatus enum

            $table->timestamps();
            $table->softDeletes();

            //$table->unique(['product_id', 'provider_id']);
            $table->unique(['branch_id', 'product_id', 'provider_id'], 'idx_branch_prod_prov_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_products');
    }
};
