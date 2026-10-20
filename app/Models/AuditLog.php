<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\AuditLogObserver;

/**
 * ============================================================================
 * BITÁCORA INMUTABLE DE AUDITORÍA (21 CFR Part 11) - AuditLog
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Según los Artículos 1 y 4 de GEMINI.md, esta tabla es la memoria inmutable
 * de todo lo que sucede en AUROTRACE (inicios de sesión, firmas electrónicas,
 * cambios en órdenes de producción, alertas de desviación y liberaciones QA).
 *
 * Está protegida por `AuditLogObserver`: ningún registro puede ser editado ni borrado.
 * Además, incluye métodos traductores (`getHumanReadableValues`, `getHumanDescriptionAttribute`)
 * para convertir datos técnicos JSON en explicaciones claras para inspectores e ICA.
 * ============================================================================
 */
#[ObservedBy([AuditLogObserver::class])]
class AuditLog extends Model
{
    protected $guarded = [];

    // Los registros de auditoría solo tienen fecha de creación (created_at), nunca se actualizan.
    const UPDATED_AT = null;

    /**
     * Diccionario de Traducción Técnica a Lenguaje Industrial.
     */
    protected static $fieldMap = [
        'status'                    => 'Estado de la Orden',
        'expiration_date'           => 'Fecha de Vencimiento',
        'theoretical_kg'            => 'Cantidad Teórica (KG)',
        'product_id'                => 'Producto',
        'lote'                      => 'Lote',
        'bulk_size_kg'              => 'Tamaño de Lote (KG)',
        'realizado_id'              => 'Firmante Técnico',
        'verificado_id'             => 'Firmante QA',
        'realizado_at'              => 'Sello Técnico (Hora)',
        'verificado_at'             => 'Sello QA (Hora)',
        'actual_kg'                 => 'Cantidad Real (KG)',
        'yield_percentage'          => 'Rendimiento (%)',
        'final_yield_percentage'    => 'Rendimiento Final (%)',
        'estado_anterior'           => 'Estado Anterior',
        'creado_el'                 => 'Fecha de Creación',
        'autorizado_por'            => 'Eliminación Autorizada Por',
        'producto'                  => 'Nombre del Producto',
        'cantidad'                  => 'Tamaño Total (Snapshot)',
        'formula_nombre'            => 'Fórmula (Nombre)',
        'formula_version'           => 'Fórmula (Versión)',
        'batch_size_kg'             => 'Tamaño Lote (KG)',
        'etapa_nombre'              => 'Etapa de Proceso',
        'etapa_numero'              => 'N° Etapa',
        'valor_teorico'             => 'Valor Teórico',
        'valor_real'                => 'Valor Real',
        'dentro_de_rango'           => 'Dentro de Rango',
        'limite_min'                => 'Límite Mínimo',
        'limite_max'                => 'Límite Máximo',
        'materia_prima'             => 'Materia Prima',
        'peso_teorico_kg'           => 'Peso Teórico (KG)',
        'peso_real_kg'              => 'Peso Real (KG)',
        'tolerancia_pct'            => 'Tolerancia (%)',
        'balanza_id'                => 'ID Balanza',
        'calibracion_vigente'       => 'Calibración Vigente',
        'calibracion_vence'         => 'Vencimiento Calibración',
        'decision'                  => 'Decisión de Liberación',
        'checklist_items_total'     => 'Ítems de Checklist (Total)',
        'checklist_items_conformes' => 'Ítems Conformes',
        'analisis_fisicoquimico'    => 'Análisis Fisicoquímico',
        'analisis_microbiologico'   => 'Análisis Microbiológico',
        'resultado'                 => 'Resultado',
        'intento_numero'            => 'N° de Intento',
        'motivo_bloqueo'            => 'Motivo de Bloqueo',
        'tipo_cierre'               => 'Tipo de Cierre de Sesión',
    ];

    /** Usuario principal asociado al evento de auditoría. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Usuario que digitó la firma en la terminal. */
    public function signerUser()
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    /** Usuario en nombre de quien se ejecutó la firma o verificación. */
    public function onBehalfOfUser()
    {
        return $this->belongsTo(User::class, 'on_behalf_of_id');
    }

    /**
     * Traduce un JSON de cambios a un arreglo asociativo legible para auditores.
     */
    public function getHumanReadableValues($valuesJson)
    {
        if (!$valuesJson) {
            return null;
        }
        $values = json_decode($valuesJson, true);
        if (!is_array($values)) {
            return $valuesJson;
        }

        $translated = [];
        foreach ($values as $key => $value) {
            $label = self::$fieldMap[$key] ?? ucwords(str_replace('_', ' ', $key));

            // Resolución de IDs a Nombres legibles
            if ($key === 'product_id' && is_numeric($value)) {
                $product = Product::find($value);
                $value = $product ? $product->name : "ID: $value";
            }

            if ($key === 'realizado_id' || $key === 'verificado_id') {
                $user = User::find($value);
                $value = $user ? $user->name : "ID: $value";
            }

            // Formateo de Fechas y Sellos de Tiempo
            if (is_string($value) && (str_contains($key, 'date') || str_contains($key, '_at'))) {
                try {
                    $parsed = \Carbon\Carbon::parse($value);
                    // Si es un sello de hora (_at), conservar fecha y hora completa para cumplimiento 21 CFR Part 11
                    $value = str_contains($key, '_at')
                        ? $parsed->format('Y-m-d H:i:s')
                        : $parsed->format('Y-m');
                } catch (\Exception $e) {
                    // Conservar valor original si no es parseable
                }
            }

            // Manejo de valores compuestos (arrays/objetos) para evitar TypeError en vistas Blade
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $translated[$label] = $value;
        }
        return $translated;
    }

    /**
     * Extrae la metadata adicional (`_metadata`) inyectada en `new_values`.
     */
    public function getMetadataAttribute()
    {
        if (!$this->new_values) {
            return null;
        }
        $values = json_decode($this->new_values, true);
        return $values['_metadata'] ?? null;
    }

    /**
     * Limpia `new_values` eliminando la clave `_metadata` para la visualización tabular.
     */
    public function getCleanNewValuesAttribute()
    {
        if (!$this->new_values) {
            return null;
        }
        $values = json_decode($this->new_values, true);
        if (is_array($values) && isset($values['_metadata'])) {
            unset($values['_metadata']);
        }
        return json_encode($values);
    }

    /**
     * Estilos visuales (color, fondo e ícono) según el tipo de acción auditada.
     */
    public function getActionStyleAttribute()
    {
        $action = mb_strtoupper($this->action ?? '', 'UTF-8');

        if (str_contains($action, 'CREA')) {
            return ['color' => '#4ade80', 'bg' => 'rgba(74,222,128,0.1)', 'icon' => '✦'];
        }
        if (str_contains($action, 'ACTUALIZA') || str_contains($action, 'EDIT') || str_contains($action, 'AJUSTE')) {
            return ['color' => '#fbbf24', 'bg' => 'rgba(251,191,36,0.1)', 'icon' => '✎'];
        }
        if (str_contains($action, 'APROBA') || str_contains($action, 'LIBERAD') || str_contains($action, 'VERIFICAD')) {
            return ['color' => '#60a5fa', 'bg' => 'rgba(96,165,250,0.1)', 'icon' => '✓✓'];
        }
        if (str_contains($action, 'RECHAZ') || str_contains($action, 'ELIMINA')) {
            return ['color' => '#f87171', 'bg' => 'rgba(248,113,113,0.1)', 'icon' => '✕'];
        }
        if (str_contains($action, 'FIRMA')) {
            return ['color' => '#c084fc', 'bg' => 'rgba(192,132,252,0.1)', 'icon' => '✍'];
        }
        if (str_contains($action, 'LOGIN') || str_contains($action, 'LOGOUT')) {
            return ['color' => '#94a3b8', 'bg' => 'rgba(148,163,184,0.1)', 'icon' => '👤'];
        }
        if (str_contains($action, 'ESTADO') || str_contains($action, 'CAMBIO')) {
            return ['color' => '#fb923c', 'bg' => 'rgba(251,146,60,0.1)', 'icon' => '↻'];
        }
        if (str_contains($action, 'ALERTA') || str_contains($action, 'BLOQUEO') || str_contains($action, 'FALLID')) {
            return ['color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)', 'icon' => '⚠'];
        }

        return ['color' => '#94a3b8', 'bg' => 'rgba(148,163,184,0.1)', 'icon' => '•'];
    }

    /**
     * Etiqueta visual (Badge) del módulo afectado por el registro.
     */
    public function getModuleBadgeAttribute()
    {
        $type = $this->model_type ?? '';
        $action = mb_strtoupper($this->action ?? '', 'UTF-8');
        $reason = mb_strtoupper($this->reason ?? '', 'UTF-8');

        if (str_contains($type, 'ProductionOrder') || str_contains($reason, 'OP ') || str_contains($reason, 'LOTE')) {
            if (str_contains($action, 'LIBERA')) {
                return ['name' => 'LIBERACION', 'color' => 'bg-blue-100 text-blue-800 border-blue-200'];
            }
            if (str_contains($action, 'CODIFICADO') || str_contains($action, 'EMPAQUE')) {
                return ['name' => 'ETIQUETADO', 'color' => 'bg-purple-100 text-purple-800 border-purple-200'];
            }
            return ['name' => 'ORDENES', 'color' => 'bg-blue-100 text-blue-800 border-blue-200'];
        }
        if (str_contains($type, 'Dispensing') || str_contains($reason, 'PESAJE') || str_contains($reason, 'DISPENS')) {
            return ['name' => 'DISPENSACION', 'color' => 'bg-orange-100 text-orange-800 border-orange-200'];
        }
        if (str_contains($type, 'User') || str_contains($action, 'LOGIN') || str_contains($action, 'LOGOUT') || str_contains($reason, 'SESIÓN')) {
            return ['name' => 'USUARIOS', 'color' => 'bg-slate-100 text-slate-800 border-slate-200'];
        }
        if (str_contains($type, 'Product') || str_contains($type, 'Formula')) {
            return ['name' => 'FORMULAS', 'color' => 'bg-emerald-100 text-emerald-800 border-emerald-200'];
        }
        if (str_contains($action, 'ALERTA') || str_contains($reason, 'DESVIACIÓN')) {
            return ['name' => 'DESVIACIONES', 'color' => 'bg-red-100 text-red-800 border-red-200'];
        }

        return ['name' => 'SISTEMA', 'color' => 'bg-gray-100 text-gray-800 border-gray-200'];
    }

    /**
     * Genera una narrativa forense en lenguaje humano explicando qué ocurrió en este evento.
     */
    public function getHumanDescriptionAttribute()
    {
        $action = mb_strtolower($this->action ?? '', 'UTF-8');
        $reason = $this->reason ?? '';
        $meta = $this->metadata;

        $entityName = "registro";
        $lote = null;
        $productName = "Producto No Identificado";

        if ($this->model_id) {
            if (str_contains((string) $this->model_type, 'ProductionOrder') || str_contains(mb_strtoupper($reason, 'UTF-8'), 'LOTE')) {
                $newValues = json_decode($this->new_values, true);
                $oldValues = json_decode($this->old_values, true);

                $lote = $newValues['lote'] ?? $oldValues['lote'] ?? null;
                $productId = $newValues['product_id'] ?? $oldValues['product_id'] ?? null;

                if (!$lote || !$productId) {
                    $op = ProductionOrder::find($this->model_id);
                    if ($op) {
                        $lote = $lote ?: $op->lote;
                        $productName = $op->product->name ?? $productName;
                    }
                } else {
                    $prod = Product::find($productId);
                    $productName = $prod ? $prod->name : $productName;
                }

                $entityName = "el lote " . ($lote ?: $this->model_id);
            }
        }

        // 1. Creación de OP
        if (str_contains($action, 'crea') && (str_contains($action, 'op') || str_contains((string) $this->model_type, 'ProductionOrder'))) {
            return "Generó el documento inicial de la Orden de Producción del producto " . ($productName ?: 'X') . " con lote " . ($lote ?: 'X') . ".";
        }

        // 2. Firmas Electrónicas
        if (str_contains($action, 'firma')) {
            $etapa = $meta['etapa_nombre'] ?? 'documento';
            return "Ejecutó firma electrónica de la etapa [$etapa] vinculada al $entityName.";
        }

        // 3. Liberaciones y Aprobaciones
        if (str_contains($action, 'libera') || str_contains($action, 'aproba')) {
            return "Dictaminó la conformidad y liberación final del $entityName.";
        }

        // 4. Ajustes y Ediciones
        if (str_contains($action, 'actualiza') || str_contains($action, 'ajuste') || str_contains($action, 'edita')) {
            $cleanOld = json_decode($this->old_values, true);
            $cleanNew = json_decode($this->clean_new_values, true);

            if (is_array($cleanOld) && is_array($cleanNew)) {
                foreach ($cleanNew as $k => $v) {
                    if (isset($cleanOld[$k]) && $cleanOld[$k] != $v) {
                        $field = self::$fieldMap[$k] ?? $k;
                        return "Modificó el parámetro [$field] en el $entityName. Justificación: " . ($reason ?: 'Ajuste operativo');
                    }
                }
            }
            return "Modificó parámetros técnicos en el $entityName.";
        }

        // 5. Fallback General
        if (!empty($reason)) {
            return $reason;
        }

        return "Realizó la actividad de " . $action . " sobre el $entityName.";
    }
}
