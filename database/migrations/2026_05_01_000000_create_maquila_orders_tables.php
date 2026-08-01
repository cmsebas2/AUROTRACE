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
        Schema::create('maquila_orders', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_producto', ['PREMEZCLA', 'PRODUCTO_TERMINADO']);
            $table->string('odm')->unique()->index();
            $table->string('sdm')->nullable()->index();
            $table->string('maquilador');
            $table->date('fecha_creacion');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('maquila_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquila_order_id')->constrained('maquila_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('cantidad', 12, 2);
            $table->string('lote');
            $table->decimal('cantidad_programada', 12, 2);
            $table->date('fecha_fabricacion');
            $table->date('fecha_vencimiento');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquila_order_items');
        Schema::dropIfExists('maquila_orders');
    }
};
