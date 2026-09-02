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
        // 1. Catálogo Corporativo de Maquiladores (BPM-ICA)
        Schema::create('maquiladores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('nit')->nullable();
            $table->boolean('activo')->default(true);
            $table->date('certificado_bpm_ica_vigente_hasta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Firmas Electrónicas Polimórficas (21 CFR Part 11)
        Schema::create('electronic_signatures', function (Blueprint $table) {
            $table->id();
            $table->string('signable_type');
            $table->unsignedBigInteger('signable_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('second_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('meaning');
            $table->string('hash_integridad');
            $table->timestamp('signed_at');
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['signable_type', 'signable_id']);
        });

        // 3. Órdenes de Producción en Maquila (ODM)
        Schema::create('maquila_production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('numero_odm')->unique();
            $table->string('op')->nullable();
            $table->string('lote')->nullable();
            $table->string('numero_sdm')->nullable();
            $table->enum('tipo_producto', ['premezcla', 'producto_terminado'])->default('producto_terminado');
            $table->foreignId('maquilador_id')->constrained('maquiladores')->onDelete('cascade');
            $table->date('fecha_creacion');
            $table->date('fecha_envio_maquila')->nullable();
            $table->enum('estado', [
                'borrador',
                'enviada_a_maquila',
                'en_proceso',
                'entrega_parcial',
                'completada_pendiente_liquidacion',
                'liquidada',
                'cerrada_tecnicamente',
                'anulada'
            ])->default('borrador');
            $table->foreignId('usuario_creador_id')->constrained('users')->onDelete('cascade');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Detalle de Ítems por Orden de Maquila
        Schema::create('maquila_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquila_production_order_id')->constrained('maquila_production_orders')->onDelete('cascade');
            $table->string('sdm')->nullable();
            $table->string('codigo_item');
            $table->string('descripcion_producto');
            $table->string('lote_fisico')->nullable();
            $table->string('presentacion')->nullable();
            $table->decimal('cantidad_programada', 12, 3);
            $table->enum('unidad_medida', ['KG', 'UND'])->default('KG');
            $table->date('fecha_fabricacion')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->boolean('liquidado')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Entregas Parciales (Remisiones / Transacciones de Recepción)
        Schema::create('maquila_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquila_item_id')->constrained('maquila_items')->onDelete('cascade');
            $table->date('fecha_recepcion');
            $table->string('numero_remision_factura');
            $table->decimal('cantidad_recibida', 12, 3);
            $table->foreignId('usuario_registro_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('firma_electronica_id')->nullable()->constrained('electronic_signatures')->onDelete('set null');
            $table->string('hash_integridad')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquila_deliveries');
        Schema::dropIfExists('maquila_items');
        Schema::dropIfExists('maquila_production_orders');
        Schema::dropIfExists('electronic_signatures');
        Schema::dropIfExists('maquiladores');
    }
};
