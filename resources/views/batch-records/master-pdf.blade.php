@extends('layouts.pdf-clean')

@section('content')
    <style>
        /* Reparación de Logo para DomPDF (Carga local absoluta) */
        img[alt="AUROFARMA"], img[alt="Aurofarma Logo"] {
            content: url("{{ public_path('img/logo.png') }}") !important;
            max-height: 45px !important;
        }

        /* Estética Industrial (Centrado y Alineación) */
        .tabla-rigida td { 
            text-align: center !important; 
            vertical-align: middle !important; 
        }
        .tabla-rigida td:nth-child(2), .tabla-rigida td.txt-left, .tabla-rigida td.description-cell { 
            text-align: left !important; 
        }
        .bg-gris { font-weight: bold !important; background-color: #D9D9D9 !important; }

        /* Ajustes de escala industrial específicos para este reporte */
        .formato-a3ppr0007, .formato-a4ppr0007 {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 auto !important;
            width: 100% !important;
        }
        table { page-break-inside: avoid !important; }
    </style>

    <!-- SECCIÓN 1: ORDEN DE PRODUCCIÓN (A1PPR0007 / A3PPR0007) -->
    <div class="formato-compacto">
        @if($op->status === 'VERIFICADO' || $op->status === 'COMPLETADO')
            @include('batch.verificar-op', [
                'op' => $op, 
                'is_pdf' => true,
                'productos' => $productos,
                'presentaciones' => $presentaciones
            ])
        @else
            @include('batch.iniciar', [
                'op' => $op, 
                'is_pdf' => true, 
                'productos' => $productos,
                'presentaciones' => $presentaciones
            ])
        @endif
    </div>

    <div class="page-break"></div>

    <!-- SECCIÓN 2: VERIFICACIÓN DE AJUSTES (A4PPR0007) -->
    <div class="formato-compacto">
        @include('batch.ajuste-activos', [
            'op' => $op, 
            'is_pdf' => true, 
            'productApis' => $productApis
        ])
    </div>

    <div class="page-break"></div>

    <!-- SECCIÓN 3: CERTIFICADOS DE ANÁLISIS (COAS) -->
    <div class="formato-compacto">
        @include('batch.aprobar-coas', [
            'op' => $op,
            'is_pdf' => true
        ])
    </div>

    <div class="page-break"></div>

    <!-- SECCIÓN 4: APROBACIÓN DE CODIFICADO (A6PPR0007) -->
    <div class="formato-compacto">
        @include('batch.aprobar-codificado', [
            'op' => $op,
            'is_pdf' => true
        ])
    </div>
@endsection
