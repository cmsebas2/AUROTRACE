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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('op_number')->unique(); // El número de OP digitado
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('batch_number')->unique(); // El lote físico
            $table->decimal('bulk_size_kg', 12, 3)->default(0); // Suma total calculada de granel
            $table->string('unit')->default('KG');
            
            // Fechas de manufactura
            $table->date('manufacturing_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('destruction_date')->nullable();
            
            $table->string('maquilador')->nullable();
            $table->enum('status', ['PLANEADO', 'LIBERADO', 'EN_PROCESO', 'COMPLETADO', 'CUARENTENA', 'ANULADO'])->default('PLANEADO');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
