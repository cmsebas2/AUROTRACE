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
        if (!Schema::hasTable('batch_record_destructions')) {
            Schema::create('batch_record_destructions', function (Blueprint $table) {
                $table->id();
                $table->string('acta_numero', 100)->unique();
                $table->date('fecha_destruccion');
                $table->string('metodo_destruccion', 150)->default('INCINERACIÓN / TRITURACIÓN CERTIFICADA');
                $table->text('motivo');
                $table->text('observaciones')->nullable();
                $table->integer('cantidad_lotes')->default(0);
                $table->json('lotes_detalle')->nullable();
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->string('usuario_nombre', 150)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('maquila_production_orders')) {
            Schema::table('maquila_production_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('maquila_production_orders', 'acta_destruccion_id')) {
                    $table->unsignedBigInteger('acta_destruccion_id')->nullable();
                }
                if (!Schema::hasColumn('maquila_production_orders', 'acta_destruccion_numero')) {
                    $table->string('acta_destruccion_numero', 100)->nullable();
                }
                if (!Schema::hasColumn('maquila_production_orders', 'fecha_destruccion_ejecutada')) {
                    $table->date('fecha_destruccion_ejecutada')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('maquila_production_orders')) {
            Schema::table('maquila_production_orders', function (Blueprint $table) {
                foreach (['acta_destruccion_id', 'acta_destruccion_numero', 'fecha_destruccion_ejecutada'] as $col) {
                    if (Schema::hasColumn('maquila_production_orders', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('batch_record_destructions');
    }
};
