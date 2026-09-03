<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionOrder;
use App\Models\Equipment;
use App\Models\AuditLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Panel de Indicadores Maestros (KPIs con Caché de 30s para máxima velocidad)
        $kpis = \Illuminate\Support\Facades\Cache::remember('dashboard_kpis_v1', 30, function () {
            $today = Carbon::today()->toDateString();
            $raw = \Illuminate\Support\Facades\DB::table('production_orders')
                ->whereNull('deleted_at')
                ->selectRaw("
                    COUNT(CASE WHEN status NOT IN ('LIBERADO', 'RECHAZADO') THEN 1 END) as planta_en_marcha,
                    COUNT(CASE WHEN status = 'VERIFICADO' AND codificado_aprobado_id IS NULL THEN 1 END) as codificado_count,
                    COUNT(CASE WHEN codificado_aprobado_id IS NOT NULL AND coas_aprobado_id IS NULL THEN 1 END) as calidad_count,
                    COUNT(CASE WHEN status = 'LIBERADO' AND DATE(updated_at) = '{$today}' THEN 1 END) as liberado_hoy
                ")->first();

            return [
                'plantaEnMarcha' => (int) ($raw->planta_en_marcha ?? 0),
                'codificadoCount' => (int) ($raw->codificado_count ?? 0),
                'calidadCount' => (int) ($raw->calidad_count ?? 0),
                'liberadoHoy' => (int) ($raw->liberado_hoy ?? 0),
            ];
        });

        $plantaEnMarcha = $kpis['plantaEnMarcha'];
        $codificadoCount = $kpis['codificadoCount'];
        $calidadCount = $kpis['calidadCount'];
        $liberadoHoy = $kpis['liberadoHoy'];

        // 2. Monitor de Línea de Producción (Solo Lectura con Eager Loading específico)
        $activeOrders = ProductionOrder::with(['product:id,name', 'lineClearances:id,production_order_id', 'dispensing:id,production_order_id,status'])
            ->whereNotIn('status', ['LIBERADO', 'RECHAZADO'])
            ->orderBy('updated_at', 'desc')
            ->take(50)
            ->get();

        $activeOrders->transform(function($op) {
            $progress = 10; // Emisión base
            $currentStep = "Emisión";

            if ($op->status !== 'AJ_PENDIENTE') { $progress = 20; $currentStep = "Ajuste DT"; }
            if ($op->codificado_aprobado_id) { $progress = 35; $currentStep = "Codificación"; }
            if ($op->coas_aprobado_id) { $progress = 50; $currentStep = "Revisión COAs"; }
            if ($op->lineClearances->count() > 0) { $progress = 60; $currentStep = "Despeje Línea"; }
            if ($op->dispensing && $op->dispensing->status === 'COMPLETADO') { $progress = 75; $currentStep = "Dispensación"; }
            if (in_array($op->status, ['ACONDICIONAMIENTO', 'LIBERADO'])) { $progress = 95; $currentStep = "Empaque"; }
            elseif ($op->status === 'MANUFACTURA') { $progress = 85; $currentStep = "Manufactura"; }

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
            'activeOrders',
            'auditLogs'
        ));
    }
}
