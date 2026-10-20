<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\Product;
use App\Models\ProductPresentation;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * ============================================================================
 * CONTROLADOR DE EXPEDIENTES ACUMULATIVOS (BATCH RECORDS) - BatchRecordController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Permite consultar los expedientes electrónicos de lote (EBR) y descargar el
 * documento PDF Maestro Acumulativo que une:
 * - Formato A3PPR0007 (Orden de Producción y Explosión de Materiales)
 * - Formato A4PPR0007 (Ajuste de Principios Activos por Potencia y Humedad)
 * ============================================================================
 */
class BatchRecordController extends Controller
{
    /**
     * Muestra el listado de expedientes de lotes iniciados en planta.
     */
    public function index()
    {
        $ops = ProductionOrder::with('product')
            ->where('status', '!=', 'PENDIENTE')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('batch-records.index', compact('ops'));
    }

    /**
     * Genera y descarga el PDF Acumulativo (Master Batch Record) para un lote específico.
     */
    public function downloadMasterPdf($lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();

        // Refresco de relaciones para asegurar que los últimos ajustes y firmas aparezcan en el PDF
        $op->refresh();
        $op->load([
            'product.ingredients',
            'opMaterialReconciliations' => function ($q) {
                $q->orderByRaw("CASE UPPER(type) WHEN 'MATERIA PRIMA' THEN 1 WHEN 'ENVASE' THEN 2 WHEN 'EMPAQUE' THEN 3 ELSE 4 END, id ASC");
            },
            'opPresentations.presentation',
        ]);

        // 1. Datos de referencia para el formato A3PPR0007
        $productos = Product::with(['presentations', 'ingredients'])->where('status', 'ACTIVO')->get();
        $presentaciones = ProductPresentation::all();

        // 2. Principios Activos (APIs) para el formato A4PPR0007
        $productApis = $op->product?->ingredients
            ? $op->product->ingredients->filter(fn($ing) => mb_strtoupper((string) $ing->function, 'UTF-8') === 'API')
            : collect();

        $is_pdf = true;

        $pdf = Pdf::loadView('batch-records.master-pdf', compact(
            'op',
            'productos',
            'presentaciones',
            'productApis',
            'is_pdf'
        ))
        ->setPaper('letter', 'landscape')
        ->setOption([
            'isRemoteEnabled'      => true,
            'isHtml5ParserEnabled' => true,
            'chroot'               => public_path(),
        ]);

        $safeProductName = str_replace(['/', '\\'], '-', $op->product?->name ?? 'Producto');
        $safeLote = str_replace(['/', '\\'], '-', $op->lote);

        return $pdf->download("EBR_{$safeProductName}_{$safeLote}.pdf");
    }
}
