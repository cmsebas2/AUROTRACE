<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modificaciones a maquila_production_orders
        if (Schema::hasTable('maquila_production_orders')) {
            Schema::table('maquila_production_orders', function (Blueprint $table) {
                // Datos de creación
                if (!Schema::hasColumn('maquila_production_orders', 'pre_orden')) {
                    $table->string('pre_orden')->nullable()->after('numero_odm');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'producto_nombre')) {
                    $table->string('producto_nombre')->nullable()->after('op');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'producto_id')) {
                    $table->unsignedBigInteger('producto_id')->nullable()->after('producto_nombre');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'forma_farmaceutica')) {
                    $table->string('forma_farmaceutica')->nullable()->after('tipo_producto');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'tamano_lote')) {
                    $table->decimal('tamano_lote', 12, 3)->nullable()->after('lote');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'fecha_fabricacion')) {
                    $table->string('fecha_fabricacion', 10)->nullable()->after('fecha_creacion');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'fecha_vencimiento')) {
                    $table->string('fecha_vencimiento', 10)->nullable()->after('fecha_fabricacion');
                }

                // Batch Record & Archivo Físico
                if (!Schema::hasColumn('maquila_production_orders', 'fecha_llegada_br')) {
                    $table->date('fecha_llegada_br')->nullable()->after('fecha_envio_maquila');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'total_producto_terminado_fabricado')) {
                    $table->decimal('total_producto_terminado_fabricado', 12, 3)->nullable()->after('fecha_llegada_br');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'rendimiento_real')) {
                    $table->decimal('rendimiento_real', 8, 2)->nullable()->after('total_producto_terminado_fabricado');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'posicion_archivo_fisico')) {
                    $table->string('posicion_archivo_fisico')->nullable()->after('rendimiento_real');
                }

                // Revisión Director Técnico & Producción
                if (!Schema::hasColumn('maquila_production_orders', 'estado_br_dt')) {
                    $table->string('estado_br_dt', 20)->nullable()->after('posicion_archivo_fisico'); // ABIERTO / CERRADO
                }
                if (!Schema::hasColumn('maquila_production_orders', 'comentario_dt')) {
                    $table->text('comentario_dt')->nullable()->after('estado_br_dt');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'fecha_revision_dt')) {
                    $table->timestamp('fecha_revision_dt')->nullable()->after('comentario_dt');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'usuario_dt_id')) {
                    $table->unsignedBigInteger('usuario_dt_id')->nullable()->after('fecha_revision_dt');
                }

                // Revisión Calidad (QA) & Liberación
                if (!Schema::hasColumn('maquila_production_orders', 'certificado_fisicoquimico')) {
                    $table->string('certificado_fisicoquimico', 20)->nullable()->after('usuario_dt_id'); // SI, NO, NO_APLICA
                }
                if (!Schema::hasColumn('maquila_production_orders', 'certificado_microbiologico')) {
                    $table->string('certificado_microbiologico', 20)->nullable()->after('certificado_fisicoquimico');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'certificado_endotoxinas')) {
                    $table->string('certificado_endotoxinas', 20)->nullable()->after('certificado_microbiologico');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'liberar_br')) {
                    $table->boolean('liberar_br')->default(false)->after('certificado_endotoxinas');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'fecha_liberacion_br')) {
                    $table->date('fecha_liberacion_br')->nullable()->after('liberar_br');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'estado_br_calidad')) {
                    $table->string('estado_br_calidad', 20)->nullable()->after('fecha_liberacion_br'); // ABIERTO / CERRADO
                }
                if (!Schema::hasColumn('maquila_production_orders', 'observaciones_calidad')) {
                    $table->text('observaciones_calidad')->nullable()->after('estado_br_calidad');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'usuario_calidad_id')) {
                    $table->unsignedBigInteger('usuario_calidad_id')->nullable()->after('observaciones_calidad');
                }
            });

            // Cambiar columna estado a string para permitir estados ampliados
            try {
                DB::statement('ALTER TABLE maquila_production_orders ALTER COLUMN estado TYPE VARCHAR(60)');
            } catch (\Throwable $e) {
                // SQLite or MySQL alternative
                try {
                    DB::statement('ALTER TABLE maquila_production_orders MODIFY estado VARCHAR(60)');
                } catch (\Throwable $ex) {}
            }
        }

        // 2. Modificaciones a maquila_deliveries
        if (Schema::hasTable('maquila_deliveries')) {
            Schema::table('maquila_deliveries', function (Blueprint $table) {
                if (!Schema::hasColumn('maquila_deliveries', 'numero_factura')) {
                    $table->string('numero_factura')->nullable()->after('numero_remision_factura');
                }
                if (!Schema::hasColumn('maquila_deliveries', 'esm')) {
                    $table->string('esm')->nullable()->after('numero_factura');
                }
                if (!Schema::hasColumn('maquila_deliveries', 'tipo_entrega')) {
                    $table->string('tipo_entrega', 20)->default('PARCIAL')->after('cantidad_recibida'); // PARCIAL, TOTAL
                }
                if (!Schema::hasColumn('maquila_deliveries', 'observaciones')) {
                    $table->text('observaciones')->nullable()->after('tipo_entrega');
                }
            });
        }

        // 3. Modificaciones a maquila_items
        if (Schema::hasTable('maquila_items')) {
            Schema::table('maquila_items', function (Blueprint $table) {
                if (!Schema::hasColumn('maquila_items', 'forma_farmaceutica')) {
                    $table->string('forma_farmaceutica')->nullable()->after('descripcion_producto');
                }
                if (!Schema::hasColumn('maquila_items', 'esm')) {
                    $table->string('esm')->nullable()->after('sdm');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};
