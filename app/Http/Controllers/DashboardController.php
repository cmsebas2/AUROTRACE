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
        // 1. Panel de Indicadores Maestros (KPIs de Solo Lectura)
        $plantaEnMarcha = ProductionOrder::whereNotIn('status', ['LIBERADO', 'RECHAZADO'])->count();
        
        $codificadoCount = ProductionOrder::where('status', 'VERIFICADO')
            ->whereNull('codificado_aprobado_id')
            ->count();
            
        $calidadCount = ProductionOrder::whereNotNull('codificado_aprobado_id')
            ->whereNull('coas_aprobado_id')
            ->count();
            
        $liberadoHoy = ProductionOrder::where('status', 'LIBERADO')
            ->whereDate('updated_at', Carbon::today())
            ->count();

        // 2. Monitor de Línea de Producción (Solo Lectura)
        $activeOrders = ProductionOrder::with(['product', 'lineClearances', 'dispensing'])
            ->whereNotIn('status', ['LIBERADO', 'RECHAZADO'])
            ->orderBy('updated_at', 'desc')
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
