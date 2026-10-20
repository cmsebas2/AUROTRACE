<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\MaquilaProductionOrder;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * ============================================================================
 * CONTROLADOR DE GENEALOGÍA 360° Y LIBERACIÓN QA - GenealogyController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Este controlador muestra la "historia clínica completa" (Vista 360°) de cualquier lote:
 * 1. `index()`: Muestra el buscador de lotes.
 * 2. `showByBatch($op)`: Busca el lote primero en Planta Interna y, si no está,
 *    lo busca en Maquilas Externas / Lotes Históricos. Muestra la línea de tiempo
 *    de hitos (Milestones), los registros de pesaje, fabricación, envase y alertas.
 * 3. `release()`: Permite que Aseguramiento de Calidad (QA) libere oficialmente el lote
 *    mediante firma electrónica 21 CFR Part 11.
 * 4. `downloadPdf()`: Genera el reporte PDF de trazabilidad forense del lote.
 * ============================================================================
 */
class GenealogyController extends Controller
{
    protected $signatureService;

    public function __construct(SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Muestra el buscador principal de genealogía de lotes.
     */
    public function index()
    {
        return view('genealogy.search');
    }

    /**
     * Vista 360° del Lote (Soporta Planta Interna y Maquila/Lotes Históricos).
     */
    public function showByBatch($op)
    {
        $searchKey = trim((string) $op);

        // 1. Buscar primero en Órdenes de Producción de Planta Interna
        $plantOp = ProductionOrder::where('lote', $searchKey)->orWhere('op_number', $searchKey)->first();

        if (!$plantOp) {
            // 2. Si no está en planta, buscar en Órdenes de Maquila / Lotes Históricos
            $maquilaOp = MaquilaProductionOrder::where('lote', $searchKey)
                ->orWhere('op', $searchKey)
                ->first();

            if (!$maquilaOp) {
                $maquilaOp = MaquilaProductionOrder::where('lote', 'LIKE', "%{$searchKey}%")
                    ->orWhere('op', 'LIKE', "%{$searchKey}%")
                    ->orWhere('producto_nombre', 'LIKE', "%{$searchKey}%")
                    ->first();
            }

            if ($maquilaOp) {
                return redirect()->route('maquila.show', $maquilaOp->id);
            }

            return redirect('/genealogia')->with('error', "El lote u OP '{$searchKey}' no fue encontrado en los registros de Planta ni en Maquilas Históricas.");
        }

        $op = $plantOp;

        // Carga anticipada (Eager Loading) de todas las etapas del lote
        $op->load([
            'product',
            'dispensing.dispensingDetails.formulaIngredient',
            'manufacturingExecutions.planStep',
            'manufacturingExecutions.user',
            'manufacturingExecutions.qaUser',
            'packagingResult.user',
            'packagingResult.qaUser',
            'packagingWeightControls',
            'lineClearances.realizadoPor',
            'lineClearances.verificadoPor',
            'opMaterialReconciliations',
        ]);

        $packagingSigned = $op->packagingResult && $op->packagingResult->signed_at;

        // Estructura de Hitos (Milestones) para la barra de progreso visual
        $milestones = [
            ['label' => 'Emisión OP',      'status' => 'complete', 'icon' => 'check'],
            ['label' => 'Ajuste Insumos',  'status' => $op->status !== 'OP_CREADA' ? 'complete' : 'current'],
            ['label' => 'Códificado',      'status' => $op->codificado_aprobado_id ? 'complete' : (in_array($op->status, ['OP_VERIFICADA', 'COD_CREADO']) && !$op->codificado_aprobado_id ? 'current' : 'pending')],
            ['label' => 'Aprobación COAs', 'status' => $op->coas_aprobado_id ? 'complete' : ($op->codificado_aprobado_id && !$op->coas_aprobado_id ? 'current' : 'pending')],
            ['label' => 'Despeje Línea',   'status' => $op->lineClearances->count() > 0 ? 'complete' : ($op->coas_aprobado_id && $op->lineClearances->count() === 0 ? 'current' : 'pending')],
            ['label' => 'Dispensación',    'status' => ($op->dispensing && $op->dispensing->status === 'COMPLETADO') ? 'complete' : ($op->lineClearances->count() > 0 && (!$op->dispensing || $op->dispensing->status !== 'COMPLETADO') ? 'current' : 'pending')],
            ['label' => 'Manufactura',     'status' => in_array($op->status, ['ACONDICIONAMIENTO', 'COMPLETADO', 'CUARENTENA', 'LIBERADO']) ? 'complete' : ($op->dispensing && $op->dispensing->status === 'COMPLETADO' && $op->status === 'MANUFACTURA' ? 'current' : 'pending')],
            ['label' => 'Empaque',         'status' => $packagingSigned ? 'complete' : ($op->status === 'ACONDICIONAMIENTO' ? 'current' : 'pending')],
            ['label' => 'Liberación',      'status' => $op->status === 'LIBERADO' ? 'complete' : ($packagingSigned ? 'current' : 'pending')],
        ];

        $auditLogs = $this->getFormattedAuditLogs($op);
        $hasAlerts = $auditLogs->contains('is_alert', true);
        $certificadores = User::withPermission('liberacion_final_lote')->get();

        return view('genealogy.show', compact('op', 'auditLogs', 'certificadores', 'milestones', 'hasAlerts'));
    }

    /**
     * Procesa la liberación final del lote bajo protocolo 21 CFR Part 11.
     */
    public function release(Request $request, $op)
    {
        if (!($op instanceof ProductionOrder)) {
            $resolved = (new ProductionOrder)->resolveRouteBinding($op);
            if (!$resolved) {
                abort(404, 'Orden no encontrada.');
            }
            $op = $resolved;
        }

        abort_if(!auth()->user()->hasPermission('liberacion_final_lote'), 403, 'No tiene permisos para liberar lotes.');

        $request->validate([
            'signature_password' => ['required', new \App\Rules\Cfr21Signature()],
            'signature_reason'   => 'required|string',
        ]);

        $yield = $op->final_yield_percentage ?? $op->yield_percentage;
        $isOutOfRange = $yield < 90 || $yield > 110;

        // Artículo 2.1 GEMINI.md: Si el rendimiento está fuera de 90%-110%, solo QA/Admin puede liberar por excepción
        if ($isOutOfRange) {
            $signer = auth()->user();
            if (!$signer->hasRole(['admin', 'calidad', 'direccion_tecnica', 'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD'])) {
                return back()->withErrors([
                    'signature_password' => 'El rendimiento está fuera de rango (90-110%). Solo Aseguramiento de Calidad puede autorizar la liberación por excepción.',
                ]);
            }
        }

        DB::transaction(function () use ($op, $request, $yield, $isOutOfRange) {
            // 1. Cambiar estado a LIBERADO
            $op->update(['status' => 'LIBERADO']);

            $payload = [
                'yield'     => $yield,
                'status'    => 'LIBERADO',
                '_metadata' => [
                    'decision'         => $isOutOfRange ? 'Aprobado por Excepción' : 'Aprobado Regular',
                    'formula_nombre'   => optional($op->product)->name,
                    'rendimiento_real' => $yield,
                ],
            ];

            // 2. Registrar firma electrónica de liberación en el Audit Trail
            $this->signatureService->logSignature(
                $request->signature_reason . ' (Rendimiento: ' . $yield . '%)',
                ProductionOrder::class,
                $op->id,
                $payload,
                auth()->id()
            );
        });

        return back()->with('success', 'El lote ha sido liberado exitosamente bajo el protocolo 21 CFR Part 11.');
    }

    /**
     * Genera el PDF oficial de Genealogía y Auditoría del Lote.
     */
    public function downloadPdf($op)
    {
        if (!($op instanceof ProductionOrder)) {
            $resolved = (new ProductionOrder)->resolveRouteBinding($op);
            if (!$resolved) {
                abort(404, 'Orden no encontrada.');
            }
            $op = $resolved;
        }

        $op->load([
            'product',
            'dispensing.dispensingDetails.formulaIngredient',
            'manufacturingExecutions.planStep',
            'manufacturingExecutions.user',
            'manufacturingExecutions.qaUser',
            'packagingResult.user',
            'packagingResult.qaUser',
        ]);

        $auditLogs = $this->getFormattedAuditLogs($op);

        $pdf = Pdf::loadView('genealogy.pdf', compact('op', 'auditLogs'))
            ->setPaper('letter', 'portrait')
            ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        return $pdf->download("BatchRecord_{$op->lote}.pdf");
    }

    /**
     * Consulta los registros de auditoría asociados a la OP y sus ejecuciones de manufactura,
     * marcando cuáles corresponden a alertas o desviaciones de proceso.
     */
    private function getFormattedAuditLogs(ProductionOrder $op)
    {
        $auditLogs = AuditLog::with(['user', 'signerUser', 'onBehalfOfUser'])
            ->where(function ($query) use ($op) {
                $query->where(function ($q) use ($op) {
                    $q->where('model_type', ProductionOrder::class)
                      ->where('model_id', $op->id);

                    if ($op->created_at) {
                        $q->where('created_at', '>=', $op->created_at->copy()->subMinute());
                    }
                })
                ->orWhere(function ($q) use ($op) {
                    if ($op->manufacturingExecutions->isNotEmpty()) {
                        $q->where('model_type', 'App\Models\ManufacturingExecution')
                          ->whereIn('model_id', $op->manufacturingExecutions->pluck('id'));
                    } else {
                        $q->where('model_type', 'none');
                    }
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $auditLogs->transform(function ($log) {
            $reasonUpper = mb_strtoupper((string) $log->reason, 'UTF-8');
            $actionUpper = mb_strtoupper((string) $log->action, 'UTF-8');

            $log->is_alert = str_contains($reasonUpper, 'ALERTA') ||
                             str_contains($actionUpper, 'ALERTA') ||
                             str_contains($reasonUpper, 'DESVIACIÓN') ||
                             str_contains($reasonUpper, 'DESVIACION') ||
                             str_contains($reasonUpper, 'FUERA DE RANGO');
            return $log;
        });

        return $auditLogs;
    }
}
