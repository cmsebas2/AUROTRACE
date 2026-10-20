<?php

namespace App\Observers;

use App\Models\ProductionOrder;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * ============================================================================
 * OBSERVADOR DE ÓRDENES DE PRODUCCIÓN - ProductionOrderObserver
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Este observador vigila automáticamente el modelo central `ProductionOrder` (OP).
 * Cada vez que una OP cambia de estado, recibe una firma o actualiza su rendimiento,
 * guarda automáticamente qué campos cambiaron (valor anterior vs. valor nuevo)
 * en la tabla `audit_logs`.
 * ============================================================================
 */
class ProductionOrderObserver
{
    /**
     * Se ejecuta automáticamente después de actualizar una Orden de Producción.
     */
    public function updated(ProductionOrder $productionOrder): void
    {
        $old = $productionOrder->getOriginal();
        $new = $productionOrder->getChanges() ?: $productionOrder->getDirty();

        $changesOld = [];
        $changesNew = [];

        foreach ($new as $key => $value) {
            // Ignorar marcas de tiempo automáticas para registrar solo cambios de negocio
            if (in_array($key, ['created_at', 'updated_at'])) {
                continue;
            }

            $changesOld[$key] = $old[$key] ?? null;
            $changesNew[$key] = $value;
        }

        if (empty($changesNew)) {
            return;
        }

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'ACTUALIZACIÓN DE OP',
            'model_type' => ProductionOrder::class,
            'model_id'   => $productionOrder->id,
            'old_values' => json_encode($changesOld),
            'new_values' => json_encode(array_merge($changesNew, [
                '_metadata' => [
                    'formula_nombre' => optional($productionOrder->product)->name,
                    'motivo'         => $productionOrder->audit_reason ?? 'Modificación regular',
                ],
            ])),
            'reason'     => $productionOrder->audit_reason ?? "Modificación de parámetros técnicos para lote {$productionOrder->lote}.",
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Se ejecuta automáticamente si se elimina una Orden de Producción.
     */
    public function deleted(ProductionOrder $productionOrder): void
    {
        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'ELIMINACIÓN DE OP',
            'model_type' => ProductionOrder::class,
            'model_id'   => $productionOrder->id,
            'old_values' => json_encode($productionOrder->getAttributes()),
            'reason'     => $productionOrder->audit_reason ?? "Eliminación de la OP Lote {$productionOrder->lote} por solicitud de usuario.",
            'ip_address' => Request::ip(),
        ]);
    }
}
