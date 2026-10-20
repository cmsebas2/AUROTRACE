<?php

/**
 * ============================================================================
 * COMANDO ARTISAN: CleanupProductionOrders (`php artisan app:cleanup-ops`)
 * ============================================================================
 * ¿QUÉ HACE ESTE ARCHIVO? (Explicación "For Dummies")
 * Este comando de mantenimiento por consola limpia registros de Órdenes de Producción
 * que hayan quedado en estado 'ANULADO' o corruptos (sin producto o sin presentaciones),
 * registrando la acción y limpiando primero sus tablas hijas para evitar errores de
 * llaves foráneas.
 * ============================================================================
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionOrder;

class CleanupProductionOrders extends Command
{
    /**
     * Nombre y firma del comando en consola.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-ops {--force : Ejecutar sin pedir confirmación interactiva}';

    /**
     * Descripción del comando.
     *
     * @var string
     */
    protected $description = 'Limpia la tabla de Órdenes de Producción eliminando registros huérfanos o anulados.';

    /**
     * Ejecuta el comando de limpieza.
     */
    public function handle()
    {
        $this->info('Iniciando limpieza de Órdenes de Producción...');

        // 1. Eliminar OPs en estado ANULADO
        $anuladas = ProductionOrder::where('status', 'ANULADO')->get();
        $countAnuladas = 0;
        foreach ($anuladas as $op) {
            $this->purgeOrder($op);
            $countAnuladas++;
        }
        $this->info("Se eliminaron {$countAnuladas} órdenes anuladas.");

        // 2. Eliminar OPs sin presentaciones y sin avance operativo (huérfanas/incompletas)
        $huerfanas = ProductionOrder::doesntHave('opPresentations')
            ->whereIn('status', ['OP_CREADA', 'OP CREADA', 'PENDIENTE', 'ANULADO'])
            ->get();
        $countHuerfanas = 0;
        foreach ($huerfanas as $op) {
            $this->purgeOrder($op);
            $countHuerfanas++;
        }
        $this->info("Se eliminaron {$countHuerfanas} órdenes sin presentaciones (incompletas).");

        // 3. Eliminar OPs sin producto asociado (si existieran por fallos de FK)
        $sinProducto = ProductionOrder::doesntHave('product')->get();
        $countSinProducto = 0;
        foreach ($sinProducto as $op) {
            $this->purgeOrder($op);
            $countSinProducto++;
        }

        $this->info("Se eliminaron {$countSinProducto} órdenes sin producto asociado.");
        $this->info('Proceso de limpieza completado.');
    }

    /**
     * Limpia las relaciones dependientes antes de eliminar la OP.
     */
    protected function purgeOrder(ProductionOrder $op): void
    {
        if ($op->dispensing) {
            $op->dispensing->dispensingDetails()->delete();
            $op->dispensing()->delete();
        }
        $op->packagingWeightControls()->delete();
        $op->packagingResult()->delete();
        $op->opPresentations()->delete();
        $op->lineClearances()->delete();
        $op->opMaterialReconciliations()->delete();
        $op->manufacturingExecutions()->delete();
        $op->delete();
    }
}
