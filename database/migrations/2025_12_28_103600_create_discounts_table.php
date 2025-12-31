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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->unsignedTinyInteger('type')
                ->comment('1=Fixed, 2=Percentage');

            $table->decimal('value', 10, 2);
            // fixed: monto absoluto
            // percentage: porcentaje (ej: 10 = 10%)

            $table->decimal('max_amount', 10, 2)
                ->nullable();
            // Tope máximo para porcentajes

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
