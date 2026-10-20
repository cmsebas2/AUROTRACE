<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * ============================================================================
 * GUARDIÁN DE INMUTABILIDAD DEL AUDIT TRAIL (Artículo 4 GEMINI.md)
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * En un sistema regulado bajo 21 CFR Part 11, el historial de auditoría (`audit_logs`)
 * es como una "caja negra" de avión: se pueden agregar nuevos eventos, pero NADIE
 * puede modificar ni borrar un registro existente.
 *
 * Si cualquier parte del código intenta hacer `$log->update(...)` o `$log->delete()`,
 * este observador detiene la operación inmediatamente lanzando una excepción.
 * ============================================================================
 */
class AuditLogObserver
{
    /**
     * Evento disparado tras crear un registro de auditoría.
     */
    public function created(Model $model): void
    {
        // Espacio reservado para notificaciones en tiempo real si se requieren en el futuro
    }

    /**
     * Bloquea cualquier intento de modificar un registro de auditoría existente.
     */
    public function updating(Model $model): void
    {
        if ($model instanceof AuditLog) {
            throw new \Exception('Los registros de Audit Log son inalterables (CFR 21 Parte 11)');
        }
    }

    /**
     * Bloquea cualquier intento de eliminar un registro de auditoría existente.
     */
    public function deleting(Model $model): void
    {
        if ($model instanceof AuditLog) {
            throw new \Exception('Los registros de Audit Log no pueden ser eliminados (CFR 21 Parte 11)');
        }
    }
}
