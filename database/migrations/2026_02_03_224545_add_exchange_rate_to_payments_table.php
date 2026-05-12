<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('exchange_rate', 15, 4)
                ->after('amount')
                ->nullable()
                ->comment('Cotización aplicada al pago en moneda extranjera');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
