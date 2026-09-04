<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class GenealogyController extends Controller
{
    protected $signatureService;

    public function __construct(SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Muestra el buscador de genealogía.
     */
    public function index()
    {
        return view('genealogy.search');
    }

    /**
     * Vista 360° del Lote.
     */
    public function showByBatch($op)
    {
        $lote = $op;
        $op = ProductionOrder::where('lote', $lote)->first();

        if (!$op) {
            return redirect('/genealogia')->with('error', "El lote '{$lote}' no existe o no ha sido registrado en el sistema.");
        }

        // Eager Loading de toda la cadena de valor, incluyendo reconciliaciones (A4/A3)
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
            'opMaterialReconciliations'
        ]);

        // Estructura de Hitos (Milestones) para la UI
        $milestones = [
            ['label' => 'Emisión OP', 'status' => 'complete', 'icon' => 'check'],
            ['label' => 'Ajuste Insumos', 'status' => $op->status !== 'OP_CREADA' ? 'complete' : 'current'],
            ['label' => 'Códificado', 'status' => $op->codificado_aprobado_id ? 'complete' : (in_array($op->status, ['OP_VERIFICADA', 'COD_CREADO']) && !$op->codificado_aprobado_id ? 'current' : 'pending')],
            ['label' => 'Aprobación COAs', 'status' => $op->coas_aprobado_id ? 'complete' : ($op->codificado_aprobado_id && !$op->coas_aprobado_id ? 'current' : 'pending')],
            ['label' => 'Despeje Línea', 'status' => $op->lineClearances->count() > 0 ? 'complete' : ($op->coas_aprobado_id && $op->lineClearances->count() === 0 ? 'current' : 'pending')],
            ['label' => 'Dispensación', 'status' => ($op->dispensing && $op->dispensing->status === 'COMPLETADO') ? 'complete' : ($op->lineClearances->count() > 0 && (!$op->dispensing || $op->dispensing->status !== 'COMPLETADO') ? 'current' : 'pending')],
            ['label' => 'Manufactura', 'status' => in_array($op->status, ['ACONDICIONAMIENTO', 'LIBERADO']) ? 'complete' : ($op->dispensing && $op->dispensing->status === 'COMPLETADO' && $op->status === 'MANUFACTURA' ? 'current' : 'pending')],
            ['label' => 'Empaque', 'status' => $op->packagingResult ? 'complete' : ($op->status === 'ACONDICIONAMIENTO' ? 'current' : 'pending')],
            ['label' => 'Liberación', 'status' => $op->status === 'LIBERADO' ? 'complete' : ($op->packagingResult ? 'current' : 'pending')],
        ];

        // Audit Logs (Trazabilidad Forense)
        $auditLogs = AuditLog::where(function($query) use ($op) {
                $query->where(function($q) use ($op) {
                    $q->where('model_type', ProductionOrder::class)
                      ->where('model_id', $op->id)
                      // Filtrar logs de OPs eliminadas que usaron el mismo ID (reuso de AUTO_INCREMENT)
                      ->where('created_at', '>=', $op->created_at->copy()->subMinute());
                })
                ->orWhere(function($q) use ($op) {
                    // Los ManufacturingExecution siempre son nuevos, pero los aseguramos
                    if ($op->manufacturingExecutions->isNotEmpty()) {
                        $q->where('model_type', 'App\Models\ManufacturingExecution')
                          ->whereIn('model_id', $op->manufacturingExecutions->pluck('id'));
                    } else {
                        // Prevent fetching all if manufacturingExecutions is empty
                        $q->where('model_type', 'none'); 
                    }
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $auditLogs->transform(function($log) {
            // Detección de Alertas Críticas (Desviaciones o Rendimientos)
            $isAlert = str_contains(strtoupper($log->reason), 'ALERTA') || 
                       str_contains(strtoupper($log->action), 'ALERTA') ||
                       str_contains(strtoupper($log->reason), 'DESVIACIÓN') ||
                       str_contains(strtoupper($log->reason), 'FUERA DE RANGO');
            $log->is_alert = $isAlert;
            return $log;
        });

        $hasAlerts = $auditLogs->contains('is_alert', true);
        $certificadores = User::withPermission('liberacion_final_lote')->get();

        return view('genealogy.show', compact('op', 'auditLogs', 'certificadores', 'milestones', 'hasAlerts'));
    }

    /**
     * Procesa la liberación final del producto.
     */
    public function release(Request $request, $op)
    {
        if (!($op instanceof ProductionOrder)) {
            $resolved = (new ProductionOrder)->resolveRouteBinding($op);
            if (!$resolved) abort(404, 'Orden no encontrada.');
            $op = $resolved;
        }

        abort_if(!auth()->user()->hasPermission('liberacion_final_lote'), 403, 'No tiene permisos para liberar lotes.');

        $request->validate([
            'signature_password' => ['required', new \App\Rules\Cfr21Signature()],
            'signature_reason' => 'required|string'
        ]);

        $yield = $op->final_yield_percentage;
        $isOutOfRange = $yield < 90 || $yield > 110;

        // Regla: Si está fuera de rango, el firmante DEBE tener rol Admin o QA (calidad/direccion_tecnica)
        if ($isOutOfRange) {
            $signer = auth()->user();
            if (!$signer->hasRole(['admin', 'calidad', 'direccion_tecnica', 'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD'])) {
                return back()->withErrors(['signature_password' => 'El rendimiento está fuera de rango (90-110%). Solo Aseguramiento de Calidad puede autorizar la liberación por excepción.']);
            }
        }

        // 1. Cambiar estado a LIBERADO
        $op->update(['status' => 'LIBERADO']);

        $payload = [
            'yield' => $yield, 
            'status' => 'LIBERADO',
            '_metadata' => [
                'decision' => $isOutOfRange ? 'Aprobado por Excepción' : 'Aprobado Regular',
                'formula_nombre' => optional($op->product)->name,
                'rendimiento_real' => $yield,
            ]
        ];

        // 2. Loguear firma de liberación
        $this->signatureService->logSignature(
            $request->signature_reason . ' (Rendimiento: ' . $yield . '%)',
            ProductionOrder::class,
            $op->id,
            $payload,
            auth()->id()
        );

        return back()->with('success', 'El lote ha sido liberado exitosamente bajo el protocolo 21 CFR Part 11.');
    }

    /**
     * Genera el PDF oficial del Batch Record.
     */
    public function downloadPdf($op)
    {
        if (!($op instanceof ProductionOrder)) {
            $resolved = (new ProductionOrder)->resolveRouteBinding($op);
            if (!$resolved) abort(404, 'Orden no encontrada.');
            $op = $resolved;
        }

        // El botón solo está disponible si está liberado, pero validamos igual
        // Eager Loading para el PDF
        $op->load([
            'product',
            'dispensing.dispensingDetails.formulaIngredient',
            'manufacturingExecutions.planStep',
            'manufacturingExecutions.user',
            'manufacturingExecutions.qaUser',
            'packagingResult.user',
            'packagingResult.qaUser',
        ]);

        $auditLogs = AuditLog::where(function($query) use ($op) {
                $query->where(function($q) use ($op) {
                    $q->where('model_type', ProductionOrder::class)
                      ->where('model_id', $op->id)
                      ->where('created_at', '>=', $op->created_at->copy()->subMinute());
                })
                ->orWhere(function($q) use ($op) {
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

        $auditLogs->transform(function($log) {
            $log->is_alert = str_contains(strtoupper($log->reason), 'ALERTA-') || 
                             str_contains(strtoupper($log->action), 'ALERTA-') ||
                             str_contains(strtoupper($log->reason), 'DESVIACIÓN-');
            return $log;
        });

        $pdf = Pdf::loadView('genealogy.pdf', compact('op', 'auditLogs'))
                  ->setPaper('letter', 'portrait')
                  ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        return $pdf->download("BatchRecord_{$op->lote}.pdf");
    }
}
