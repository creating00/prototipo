<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            // Relación con sucursales
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Contenido visual
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('label')->nullable();

            // Acción y control
            $table->json('buttons')->nullable(); // Guardará: [{"text": "COMPRAR", "url": "/tienda", "style": "primary"}]
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes(); // Implementación de Soft Deletes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
