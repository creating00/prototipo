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
        Schema::create('repair_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->unsignedTinyInteger('repair_type');
            $table->decimal('amount', 10, 2);

            // Si es null, es histórico. Si es 1, es el activo.
            $table->boolean('active_only_one')->nullable()->default(1);

            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // El índice ahora solo chocará si intentas tener dos registros con "1"
            $table->unique(['branch_id', 'repair_type', 'active_only_one'], 'uniq_repair_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_amounts');
    }
};
