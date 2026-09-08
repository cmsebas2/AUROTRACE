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
        if (Schema::hasTable('maquila_production_orders')) {
            Schema::table('maquila_production_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('maquila_production_orders', 'fecha_destruccion_br')) {
                    $table->string('fecha_destruccion_br', 20)->nullable()->after('fecha_vencimiento');
                }
            });

            // Actualizar registros existentes calculando fecha_destruccion_br = fecha_vencimiento + 1 año
            try {
                $orders = DB::table('maquila_production_orders')
                    ->whereNotNull('fecha_vencimiento')
                    ->whereNull('fecha_destruccion_br')
                    ->get(['id', 'fecha_vencimiento']);

                foreach ($orders as $o) {
                    $venc = trim($o->fecha_vencimiento);
                    $destruccion = null;
                    if (preg_match('/^(\d{4})-(\d{2})$/', $venc, $m)) {
                        $destruccion = ((int)$m[1] + 1) . '-' . $m[2];
                    } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $venc, $m)) {
                        $destruccion = ((int)$m[1] + 1) . '-' . $m[2] . '-' . $m[3];
                    }
                    if ($destruccion) {
                        DB::table('maquila_production_orders')
                            ->where('id', $o->id)
                            ->update(['fecha_destruccion_br' => $destruccion]);
                    }
                }
            } catch (\Throwable $e) {
                // Continuar si la tabla aún no tiene registros o falla la consulta inicial
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('maquila_production_orders')) {
            Schema::table('maquila_production_orders', function (Blueprint $table) {
                if (Schema::hasColumn('maquila_production_orders', 'fecha_destruccion_br')) {
                    $table->dropColumn('fecha_destruccion_br');
                }
            });
        }
    }
};
