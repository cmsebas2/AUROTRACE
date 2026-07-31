<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class BatchRecordController extends Controller
{
    /**
     * Dashboard de Expedientes de Lote
     */
    public function index()
    {
        // Listar OPs que ya han iniciado flujo (status != PENDIENTE o similar)
        // En este sistema, AJ_PENDIENTE es el primer estado tras creación.
        $ops = ProductionOrder::with('product')
            ->where('status', '!=', 'PENDIENTE')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('batch-records.index', compact('ops'));
    }

    /**
     * Generar PDF Acumulativo (Master Batch Record)
     */
    public function downloadMasterPdf($lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();
        
        // Johann v9.2: Refresco forzado de relaciones para asegurar datos finales en el PDF
        $op->refresh();
        $op->load([
            'product.ingredients', 
            'opMaterialReconciliations' => function($q) {
                $q->orderByRaw("FIELD(type, 'MATERIA PRIMA', 'ENVASE', 'EMPAQUE'), id ASC");
            }, 
            'opPresentations.presentation'
        ]);

        // 1. Datos para A3PPR0007 (iniciar.blade.php)
        $productos = Product::with(['presentations', 'ingredients'])->where('status', 'ACTIVO')->get();
        $presentaciones = \App\Models\ProductPresentation::all();

        // 2. Datos para A4PPR0007 (ajuste-activos.blade.php)
        $productApis = $op->product->ingredients->filter(function($ing) {
            return mb_strtoupper($ing->function) === 'API';
        });

        $is_pdf = true;

        // Johann v8.7: Configuración de motor de PDF para recursos locales (Logo)
        $pdf = Pdf::loadView('batch-records.master-pdf', compact(
            'op', 
            'productos', 
            'presentaciones', 
            'productApis', 
            'is_pdf'
        ))
        ->setPaper('letter', 'landscape')
        ->setOption([
            'isRemoteEnabled' => true, 
            'isHtml5ParserEnabled' => true, 
            'chroot' => public_path()
        ]);

        return $pdf->download("EBR_{$op->product->name}_{$op->lote}.pdf");
    }
}
