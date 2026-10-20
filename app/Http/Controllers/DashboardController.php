<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * CONTROLADOR DEL PANEL PRINCIPAL (DASHBOARD) - DashboardController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Arma la pantalla de inicio del ERP con 3 bloques de información en tiempo real:
 * 1. KPIs Maestros: Cuántos lotes están activos en planta, cuántos esperan
 *    codificado, cuántos esperan revisión de COAs y cuántos se liberaron hoy.
 * 2. Monitor de Línea de Producción: Lista las órdenes activas y calcula su
 *    porcentaje de avance (de 10% en Emisión hasta 95% en Empaque).
 * 3. Feed Forense 21 CFR Part 11: Muestra las últimas 10 actividades registradas
 *    en la bitácora `audit_logs`.
 * ============================================================================
 */
class DashboardController extends Controller
{
    /**
     * Renderiza el tablero principal de control operativo.
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

        // 2. Monitor de Línea de Producción (Solo Lectura con Eager Loading para evitar N+1 queries)
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

        // 3. Feed Forense CFR 21 (Auditoría en Vivo)
        $auditLogs = AuditLog::with('user')->latest()->take(10)->get();

        return view('dashboard', compact(
            'plantaEnMarcha',
            'codificadoCount',
            'calidadCount',
            'liberadoHoy',
            'activeOrders',
            'auditLogs'
        ));
    }
}
