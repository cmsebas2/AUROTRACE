<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * ============================================================================
 * TRAIT DE AUDITORÍA AUTOMÁTICA DE MODELOS - AuditableTrait
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Cualquier Modelo (tabla) que incluya `use AuditableTrait;` queda conectado
 * automáticamente a la bitácora `audit_logs`.
 *
 * ¿Cómo funciona?
 * - Cuando se CREA un registro (`created`), guarda los valores iniciales.
 * - Cuando se ACTUALIZA (`updated`), compara el antes (`old_values`) y el después (`new_values`).
 * - Cuando se ELIMINA (`deleted`), guarda una copia de respaldo de lo que tenía antes de borrarse.
 *
 * Nota: `ProductionOrder` usa su propio `ProductionOrderObserver` especializado,
 * por lo que este trait lo salta para no duplicar registros.
 * ============================================================================
 */
trait AuditableTrait
{
    /**
     * Registra automáticamente los escuchadores de eventos de ciclo de vida del modelo.
     */
    public static function bootAuditableTrait()
    {
        static::created(function ($model) {
            $model->auditLogAction('creado');
        });

        static::updated(function ($model) {
            $model->auditLogAction('actualizado');
        });

        static::deleted(function ($model) {
            $model->auditLogAction('eliminado');
        });
    }

    /**
     * Motivo opcional que puede asignarse en memoria antes de guardar ($model->audit_reason = '...').
     */
    public $audit_reason = null;

    /**
     * Construye y almacena el registro de auditoría en `audit_logs`.
     *
     * @param string $action ('creado', 'actualizado', 'eliminado')
     */
    protected function auditLogAction($action)
    {
        // Evitamos redundancia: ProductionOrder ya cuenta con su propio ProductionOrderObserver
        if (get_class($this) === 'App\Models\ProductionOrder') {
            return;
        }

        $oldValues = null;
        $newValues = null;

        if ($action === 'actualizado') {
            // getChanges() contiene únicamente las columnas que cambiaron tras el save()
            $newValues = $this->getChanges();

            // Si no hubo cambios reales en columnas de negocio, no generamos ruido en el log
            if (empty($newValues)) {
                return;
            }

            // Capturamos el valor original únicamente de las columnas que cambiaron
            $oldValues = array_intersect_key($this->getOriginal(), $newValues);

        } elseif ($action === 'creado') {
            $newValues = $this->getAttributes();

        } elseif ($action === 'eliminado') {
            $oldValues = $this->getOriginal();
        }

        // Limpieza de campos sensibles para que jamás se guarden hashes o tokens en el log
        $sensitive = ['password', 'remember_token', 'api_token', 'signature_password'];
        if ($oldValues) {
            $oldValues = array_diff_key($oldValues, array_flip($sensitive));
        }
        if ($newValues) {
            $newValues = array_diff_key($newValues, array_flip($sensitive));
        }

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'model_type' => get_class($this),
            'model_id'   => $this->id,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => Request::ip(),
            'reason'     => $this->audit_reason ?? "Acción automática registrada por AuditableTrait",
        ]);
    }
}
