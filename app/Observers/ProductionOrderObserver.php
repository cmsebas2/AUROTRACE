<?php

namespace App\Observers;

namespace App\Observers;

use App\Models\ProductionOrder;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ProductionOrderObserver
{

    /**
     * Handle the ProductionOrder "updated" event.
     */
    public function updated(ProductionOrder $productionOrder): void
    {
        $old = $productionOrder->getOriginal();
        $new = $productionOrder->getDirty();

        $changesOld = [];
        $changesNew = [];

        foreach ($new as $key => $value) {
            // Ignorar timestamps
            if (in_array($key, ['created_at', 'updated_at'])) continue;
            
            $changesOld[$key] = $old[$key] ?? null;
            $changesNew[$key] = $value;
        }

        if (empty($changesNew)) return;

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'ACTUALIZACIÓN DE OP',
            'model_type' => ProductionOrder::class,
            'model_id' => $productionOrder->id,
            'old_values' => json_encode($changesOld),
            'new_values' => json_encode(array_merge($changesNew, [
                '_metadata' => [
                    'formula_nombre' => optional($productionOrder->product)->name,
                    'motivo' => $productionOrder->audit_reason ?? 'Modificación regular',
                ]
            ])),
            'reason' => "Modificación de parámetros técnicos para lote {$productionOrder->lote}.",
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Handle the ProductionOrder "deleted" event.
     */
    public function deleted(ProductionOrder $productionOrder): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'ELIMINACIÓN DE OP',
            'model_type' => ProductionOrder::class,
            'model_id' => $productionOrder->id,
            'old_values' => json_encode($productionOrder->getAttributes()),
            'reason' => "Eliminación de la OP Lote {$productionOrder->lote} por solicitud de usuario.",
            'ip_address' => Request::ip(),
        ]);
    }
}
