<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * CONTROLADOR DEL PANEL PRINCIPAL (DASHBOARD) - DashboardController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Arma la pantalla de inicio del ERP con 4 bloques de información en tiempo real:
 * 1. KPIs Maestros: Cuántos lotes están activos en planta, cuántos esperan
 *    codificado, cuántos esperan revisión de COAs y cuántos se liberaron hoy.
 * 2. Centro de Alertas Preventivas (Pilar 4): Detecta automáticamente lotes en
 *    Cuarentena, COAs próximos a vencer (FEFO), Batch Records pendientes de llegada
 *    física y expedientes en RACK 1 que cumplieron su fecha de destrucción ICA.
 * 3. Monitor de Línea de Producción: Lista las órdenes activas y calcula su
 *    porcentaje de avance (de 10% en Emisión hasta 95% en Empaque).
 * 4. Feed Forense 21 CFR Part 11 + Rendimiento OEE Dinámico: Muestra las últimas
 *    actividades en `audit_logs` y el cumplimiento real de rendimiento en planta.
 * ============================================================================
 */
class DashboardController extends Controller
{
    /**
     * Renderiza el tablero principal de control operativo y analítica preventiva.
     */
    public function index()
    {
        // Si el usuario tiene perfil exclusivo de Calidad, redirigir directamente a su portal QA
        if (auth()->check() && auth()->user()->isQualityUser()) {
            return redirect()->route('calidad.index');
        }

        // 1. Panel de Indicadores Maestros (KPIs con Caché de 30s para máxima velocidad)
        $kpis = Cache::remember('dashboard_kpis_v3', 30, function () {
            try {
                $today = Carbon::today()->toDateString();
                $raw = DB::table('production_orders')
                    ->selectRaw("
                        COUNT(CASE WHEN status NOT IN ('LIBERADO', 'RECHAZADO') THEN 1 END) as planta_en_marcha,
                        COUNT(CASE WHEN status IN ('OP_VERIFICADA', 'VERIFICADO') AND codificado_aprobado_id IS NULL THEN 1 END) as codificado_count,
                        COUNT(CASE WHEN codificado_aprobado_id IS NOT NULL AND coas_aprobado_id IS NULL THEN 1 END) as calidad_count,
                        COUNT(CASE WHEN status = 'LIBERADO' AND CAST(updated_at AS DATE) = ? THEN 1 END) as liberado_hoy
                    ", [$today])->first();

                return [
                    'plantaEnMarcha'  => (int) ($raw->planta_en_marcha ?? 0),
                    'codificadoCount' => (int) ($raw->codificado_count ?? 0),
                    'calidadCount'    => (int) ($raw->calidad_count ?? 0),
                    'liberadoHoy'     => (int) ($raw->liberado_hoy ?? 0),
                ];
            } catch (\Throwable $e) {
                return [
                    'plantaEnMarcha'  => 0,
                    'codificadoCount' => 0,
                    'calidadCount'    => 0,
                    'liberadoHoy'     => 0,
                ];
            }
        });

        $plantaEnMarcha = $kpis['plantaEnMarcha'];
        $codificadoCount = $kpis['codificadoCount'];
        $calidadCount = $kpis['calidadCount'];
        $liberadoHoy = $kpis['liberadoHoy'];

        // 2. Analítica OEE Real y Centro de Alertas Preventivas (Pilar 4 - Caché de 60s)
        $analytics = Cache::remember('dashboard_analytics_alerts_v1', 60, function () {
            $alerts = [
                'cuarentena_count'    => 0,
                'cuarentena_lotes'    => [],
                'coas_por_vencer'     => 0,
                'coas_detalle'        => [],
                'br_pendientes_count' => 0,
                'destruccion_count'   => 0,
                'destruccion_lotes'   => [],
                'desviaciones_7d'     => 0,
                'total_alertas'       => 0,
            ];

            $oee = [
                'compliance_pct' => 100.0,
                'avg_yield'      => 100.0,
                'status_label'   => 'Óptimo',
                'evaluated_ops'  => 0,
            ];

            try {
                // A) Lotes en Cuarentena (GEMINI.md Art. 2.1)
                $cuarentenaRows = DB::table('production_orders')
                    ->where('status', 'CUARENTENA')
                    ->select('lote', 'op_number', 'final_yield_percentage')
                    ->limit(5)
                    ->get();
                $alerts['cuarentena_count'] = DB::table('production_orders')->where('status', 'CUARENTENA')->count();
                $alerts['cuarentena_lotes'] = $cuarentenaRows->pluck('lote')->toArray();

                // B) COAs vencidos o próximos a vencer en 30 días (GEMINI.md Art. 5 - Ley FEFO)
                if (Schema::hasTable('op_material_reconciliations')) {
                    $limitDate = Carbon::today()->addDays(30)->toDateString();
                    $coasQuery = DB::table('op_material_reconciliations')
                        ->join('production_orders', 'production_orders.id', '=', 'op_material_reconciliations.production_order_id')
                        ->whereNotIn('production_orders.status', ['LIBERADO', 'RECHAZADO'])
                        ->whereNotNull('op_material_reconciliations.fecha_vencimiento_coa')
                        ->where('op_material_reconciliations.fecha_vencimiento_coa', '<=', $limitDate);

                    $alerts['coas_por_vencer'] = (clone $coasQuery)->count();
                    $alerts['coas_detalle'] = $coasQuery
                        ->select('production_orders.lote', 'op_material_reconciliations.material_code', 'op_material_reconciliations.fecha_vencimiento_coa')
                        ->limit(4)
                        ->get()
                        ->toArray();
                }

                // C) Maquilas con BR Pendiente de Llegada Física y Expedientes listos para Destrucción ICA
                if (Schema::hasTable('maquila_production_orders')) {
                    $alerts['br_pendientes_count'] = DB::table('maquila_production_orders')
                        ->whereNull('deleted_at')
                        ->whereIn('estado', ['OP TERMINADA - BR PENDIENTE', 'completada_pendiente_liquidacion'])
                        ->count();

                    $currentYearMonth = Carbon::now()->format('Y-m');
                    $destruccionQuery = DB::table('maquila_production_orders')
                        ->whereNull('deleted_at')
                        ->whereNotNull('fecha_destruccion_br')
                        ->where('fecha_destruccion_br', '!=', '')
                        ->where('fecha_destruccion_br', '<=', $currentYearMonth);

                    $alerts['destruccion_count'] = (clone $destruccionQuery)->count();
                    $alerts['destruccion_lotes'] = $destruccionQuery->limit(5)->pluck('lote')->toArray();
                }

                // D) Alertas de Desviación (>5%) en los últimos 7 días (GEMINI.md Art. 2.2)
                if (Schema::hasTable('audit_logs')) {
                    $alerts['desviaciones_7d'] = DB::table('audit_logs')
                        ->where('action', 'LIKE', '%DESVIACIÓN%')
                        ->where('created_at', '>=', Carbon::now()->subDays(7))
                        ->count();
                }

                $alerts['total_alertas'] = $alerts['cuarentena_count']
                    + $alerts['coas_por_vencer']
                    + $alerts['br_pendientes_count']
                    + $alerts['destruccion_count']
                    + $alerts['desviaciones_7d'];

                // E) Cálculo Dinámico de Rendimiento OEE (Planta + Maquila)
                $yieldStats = DB::table('production_orders')
                    ->whereNotNull('final_yield_percentage')
                    ->where('final_yield_percentage', '>', 0)
                    ->selectRaw("
                        COUNT(*) as total_eval,
                        COUNT(CASE WHEN final_yield_percentage >= 90.00 AND final_yield_percentage <= 110.00 THEN 1 END) as in_range,
                        AVG(final_yield_percentage) as avg_yield
                    ")->first();

                if ($yieldStats && $yieldStats->total_eval > 0) {
                    $oee['evaluated_ops']  = (int) $yieldStats->total_eval;
                    $oee['compliance_pct'] = round(($yieldStats->in_range / $yieldStats->total_eval) * 100, 1);
                    $oee['avg_yield']      = round((float) $yieldStats->avg_yield, 1);
                } elseif (Schema::hasTable('maquila_production_orders')) {
                    $maqStats = DB::table('maquila_production_orders')
                        ->whereNull('deleted_at')
                        ->whereNotNull('rendimiento_real')
                        ->where('rendimiento_real', '>', 0)
                        ->selectRaw("
                            COUNT(*) as total_eval,
                            COUNT(CASE WHEN rendimiento_real >= 90.00 AND rendimiento_real <= 110.00 THEN 1 END) as in_range,
                            AVG(rendimiento_real) as avg_yield
                        ")->first();

                    if ($maqStats && $maqStats->total_eval > 0) {
                        $oee['evaluated_ops']  = (int) $maqStats->total_eval;
                        $oee['compliance_pct'] = round(($maqStats->in_range / $maqStats->total_eval) * 100, 1);
                        $oee['avg_yield']      = round((float) $maqStats->avg_yield, 1);
                    }
                }

                if ($oee['compliance_pct'] < 85) {
                    $oee['status_label'] = 'Crítico';
                } elseif ($oee['compliance_pct'] < 95) {
                    $oee['status_label'] = 'Atención';
                } else {
                    $oee['status_label'] = 'Óptimo';
                }
            } catch (\Throwable $e) {
                // Fallback silencioso
            }

            return [
                'alerts' => $alerts,
                'oee'    => $oee,
            ];
        });

        $preventiveAlerts = $analytics['alerts'];
        $oeeMetrics = $analytics['oee'];

        // 3. Monitor de Línea de Producción (Solo Lectura con Eager Loading para evitar N+1 queries)
        $activeOrders = ProductionOrder::with(['product', 'lineClearances:id,production_order_id', 'dispensing:id,production_order_id,status'])
            ->whereNotIn('status', ['LIBERADO', 'RECHAZADO'])
            ->orderBy('updated_at', 'desc')
            ->take(50)
            ->get();

        $activeOrders->transform(function ($op) {
            $progress = 10; // Emisión base
            $currentStep = "Emisión";

            if (!in_array($op->status, ['OP_CREADA', 'AJ_PENDIENTE', 'PENDIENTE'])) {
                $progress = 20;
                $currentStep = "Ajuste DT";
            }
            if ($op->codificado_aprobado_id) {
                $progress = 35;
                $currentStep = "Codificación";
            }
            if ($op->coas_aprobado_id) {
                $progress = 50;
                $currentStep = "Revisión COAs";
            }
            if ($op->lineClearances->count() > 0) {
                $progress = 60;
                $currentStep = "Despeje Línea";
            }
            if ($op->dispensing && $op->dispensing->status === 'COMPLETADO') {
                $progress = 75;
                $currentStep = "Dispensación";
            }
            if (in_array($op->status, ['ACONDICIONAMIENTO', 'COMPLETADO', 'LIBERADO'])) {
                $progress = 95;
                $currentStep = "Empaque";
            } elseif ($op->status === 'CUARENTENA') {
                $progress = 95;
                $currentStep = "Cuarentena QA";
            } elseif ($op->status === 'MANUFACTURA') {
                $progress = 85;
                $currentStep = "Manufactura";
            }

            $op->progress_percentage = $progress;
            $op->current_step_label = $currentStep;
            return $op;
        });

        // 4. Feed Forense CFR 21 (Auditoría en Vivo)
        $auditLogs = AuditLog::with('user')->latest()->take(10)->get();

        return view('dashboard', compact(
            'plantaEnMarcha',
            'codificadoCount',
            'calidadCount',
            'liberadoHoy',
            'preventiveAlerts',
            'oeeMetrics',
            'activeOrders',
            'auditLogs'
        ));
    }
}
