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
        Schema::create('promotion_images', function (Blueprint $table) {
            $table->id();

            // Relación con sucursal (opcional si ya está en promotions)
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_images');
    }
};
