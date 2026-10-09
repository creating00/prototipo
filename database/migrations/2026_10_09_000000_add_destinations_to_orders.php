<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_multi_destination')->default(false);
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('destination_branch_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->timestamp('stock_sent_at')->nullable();
            $table->unique(['order_id', 'product_id', 'destination_branch_id'], 'order_items_destination_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('orders')->where('is_multi_destination', true)->exists()) {
            throw new RuntimeException('No se puede revertir esta migración mientras existan pedidos con destinos por producto.');
        }
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropUnique('order_items_destination_unique');
            $table->dropConstrainedForeignId('destination_branch_id');
            $table->dropColumn('stock_sent_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('is_multi_destination');
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });
    }
};
