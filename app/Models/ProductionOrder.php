<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditableTrait;
use App\Traits\SignatureTrait;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\ProductionOrderObserver;

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
        'realizado_at' => 'datetime',
        'verificado_at' => 'datetime',
        'manufacturing_date' => 'date',
        'expiration_date' => 'date',
        'destruction_date' => 'date',
        'codificado_elaborado_at' => 'datetime',
        'codificado_aprobado_at' => 'datetime',
        'coas_realizado_at' => 'datetime',
        'coas_aprobado_at' => 'datetime',
        'bulk_size_kg' => 'decimal:2',
        'theoretical_kg' => 'decimal:2',
        'actual_kg' => 'decimal:2',
        'yield_percentage' => 'decimal:2',
    ];

    /**
     * Resuelve el enlace de ruta de forma segura para PostgreSQL y MySQL,
     * admitiendo tanto IDs numéricos como strings de lote o número de OP.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return $this->where($field, $value)->first();
        }

        if (is_numeric($value)) {
            return $this->where('id', $value)->orWhere('lote', (string)$value)->first()
                ?? $this->where('op_number', (string)$value)->first();
        }

        return $this->where('lote', $value)->orWhere('op_number', $value)->first();
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['COMPLETADO', 'ANULADO']);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function opPresentations()
    {
        return $this->hasMany(OpPresentation::class);
    }

    public function lineClearances()
    {
        return $this->hasMany(LineClearance::class, 'production_order_id');
    }

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
            'LIBERADO'          => 'Producto Liberado',
            'RECHAZADO'         => 'Producto Rechazado',
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    public function getCurrentActionAttribute()
    {
        $status = $this->status;
        
        $actions = [
            'OP CREADA' => [
                'route' => route('op.ajuste_activos', $this->lote),
                'label' => 'AJUSTE ACTIVOS',
                'next'  => 'A4PPR0007'
            ],
            'PROGRAMADA' => [
                'route' => route('op.ajuste_activos', $this->lote),
                'label' => 'AJUSTE ACTIVOS',
                'next'  => 'A4PPR0007'
            ],
            'PENDIENTE' => [
                'route' => route('op.ajuste_activos', $this->lote),
                'label' => 'AJUSTE ACTIVOS',
                'next'  => 'A4PPR0007'
            ],
            // ACTO 1: OP generada
            'OP_CREADA' => [
                'route' => route('op.ajuste_activos', $this->lote),
                'label' => 'AJUSTAR ACTIVOS',
                'next'  => 'Firma DT (A4PPR0007)'
            ],
            // ACTO 2: Ajuste realizado por DT
            'AJ_CREADO' => [
                'route' => route('op.verificar_ajuste', $this->lote),
                'label' => 'VERIFICAR AJUSTE',
                'next'  => 'Firma QA (A4PPR0007-V)'
            ],
            'AJ_VERIFICADO' => [
                'route' => route('op.verificar_final', $this->lote),
                'label' => 'VERIFICAR OP',
                'next'  => 'Cierre A3PPR0007'
            ],
            'AJUSTE REALIZADO' => [
                'route' => route('batch.despeje', $this->lote),
                'label' => 'DESPEJE LÍNEA',
                'next'  => 'Inicio de Pesaje'
            ],
            'EN PRODUCCIÓN' => [
                'route' => route('batch.fabricacion', $this->lote),
                'label' => 'PRODUCCIÓN',
                'next'  => 'Controles en Proceso'
            ],
            'MANUFACTURA' => [
                'route' => route('batch.fabricacion', $this->lote),
                'label' => 'PRODUCCIÓN',
                'next'  => 'Controles en Proceso'
            ],
            'FINALIZADO' => [
                'route' => route('batch.conciliacion', $this->lote),
                'label' => 'CONCILIACIÓN',
                'next'  => 'Cierre de Orden'
            ],
            'ACONDICIONAMIENTO' => [
                'route' => route('batch.conciliacion', $this->lote),
                'label' => 'CONCILIACIÓN',
                'next'  => 'Cierre de Orden'
            ],
            // FASE DOCUMENTAL:
            'OP_VERIFICADA' => [
                'route' => route('op.solicitud_codificado', $this->lote),
                'label' => 'SOLICITUD CODIFICADO',
                'next'  => 'Elaboración A6PPR0007'
            ],
            'COD_CREADO' => [
                'route' => route('op.aprobar_codificado', $this->lote),
                'label' => 'APROBAR CODIFICADO',
                'next'  => 'Cierre A6PPR0007'
            ],
            'COD_VERIFICADO' => [
                'route' => route('op.coas', $this->lote),
                'label' => 'CARGAR COAS',
                'next'  => 'Aprobación Documental'
            ],
            'COA_CREADO' => [
                'route' => route('op.coas', $this->lote),
                'label' => 'APROBAR COAS',
                'next'  => 'Cierre Documental'
            ],
            'COA_VERIFICADO' => [
                'route' => route('batch.despeje', $this->lote),
                'label' => 'DESPEJE LÍNEA',
                'next'  => 'Dispensación'
            ],
        ];

        return $actions[$status] ?? [
            'route' => route('batch.despeje', $this->lote),
            'label' => 'CONTINUAR',
            'next'  => 'Procesar'
        ];
    }

    public function opMaterialReconciliations()
    {
        return $this->hasMany(OpMaterialReconciliation::class, 'production_order_id');
    }

    public function dispensing()
    {
        return $this->hasOne(Dispensing::class);
    }

    public function manufacturingExecutions()
    {
        return $this->hasMany(ManufacturingExecution::class, 'production_order_id');
    }

    /**
     * SEGURIDAD BPM: Verifica si un paso o ingrediente ya está firmado.
     */
    public function pasoEstaFirmado($stepId, $ingredientId = null)
    {
        return $this->manufacturingExecutions()
            ->where('plan_step_id', $stepId)
            ->where('plan_step_ingredient_id', $ingredientId)
            ->whereNotNull('signed_at')
            ->exists();
    }

    public function packagingResult()
    {
        return $this->hasOne(BatchPackagingResult::class, 'production_order_id');
    }

    public function packagingWeightControls()
    {
        return $this->hasMany(BatchPackagingWeightControl::class, 'production_order_id');
    }

    public function realizadoPorUser()
    {
        return $this->belongsTo(User::class, 'realizado_id');
    }

    public function verificadoPorUser()
    {
        return $this->belongsTo(User::class, 'verificado_id');
    }

    /**
     * RELACIÓN: Puente con el diccionario de procesos jerárquicos (Nivel 4).
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'status', 'status_key');
    }

    /**
     * ACCESSOR HÍBRIDO: Retorna la ubicación real del lote en 3 niveles.
     * (Proceso > Subproceso > Actividad)
     */
    public function getFaseVisualAttribute()
    {
        // 1. Intentar resolver por status_key (Fase Documental o Finalización)
        $activity = $this->activity;

        // 2. LÓGICA HÍBRIDA PARA MANUFACTURA:
        // Si no hay mapeo o el proceso es Manufactura, deducimos por tablas físicas.
        if ($this->status === 'MANUFACTURA' || $this->status === 'ACONDICIONAMIENTO' || ($activity && $activity->subProcess->process->name === 'Manufactura')) {
            
            // a. Conciliación (Paso Final de Manufactura)
            if ($this->opMaterialReconciliations()->exists()) {
                return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => 'Conciliación Materiales',
                    'actividad'  => 'Cuadre de Mermas/Rendimientos'
                ];
            }

            // b. Acondicionado / Empaque
            if ($this->packagingResult()->exists() || $this->packagingWeightControls()->exists()) {
                 return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => 'Acondicionado',
                    'actividad'  => 'Realizar Acondicionado'
                ];
            }

            // c. Fabricación (Ejecución de pasos)
            if ($this->manufacturingExecutions()->exists()) {
                return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => 'Fabricación',
                    'actividad'  => 'Realizar Fabricación'
                ];
            }

            // d. Dispensación (Pesaje de materias primas)
            if ($this->dispensing()->exists()) {
                return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => 'Dispensación',
                    'actividad'  => 'Realizar Dispensación'
                ];
            }

            // e. Despejes de Línea (Detecta el área del último despeje)
            $lastClearance = $this->lineClearances()->latest()->first();
            if ($lastClearance) {
                $areaMap = [
                    'dispensacion' => 'Dispensación',
                    'fabricacion'  => 'Fabricación',
                    'envase'       => 'Envase',
                    'codificado'   => 'Codificado Físico',
                    'acondicionado'=> 'Acondicionado',
                    'empaque'      => 'Acondicionado',
                ];
                $subName = $areaMap[strtolower($lastClearance->area)] ?? 'Fabricación';
                return [
                    'proceso'    => 'Manufactura',
                    'subproceso' => $subName,
                    'actividad'  => 'Despeje de Línea'
                ];
            }

            // f. Default Manufactura
            return [
                'proceso'    => 'Manufactura',
                'subproceso' => 'Verificación Documental',
                'actividad'  => 'Confirmación de EBR Teórico'
            ];
        }

        // 3. Retornar jerarquía desde la BD para fases no-manufactura
        if ($activity) {
            return [
                'proceso'    => $activity->subProcess->process->name,
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

