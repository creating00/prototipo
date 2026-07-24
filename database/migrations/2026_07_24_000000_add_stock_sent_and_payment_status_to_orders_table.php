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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'is_stock_sent')) {
                $table->boolean('is_stock_sent')->default(false)->after('status');
            }
            if (!Schema::hasColumn('orders', 'stock_sent_at')) {
                $table->timestamp('stock_sent_at')->nullable()->after('is_stock_sent');
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->unsignedTinyInteger('payment_status')->default(2)->after('stock_sent_at'); // 1 = Pagado, 2 = Pendiente
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_stock_sent', 'stock_sent_at', 'payment_status']);
        });
    }
};
