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
        // 1. Tabla de Ubicaciones Físicas de Batch Records en Archivo 3D
        if (!Schema::hasTable('batch_record_archive_locations')) {
            Schema::create('batch_record_archive_locations', function (Blueprint $table) {
                $table->id();
                $table->string('rack', 20)->default('RACK 1'); // RACK 1, RACK 2, RACK 3, RACK 4
                $table->unsignedInteger('nivel')->default(1); // 1, 2, 3, 4
                $table->unsignedInteger('archivador_numero'); // Número del archivador (1..42, 43..84, etc.)
                $table->string('cara', 20)->default('VISIBLE'); // VISIBLE (Impares) o POSTERIOR (Pares)
                $table->unsignedInteger('slot')->default(1); // 1, 2, 3, 4 (4 batch por archivador)
                $table->string('lote')->nullable()->index();
                $table->string('op_number')->nullable();
                $table->string('producto_nombre')->nullable();
                $table->string('tipo_origen', 30)->default('PLANTA'); // PLANTA o MAQUILA
                $table->unsignedBigInteger('production_order_id')->nullable();
                $table->unsignedBigInteger('maquila_production_order_id')->nullable();
                $table->date('fecha_archivo')->nullable();
                $table->text('notas')->nullable();
                $table->timestamps();

                $table->unique(['rack', 'nivel', 'archivador_numero', 'slot'], 'br_archive_slot_unique');
            });
        }

        // 2. Columna posicion_archivo_fisico en production_orders si no existe
        if (Schema::hasTable('production_orders')) {
            Schema::table('production_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('production_orders', 'posicion_archivo_fisico')) {
                    $table->string('posicion_archivo_fisico')->nullable()->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_record_archive_locations');
    }
};
