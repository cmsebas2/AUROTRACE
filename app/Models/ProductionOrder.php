<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditableTrait;
use App\Traits\SignatureTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\ProductionOrderObserver;

/**
 * ============================================================================
 * MODELO CENTRAL: ORDEN DE PRODUCCIÓN (OP) - ProductionOrder
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Según el Artículo 1 de GEMINI.md, esta clase es el "corazón" de AUROTRACE.
 * Representa un lote físico que se fabrica en la planta (ej: Lote "0010526").
 *
 * Ciclo de vida del lote (`status`):
 * 1. Fase Documental:
 *    - `OP_CREADA`       -> Se emitió la orden inicial (Formato A3PPR0007).
 *    - `AJ_CREADO`       -> Dirección Técnica calculó el ajuste de principios activos (A4PPR0007).
 *    - `AJ_VERIFICADO`   -> Calidad (QA) verificó el ajuste de activos.
 *    - `OP_VERIFICADA`   -> La OP quedó doblemente firmada y oficializada.
 *    - `COD_CREADO` / `COD_VERIFICADO` -> Solicitud y aprobación de codificado de envases (A6PPR0007).
 *    - `COA_CREADO` / `COA_VERIFICADO` -> Carga y aprobación de Certificados de Análisis (COAs).
 * 2. Fase Operativa en Planta (EBR):
 *    - `MANUFACTURA`       -> Pasa por Despeje de Línea, Dispensación (Pesaje) y Fabricación.
 *    - `ACONDICIONAMIENTO` -> Pasa a Envasado, Control de Pesos y Conciliación de Materiales.
 * 3. Dictamen Final de Calidad:
 *    - `CUARENTENA` -> Bloqueo automático si el rendimiento (`final_yield_percentage`) sale del rango 90% - 110%.
 *    - `COMPLETADO` / `LIBERADO` -> Lote finalizado y liberado oficialmente.
 * ============================================================================
 */
#[ObservedBy([ProductionOrderObserver::class])]
class ProductionOrder extends Model
{
    use AuditableTrait, SignatureTrait;

    protected $fillable = [
        'op_number',
        'product_id',
        'lote',
        'bulk_size_kg',
        'unit',
        'manufacturing_date',
        'expiration_date',
        'destruction_date',
        'maquilador',
        'status',
        'realizado_por',
        'realizado_fecha',
        'verificado_por',
        'verificado_fecha',
        'theoretical_kg',
        'theoretical_units',
        'actual_kg',
        'actual_units',
        'yield_percentage',
        'final_yield_percentage',
        'posicion_archivo_fisico',
        'realizado_id',
        'realizado_at',
        'verificado_id',
        'verificado_at',
        'codificado_elaborado_id',
        'codificado_elaborado_por',
        'codificado_elaborado_at',
        'codificado_aprobado_id',
        'codificado_aprobado_por',
        'codificado_aprobado_at',
        'codificado_observaciones',
        'coas_realizado_id',
        'coas_realizado_por',
        'coas_realizado_at',
        'coas_aprobado_id',
        'coas_aprobado_por',
        'coas_aprobado_at',
        'coas_observaciones',
    ];

    protected $casts = [
        'realizado_at'           => 'datetime',
        'verificado_at'          => 'datetime',
        'manufacturing_date'     => 'date',
        'expiration_date'        => 'date',
        'destruction_date'       => 'date',
        'codificado_elaborado_at'=> 'datetime',
        'codificado_aprobado_at' => 'datetime',
        'coas_realizado_at'      => 'datetime',
        'coas_aprobado_at'       => 'datetime',
        'bulk_size_kg'           => 'decimal:2',
        'theoretical_kg'         => 'decimal:2',
        'actual_kg'              => 'decimal:2',
        'yield_percentage'       => 'decimal:2',
        'final_yield_percentage' => 'decimal:2',
    ];

    /**
     * Resuelve el enlace de ruta (Route Model Binding) de forma segura para PostgreSQL y MySQL,
     * permitiendo buscar una OP tanto por su ID numérico como por su código de lote o número de OP.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return $this->where($field, $value)->first();
        }

        if (is_numeric($value)) {
            return $this->where('id', $value)->orWhere('lote', (string) $value)->first()
                ?? $this->where('op_number', (string) $value)->first();
        }

        return $this->where('lote', $value)->orWhere('op_number', $value)->first();
    }

    /**
     * Filtra únicamente las órdenes que siguen activas en planta.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['COMPLETADO', 'ANULADO']);
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES CON OTRAS TABLAS DEL EBR
    |--------------------------------------------------------------------------
    */

    /** Producto maestro que se está fabricando en esta orden. */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Presentaciones comerciales (ej: Frasco x 100 mL) asignadas a esta OP. */
    public function opPresentations()
    {
        return $this->hasMany(OpPresentation::class);
    }

    /** Registros de despeje de línea por área (Dispensación, Fabricación, Envasado, etc.). */
    public function lineClearances()
    {
        return $this->hasMany(LineClearance::class, 'production_order_id');
    }

    /** Conciliación de materias primas y materiales de envase/empaque de la OP. */
    public function opMaterialReconciliations()
    {
        return $this->hasMany(OpMaterialReconciliation::class, 'production_order_id');
    }

    /**
     * Alias de compatibilidad para vistas que invocan `$op->materialReconciliations()`.
     */
    public function materialReconciliations()
    {
        return $this->opMaterialReconciliations();
    }

    /** Registro general de dispensación y pesaje de materias primas. */
    public function dispensing()
    {
        return $this->hasOne(Dispensing::class);
    }

    /** Ejecuciones paso a paso del instructivo de manufactura en planta. */
    public function manufacturingExecutions()
    {
        return $this->hasMany(ManufacturingExecution::class, 'production_order_id');
    }

    /** Resultado consolidado de la etapa de envasado (unidades obtenidas, peso promedio, etc.). */
    public function packagingResult()
    {
        return $this->hasOne(BatchPackagingResult::class, 'production_order_id');
    }

    /** Controles de peso individuales tomados durante el envasado. */
    public function packagingWeightControls()
    {
        return $this->hasMany(BatchPackagingWeightControl::class, 'production_order_id');
    }

    /** Usuario que elaboró/emitió la Orden de Producción. */
    public function realizadoPorUser()
    {
        return $this->belongsTo(User::class, 'realizado_id');
    }

    /** Usuario de Calidad / Dirección Técnica que verificó la Orden de Producción. */
    public function verificadoPorUser()
    {
        return $this->belongsTo(User::class, 'verificado_id');
    }

    /** Relación con el diccionario jerárquico de procesos (Nivel 4: Actividad). */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'status', 'status_key');
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO Y ATRIBUTOS CALCULADOS (ACCESSORS)
    |--------------------------------------------------------------------------
    */

    /**
     * SEGURIDAD BPM: Verifica si un paso o adición de ingrediente ya fue firmado electrónicamente.
     */
    public function pasoEstaFirmado($stepId, $ingredientId = null)
    {
        return $this->manufacturingExecutions()
            ->where('plan_step_id', $stepId)
            ->where('plan_step_ingredient_id', $ingredientId)
            ->whereNotNull('signed_at')
            ->exists();
    }

    /**
     * Traduce el código interno de `status` a una etiqueta amigable para mostrar en pantalla.
     */
    public function getFriendlyStatusAttribute()
    {
        $statusMap = [
            'OP_CREADA'         => 'Orden Emitida',
            'AJ_CREADO'         => 'Ajuste Elaborado',
            'AJ_VERIFICADO'     => 'Ajuste Auditado',
            'OP_VERIFICADA'     => 'Orden Oficializada',
            'COD_CREADO'        => 'Codificado Elaborado',
            'COD_VERIFICADO'    => 'Codificado Aprobado',
            'COA_CREADO'        => 'Protocolo Elaborado',
            'COA_VERIFICADO'    => 'Protocolo Aprobado',
            'MANUFACTURA'       => 'En Fabricación',
            'ACONDICIONAMIENTO' => 'En Empaque',
            'CUARENTENA'        => 'En Cuarentena',
            'COMPLETADO'        => 'Completado',
            'LIBERADO'          => 'Producto Liberado',
            'RECHAZADO'         => 'Producto Rechazado',
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    /**
     * Determina automáticamente cuál es el siguiente botón de acción y ruta según el estado actual de la OP.
     */
    public function getCurrentActionAttribute()
    {
        $status = $this->status;

        $actions = [
            'OP CREADA' => [
                'route' => route('op.ajuste_activos', $this->lote),
                'label' => 'AJUSTE ACTIVOS',
                'next'  => 'A4PPR0007',
            ],
            'PROGRAMADA' => [
                'route' => route('op.ajuste_activos', $this->lote),
                'label' => 'AJUSTE ACTIVOS',
                'next'  => 'A4PPR0007',
            ],
            'PENDIENTE' => [
                'route' => route('op.ajuste_activos', $this->lote),
                'label' => 'AJUSTE ACTIVOS',
                'next'  => 'A4PPR0007',
            ],
            // ACTO 1: OP generada
            'OP_CREADA' => [
                'route' => route('op.ajuste_activos', $this->lote),
                'label' => 'AJUSTAR ACTIVOS',
                'next'  => 'Firma DT (A4PPR0007)',
            ],
            // ACTO 2: Ajuste realizado por DT
            'AJ_CREADO' => [
                'route' => route('op.verificar_ajuste', $this->lote),
                'label' => 'VERIFICAR AJUSTE',
                'next'  => 'Firma QA (A4PPR0007-V)',
            ],
            'AJ_VERIFICADO' => [
                'route' => route('op.verificar_final', $this->lote),
                'label' => 'VERIFICAR OP',
                'next'  => 'Cierre A3PPR0007',
            ],
            'AJUSTE REALIZADO' => [
                'route' => route('batch.despeje', $this->lote),
                'label' => 'DESPEJE LÍNEA',
                'next'  => 'Inicio de Pesaje',
            ],
            'EN PRODUCCIÓN' => [
                'route' => route('batch.fabricacion', $this->lote),
                'label' => 'PRODUCCIÓN',
                'next'  => 'Controles en Proceso',
            ],
            'MANUFACTURA' => [
                'route' => route('batch.fabricacion', $this->lote),
                'label' => 'PRODUCCIÓN',
                'next'  => 'Controles en Proceso',
            ],
            'FINALIZADO' => [
                'route' => route('batch.conciliacion', $this->lote),
                'label' => 'CONCILIACIÓN',
                'next'  => 'Cierre de Orden',
            ],
            'ACONDICIONAMIENTO' => [
                'route' => route('batch.conciliacion', $this->lote),
                'label' => 'CONCILIACIÓN',
                'next'  => 'Cierre de Orden',
            ],
            // FASE DOCUMENTAL:
            'OP_VERIFICADA' => [
                'route' => route('op.solicitud_codificado', $this->lote),
                'label' => 'SOLICITUD CODIFICADO',
                'next'  => 'Elaboración A6PPR0007',
            ],
            'COD_CREADO' => [
                'route' => route('op.aprobar_codificado', $this->lote),
                'label' => 'APROBAR CODIFICADO',
                'next'  => 'Cierre A6PPR0007',
            ],
            'COD_VERIFICADO' => [
                'route' => route('op.coas', $this->lote),
                'label' => 'CARGAR COAS',
                'next'  => 'Aprobación Documental',
            ],
            'COA_CREADO' => [
                'route' => route('op.aprobar_coas', $this->lote),
                'label' => 'APROBAR COAS',
                'next'  => 'Cierre Documental',
            ],
            'COA_VERIFICADO' => [
                'route' => route('batch.despeje', $this->lote),
                'label' => 'DESPEJE LÍNEA',
                'next'  => 'Dispensación',
            ],
        ];

        return $actions[$status] ?? [
            'route' => route('batch.despeje', $this->lote),
            'label' => 'CONTINUAR',
            'next'  => 'Procesar',
        ];
    }

    /**
     * ACCESSOR HÍBRIDO: Retorna la ubicación real del lote en 3 niveles
     * (Proceso > Subproceso > Actividad) para los tableros de monitoreo.
     */
    public function getFaseVisualAttribute()
    {
        // 1. Intentar resolver por status_key (Fase Documental o Finalización)
        $activity = $this->activity;

        // 2. LÓGICA HÍBRIDA PARA MANUFACTURA:
        // Si no hay mapeo o el proceso es Manufactura, deducimos por tablas físicas.
        if ($this->status === 'MANUFACTURA' || $this->status === 'ACONDICIONAMIENTO' || ($activity && $activity->subProcess?->process?->name === 'Manufactura')) {

            // a. Conciliación (Paso Final de Manufactura)
            if ($this->opMaterialReconciliations()->whereNotNull('signed_at')->exists()) {
                return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => 'Conciliación Materiales',
                    'actividad'  => 'Cuadre de Mermas/Rendimientos',
                ];
            }

            // b. Acondicionado / Empaque
            if ($this->packagingResult()->exists() || $this->packagingWeightControls()->exists()) {
                return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => 'Acondicionado',
                    'actividad'  => 'Realizar Acondicionado',
                ];
            }

            // c. Fabricación (Ejecución de pasos)
            if ($this->manufacturingExecutions()->exists()) {
                return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => 'Fabricación',
                    'actividad'  => 'Realizar Fabricación',
                ];
            }

            // d. Dispensación (Pesaje de materias primas)
            if ($this->dispensing()->exists()) {
                return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => 'Dispensación',
                    'actividad'  => 'Realizar Dispensación',
                ];
            }

            // e. Despejes de Línea (Detecta el área del último despeje)
            $lastClearance = $this->lineClearances()->latest()->first();
            if ($lastClearance) {
                $areaMap = [
                    'dispensacion'  => 'Dispensación',
                    'fabricacion'   => 'Fabricación',
                    'envase'        => 'Envase',
                    'codificado'    => 'Codificado Físico',
                    'acondicionado' => 'Acondicionado',
                    'empaque'       => 'Acondicionado',
                ];
                $subName = $areaMap[strtolower($lastClearance->area)] ?? 'Fabricación';
                return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => $subName,
                    'actividad'  => 'Despeje de Línea',
                ];
            }

            // f. Default Manufactura
            return [
                'proceso'    => 'Manufactura',
                'subproceso' => 'Verificación Documental',
                'actividad'  => 'Confirmación de EBR Teórico',
            ];
        }

        // 3. Retornar jerarquía desde la BD para fases no-manufactura
        if ($activity && $activity->subProcess) {
            return [
                'proceso'    => $activity->subProcess->process?->name ?? 'Gestión Documental',
                'subproceso' => $activity->subProcess->name,
                'actividad'  => $activity->name,
            ];
        }

        // 4. Fallback absoluto en caso de estados no mapeados
        return [
            'proceso'    => 'Gestión Documental',
            'subproceso' => 'Pendiente',
            'actividad'  => $this->friendly_status,
        ];
    }
}

