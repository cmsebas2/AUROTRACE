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
        // Drop legacy/incompatible tables if present
        Schema::dropIfExists('maquila_deliveries');
        Schema::dropIfExists('maquila_order_items');
        Schema::dropIfExists('maquila_orders');

        // Create maquila_orders table
        Schema::create('maquila_orders', function (Blueprint $table) {
            $table->id();
            $table->string('maquilador')->nullable();
            $table->string('fecha_creacion')->nullable();
            $table->string('estatus')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('op')->nullable();
            $table->string('codigo_item')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('lote')->unique();
            $table->string('fecha_fabricacion')->nullable();
            $table->string('fecha_vencimiento')->nullable();
            $table->decimal('cantidad_programada', 12, 2)->default(0);
            $table->decimal('adicional', 12, 2)->default(0);
            $table->decimal('devolucion', 12, 2)->default(0);
            $table->decimal('restante', 12, 2)->default(0);
            $table->string('balance')->nullable();
            $table->string('fecha_balance')->nullable();
            $table->decimal('pendiente', 12, 2)->default(0);
            $table->string('fecha_despacho_maquila')->nullable();
            $table->string('documento_traslado')->nullable();
            $table->string('fecha_llegada_aurofarma')->nullable();
            $table->string('op_secundaria')->nullable();
            $table->text('observaciones')->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamps();
        });

        // Create maquila_deliveries table
        Schema::create('maquila_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquila_order_id')->constrained('maquila_orders')->onDelete('cascade');
            $table->string('lote');
            $table->integer('numero_entrega');
            $table->string('documento_remision')->nullable();
            $table->decimal('cantidad_entregada', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquila_deliveries');
        Schema::dropIfExists('maquila_orders');
    }
};
