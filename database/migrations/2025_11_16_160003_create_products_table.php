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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Código único global
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('branch_id')->constrained('branches');

            $table->integer('stock')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->tinyInteger('status')->default(1); // ProductStatus enum

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'branch_id']);
        });

        Schema::create('product_branch_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_branch_id')->constrained('product_branches');

            $table->tinyInteger('type'); // Usaremos PriceType::PURCHASE, ::SALE, ::WHOLESALE
            $table->decimal('amount', 10, 2);
            $table->tinyInteger('currency'); // Usaremos CurrencyType::ARS, ::USD

            $table->timestamps();

            $table->unique(['product_branch_id', 'type', 'currency']);
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('rate', 3, 1);
            $table->integer('count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
