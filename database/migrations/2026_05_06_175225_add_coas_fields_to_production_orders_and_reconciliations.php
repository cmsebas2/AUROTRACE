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
        Schema::table('production_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('coas_realizado_id')->nullable();
            $table->string('coas_realizado_por')->nullable();
            $table->timestamp('coas_realizado_at')->nullable();
            $table->unsignedBigInteger('coas_aprobado_id')->nullable();
            $table->string('coas_aprobado_por')->nullable();
            $table->timestamp('coas_aprobado_at')->nullable();
            $table->text('coas_observaciones')->nullable();
        });

        Schema::table('op_material_reconciliations', function (Blueprint $table) {
            $table->string('n_analisis')->nullable();
            $table->date('fecha_vencimiento_coa')->nullable();
            $table->string('coa_pdf_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn([
                'coas_realizado_id', 'coas_realizado_por', 'coas_realizado_at',
                'coas_aprobado_id', 'coas_aprobado_por', 'coas_aprobado_at',
                'coas_observaciones'
            ]);
        });

        Schema::table('op_material_reconciliations', function (Blueprint $table) {
            $table->dropColumn(['n_analisis', 'fecha_vencimiento_coa', 'coa_pdf_path']);
        });
    }
};
