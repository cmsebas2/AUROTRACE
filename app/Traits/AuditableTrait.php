<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait AuditableTrait
{
    /**
     * Boot the trait and register model events.
     * Laravel calls boot[TraitName]() automatically.
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

    public $audit_reason = null;

    /**
     * Create an audit log record.
     *
     * @param string $action
     */
    protected function auditLogAction($action)
    {
        $oldValues = null;
        $newValues = null;

        if ($action === 'actualizado') {
            // Evitamos redundancia: ProductionOrder ya tiene su propio Observer para actualizaciones
            if (get_class($this) === 'App\Models\ProductionOrder') {
                return;
            }

            // getChanges() tiene los campos que cambiaron después del save
            $newValues = $this->getChanges();
            
            // No guardamos nada si no hay cambios reales (evita duplicidad por timestamps)
            if (empty($newValues)) {
                return;
            }

            // Capturamos el valor original solo de lo que cambió
            $oldValues = array_intersect_key($this->getOriginal(), $newValues);
            
        } elseif ($action === 'creado') {
            // Evitamos que AuditableTrait registre la creación genérica para ProductionOrder
            // ya que el controlador hace un registro enriquecido (CREACION OP INTELIGENTE).
            if (get_class($this) === 'App\Models\ProductionOrder') {
                return;
            }
            $newValues = $this->getAttributes();
            
        } elseif ($action === 'eliminado') {
            $oldValues = $this->getOriginal();
        }

        // Limpieza de campos sensibles (opcional pero recomendado)
        $sensitive = ['password', 'remember_token', 'api_token'];
        if ($oldValues) {
            $oldValues = array_diff_key($oldValues, array_flip($sensitive));
        }
        if ($newValues) {
            $newValues = array_diff_key($newValues, array_flip($sensitive));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => Request::ip(),
            'reason' => $this->audit_reason ?? "Acción automática registrada por AuditableTrait",
        ]);
    }
}
