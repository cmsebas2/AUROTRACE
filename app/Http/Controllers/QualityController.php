<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaquilaProductionOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * PORTAL DE ASEGURAMIENTO DE CALIDAD (QA) - QualityController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Este controlador administra la bandeja oficial de Aseguramiento de Calidad (`/calidad`):
 * 1. Muestra los Batch Records que ya pasaron por Dirección Técnica y esperan
 *    la liberación final de Calidad (`BR REVISION CALIDAD`).
 * 2. Muestra los lotes próximos en revisión de Dirección Técnica (`BR REVISION DT`).
 * 3. Lista el historial de lotes liberados (`BR CERRADO`) y con hallazgos (`BR ABIERTO`).
 * 4. Incluye el buscador general con ubicación física en los archivadores del Rack.
 * ============================================================================
 */
class QualityController extends Controller
{
    /**
     * Renderiza el Portal Oficial de Aseguramiento de Calidad (QA) & Liberación de Lotes.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Control estricto de acceso por rol/permiso (21 CFR Part 11)
        if (!$user->hasPermission('ver_aseguramiento_calidad') &&
            !$user->hasRole(['calidad', 'CALIDAD', 'INSPECTOR DE CALIDAD', 'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD', 'admin', 'ADMIN', 'Administrador'])) {
            abort(403, 'Acceso Denegado: Este portal está reservado exclusivamente para el personal de Aseguramiento de Calidad (QA) y Dirección Técnica.');
        }

        // 1. Solicitudes PENDIENTES de aprobación por Calidad (Estado = BR REVISION CALIDAD)
        $solicitudesPendientes = MaquilaProductionOrder::where('estado', 'BR REVISION CALIDAD')
            ->with(['maquilador', 'items', 'dtUser', 'creator'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 2. Lotes próximos en revisión por DT (Estado = BR REVISION DT)
        $enRevisionDt = MaquilaProductionOrder::where('estado', 'BR REVISION DT')
            ->with(['maquilador', 'items'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 3. Lotes Liberados / Cerrados Formalmente (Estado = BR CERRADO)
        $lotesLiberados = MaquilaProductionOrder::where('estado', 'BR CERRADO')
            ->with(['maquilador', 'qaUser', 'dtUser'])
            ->orderBy('fecha_liberacion_br', 'desc')
            ->orderBy('updated_at', 'desc')
            ->take(25)
            ->get();

        // 4. Lotes con Hallazgos / Observaciones (Estado = BR ABIERTO)
        $lotesAbiertos = MaquilaProductionOrder::where('estado', 'BR ABIERTO')
            ->with(['maquilador', 'qaUser', 'dtUser'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 5. Ubicador y Catálogo General de Batch Records (Buscador & Localizador Físico)
        $searchQuery = trim($request->input('buscar', ''));
        $filtroEstado = trim($request->input('filtro_estado', ''));

        $queryBr = MaquilaProductionOrder::with(['maquilador', 'qaUser', 'items']);
        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        if (!empty($searchQuery)) {
            $queryBr->where(function ($q) use ($searchQuery, $likeOp) {
                $q->where('lote', $likeOp, "%{$searchQuery}%")
                  ->orWhere('op', $likeOp, "%{$searchQuery}%")
                  ->orWhere('producto_nombre', $likeOp, "%{$searchQuery}%")
                  ->orWhere('posicion_archivo_fisico', $likeOp, "%{$searchQuery}%")
                  ->orWhereHas('maquilador', function ($m) use ($searchQuery, $likeOp) {
                      $m->where('nombre', $likeOp, "%{$searchQuery}%");
                  });
            });
        }

        if (!empty($filtroEstado)) {
            if ($filtroEstado === 'CON_UBICACION') {
                $queryBr->whereNotNull('posicion_archivo_fisico')->where('posicion_archivo_fisico', '!=', '');
            } elseif ($filtroEstado === 'SIN_UBICACION') {
                $queryBr->where(function ($q) {
                    $q->whereNull('posicion_archivo_fisico')->orWhere('posicion_archivo_fisico', '');
                });
            } else {
                $queryBr->where('estado', $filtroEstado);
            }
        }

        $todosBatchRecords = $queryBr->orderBy('op', 'desc')->paginate(20)->withQueryString();

        // Conteos para KPIs del Header
        $kpis = [
            'pendientes_calidad'    => $solicitudesPendientes->count(),
            'en_revision_dt'        => $enRevisionDt->count(),
            'liberados_total'       => MaquilaProductionOrder::where('estado', 'BR CERRADO')->count(),
            'abiertos_observados'   => $lotesAbiertos->count(),
            'total_archivados_rack' => MaquilaProductionOrder::whereNotNull('posicion_archivo_fisico')->where('posicion_archivo_fisico', '!=', '')->count(),
        ];

        return view('calidad.index', compact(
            'solicitudesPendientes',
            'enRevisionDt',
            'lotesLiberados',
            'lotesAbiertos',
            'todosBatchRecords',
            'kpis',
            'searchQuery',
            'filtroEstado'
        ));
    }
}
