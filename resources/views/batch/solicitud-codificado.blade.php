@extends('layouts.app')

@section('header_title', 'A6PPR0007 Ver 02 - Solicitud de Codificado')

@section('content')
<style>
    /* REPARACIÓN ESTRUCTURAL RÍGIDA A6PPR0007 (v24.0) */
    .contenedor-a6 {
        width: 100%;
        max-width: 1050px;
        margin: 0 auto;
        padding: 20px;
        background: #ffffff;
    }
    .tabla-maestra {
        width: 100% !important;
        border-collapse: collapse !important;
        table-layout: fixed !important;
        border: 1px solid #000000 !important;
        font-family: Arial, sans-serif !important;
        font-size: 11px !important;
        margin-bottom: -1px;
    }
    .tabla-maestra td, .tabla-maestra th {
        border: 1px solid #000000 !important;
        padding: 5px 10px !important;
        vertical-align: top !important; /* FORZADO AL TOPE */
    }
    .bg-gris { background-color: #f2f2f2 !important; font-weight: bold !important; }
    .bg-blanco { background-color: #ffffff !important; }
    .txt-centro { text-align: center !important; }
    .txt-bold { font-weight: bold !important; }
    .uppercase { text-transform: uppercase !important; }
    .font-mono { font-family: monospace !important; font-size: 11px !important; }

    .check-box {
        width: 15px;
        height: 15px;
        border: 1px solid #000;
        display: inline-block;
        line-height: 15px;
        font-weight: bold;
        background: #fff;
    }

    .input-invisible { 
        width: 100%; border: none; background: transparent; font-family: inherit; font-size: inherit; outline: none;
    }

    /* BLOQUE DE TÍTULO INTERNO CON LÍNEA */
    .header-box {
        font-size: 9px;
        font-weight: bold;
        border-bottom: 1px solid #000;
        display: block;
        padding-bottom: 3px;
        margin-bottom: 8px;
        margin-top: 2px; /* Espacio mínimo arriba */
    }

    /* CONTENEDOR DE VALORES */
    .val-box {
        line-height: 1.5;
        font-size: 11px;
    }

    @media print {
        .no-print { display: none !important; }
        .contenedor-a6 { padding: 0 !important; max-width: 100% !important; }
        @page { size: portrait; margin: 1cm; }
    }
</style>

@php
    $allowedTypes = ['ETIQUETA', 'PLEGADIZA', 'SOBRE', 'JERINGA', 'COLLARIN', 'GARRAFA'];
    $productIngredients = $op->product->ingredients->where('material_type', '!=', 'MATERIA PRIMA');
    $reconciliations = $op->opMaterialReconciliations;
    
    $marks = [];
    foreach($allowedTypes as $t) {
        $marks[$t] = $productIngredients->where('specific_material_type', $t)->count() > 0 ? 'X' : '';
    }
    $marks['OTRO'] = $productIngredients->whereNotNull('specific_material_type')
                    ->whereNotIn('specific_material_type', array_merge($allowedTypes, ['N.A.', '', 'NA']))
                    ->count() > 0 ? 'X' : '';

    $validMaterials = $productIngredients->filter(function($ing) use ($allowedTypes) {
        $type = strtoupper($ing->specific_material_type ?? '');
        return in_array($type, $allowedTypes) || ($type != 'N.A.' && $type != 'NA' && !empty($type));
    })->map(function($ing) use ($reconciliations) {
        $rec = $reconciliations->where('material_code', $ing->material_code)->first();
        return (object)[
            'description' => $ing->material_name,
            'lote_insumo' => ($rec && !empty($rec->lote)) ? $rec->lote : '---',
            'cantidad_requerida' => $rec ? number_format($rec->required_qty, 0, ',', '.') . ' ' . $rec->unit : '---'
        ];
    })->unique('description');
@endphp

<div class="contenedor-a6" x-data="solicitudCodificado()" @signature-verified.window="if($event.detail.role === 'elaborado') handleFirmaElaborado($event.detail)">
    
    <form action="{{ route('op.solicitud_codificado.store', $op->lote) }}" method="POST">
        @csrf

        <!-- 1. ENCABEZADO -->
        <table class="tabla-maestra">
            <colgroup><col width="25%"><col width="55%"><col width="20%"></colgroup>
            <tr>
                <td><span class="txt-bold">CÓDIGO:</span> A6PPR0007</td>
                <td rowspan="4" class="txt-centro txt-bold" style="font-size: 14px; vertical-align: middle !important;">SOLICITUD DE CODIFICADO MATERIAL DE ENVASE Y EMPAQUE</td>
                <td rowspan="4" class="txt-centro" style="vertical-align: middle !important;"><img src="{{ asset('img/logo.png') }}" alt="AUROFARMA" style="max-height: 45px;"></td>
            </tr>
            <tr><td><span class="txt-bold">VERSIÓN:</span> 02</td></tr>
            <tr><td><span class="txt-bold">FECHA EMISIÓN:</span> 2026-04-01</td></tr>
            <tr><td><span class="txt-bold">PÁGINA:</span> 1 de 1</td></tr>
        </table>

        <!-- 2. BLOQUE INTRODUCTORIO -->
        <table class="tabla-maestra">
            <tr>
                <td class="bg-gris" style="padding: 10px !important; font-style: italic;">
                    El presente documento es una solicitud por parte de Laboratorios Aurofarma, el cual se debe tener en cuenta para llevar a cabo la codificación del material de envase y empaque correspondiente a la orden de producción relacionada:
                </td>
            </tr>
        </table>

        <!-- 3. INFORMACIÓN GENERAL -->
        <table class="tabla-maestra">
            <colgroup><col width="15%"><col width="35%"><col width="15%"><col width="35%"></colgroup>
            <tr>
                <td class="bg-gris">FECHA:</td>
                <td colspan="3" class="bg-blanco txt-bold">{{ now()->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="bg-gris">PRODUCTO:</td>
                <td class="bg-blanco txt-bold uppercase">{{ $op->product->name }}</td>
                <td class="bg-gris">LOTE:</td>
                <td class="bg-blanco txt-bold" style="font-size: 13px;">{{ $op->lote }}</td>
            </tr>
            <tr>
                <td class="bg-gris">PRESENTACIONES:</td>
                <td class="bg-blanco uppercase">
                    @foreach($op->opPresentations as $pres)
                        <div class="txt-bold" style="font-size: 10px;">{{ $op->product->name }} X {{ $pres->presentation->name }}</div>
                    @endforeach
                </td>
                <td class="bg-gris">CANTIDADES:</td>
                <td class="bg-blanco">
                    @foreach($op->opPresentations as $pres)
                        <div class="txt-bold" style="font-size: 10px;">{{ number_format($pres->units_to_produce, 0, ',', '.') }} UND</div>
                    @endforeach
                </td>
            </tr>
            <tr>
                <td class="bg-gris">F. FABRICACIÓN:</td>
                <td class="bg-blanco">{{ $op->manufacturing_date ? $op->manufacturing_date->format('Y-m') : '---' }}</td>
                <td class="bg-gris">F. VENCIMIENTO:</td>
                <td class="bg-blanco txt-bold">{{ $op->expiration_date ? $op->expiration_date->format('Y-m') : '---' }}</td>
            </tr>
            <tr>
                <td class="bg-gris">REGISTRO ICA:</td>
                <td class="bg-blanco">{{ $op->product->ica_license ?? '---' }}</td>
                <td class="bg-gris">VIDA ÚTIL:</td>
                <td class="bg-blanco uppercase">{{ $op->product->vigencia_meses ?? '---' }} MESES</td>
            </tr>
        </table>

        <!-- 4. TIPO DE MATERIAL -->
        <table class="tabla-maestra">
            <tr><td colspan="7" class="bg-gris txt-centro">TIPO DE MATERIAL A CODIFICAR</td></tr>
            <tr>
                @foreach(array_merge($allowedTypes, ['OTRO']) as $t)
                <td width="14.28%" class="txt-centro bg-blanco" style="padding: 10px 0 !important;">
                    <div class="check-box">{{ $marks[$t] }}</div>
                    <div style="font-size: 8px; font-weight: bold; margin-top: 4px;">{{ $t }}</div>
                </td>
                @endforeach
            </tr>
        </table>

        <!-- 5. INFORMACIÓN DE CODIFICADO (ELIMINACIÓN DE GAPS) -->
        @forelse($validMaterials as $index => $mat)
        <table class="tabla-maestra" style="margin-top: 15px;">
            <colgroup><col width="50%"><col width="50%"></colgroup>
            <tr>
                <td colspan="2" class="bg-gris txt-centro uppercase" style="font-size: 10px; vertical-align: middle !important;">INFORMACIÓN DE CODIFICADO</td>
            </tr>
            <tr>
                <td class="bg-blanco" style="height: 40px;">
                    <span class="txt-bold">DESCRIPCIÓN DEL MATERIAL:</span> 
                    <span class="uppercase ml-1">{{ $mat->description }}</span>
                </td>
                <td class="bg-blanco" style="height: 40px;">
                    <span class="txt-bold">LOTE INSUMO:</span> 
                    <span class="font-mono ml-1">{{ $mat->lote_insumo }}</span>
                </td>
            </tr>
            <tr>
                <td class="bg-blanco" style="padding: 0 !important; vertical-align: top !important;">
                    <div style="padding: 5px 10px;">
                        <div class="header-box">INFORMACIÓN A CODIFICAR:</div>
                        <div class="val-box" style="text-align: left !important;">
                            <strong>Elaborado por:</strong> Laboratorios Aurofarma S.A.S.<br>
                            <strong>Lote:</strong> {{ $op->lote }}<br>
                            <strong>F.F:</strong> {{ $op->manufacturing_date ? $op->manufacturing_date->format('Y-m') : '---' }}<br>
                            <strong>F.V:</strong> {{ $op->expiration_date ? $op->expiration_date->format('Y-m') : '---' }}
                        </div>
                    </div>
                </td>
                <td class="bg-blanco" style="padding: 0 !important; vertical-align: top !important;">
                    <div style="padding: 5px 10px;">
                        <div class="header-box">CANTIDAD:</div>
                        <div class="val-box" style="font-size: 13px !important; font-weight: bold !important; text-align: left !important;">
                            {{ $mat->cantidad_requerida }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        @empty
        <table class="tabla-maestra" style="margin-top: 15px;"><tr><td class="txt-centro bg-blanco italic text-slate-400">Sin materiales de codificación requeridos.</td></tr></table>
        @endforelse

        <!-- 6. OBSERVACIONES -->
        <table class="tabla-maestra" style="margin-top: 15px;">
            <tr><td class="bg-gris txt-centro">OBSERVACIONES ADICIONALES</td></tr>
            <tr>
                <td class="bg-blanco">
                    <textarea name="observaciones" class="input-invisible" rows="3" 
                              :readonly="firmado"
                              placeholder="Indique requerimientos específicos de codificado...">{{ $op->codificado_observaciones }}</textarea>
                </td>
            </tr>
        </table>

        <!-- 7. FIRMAS -->
        <table class="tabla-maestra">
            <tr>
                <td width="50%" class="bg-gris txt-centro uppercase">ELABORADO POR:</td>
                <td width="50%" class="bg-gris txt-centro uppercase">APROBADO POR:</td>
            </tr>
            <tr>
                <td class="bg-blanco" style="height: 130px;">
                    <div class="flex items-center justify-center h-full">
                        <x-cfr21-signature-flow 
                            module="PRODUCCION" action="Elaboración Solicitud Codificado (A6PPR0007)" role="elaborado"
                            buttonText="Firmar Elaboración" buttonClass="'px-4 py-2 bg-slate-800 text-white font-bold rounded text-[10px] no-print'"
                            :initialSigned="$op->codificado_elaborado_id ? true : false"
                            :initialName="$op->codificado_elaborado_por ?? ''"
                            :initialDate="$op->codificado_elaborado_at ? $op->codificado_elaborado_at->format('Y-m-d') : ''"
                            :initialHour="$op->codificado_elaborado_at ? $op->codificado_elaborado_at->format('H:i:s') : ''"
                            :initialHtml="$op->codificado_elaborado_id ? app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->codificado_elaborado_por, $op->codificado_elaborado_at) : ''"
                            @signature-verified="/* El evento se maneja globalmente en el contenedor principal */"
                        />
                    </div>
                </td>
                <td class="bg-blanco" style="height: 130px; position: relative;">
                    <div class="flex flex-col items-center justify-center h-full text-center opacity-30">
                        <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">Bloqueado</span>
                        <p class="text-[8px] text-slate-400 mt-1 px-4">La aprobación se realiza en el paso siguiente</p>
                    </div>
                </td>
            </tr>
        </table>

        <div class="mt-6 flex justify-center space-x-4 no-print">
            <a href="{{ route('op.ejecucion') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-bold rounded border border-gray-300">VOLVER</a>
            <button type="submit" id="btn-guardar-solicitud" class="px-6 py-2 bg-emerald-600 text-white font-bold rounded shadow-lg hover:bg-emerald-700" :disabled="!firmado" :style="!firmado ? 'opacity: 0.5; cursor: not-allowed;' : 'opacity: 1; cursor: pointer;'">GUARDAR SOLICITUD</button>
        </div>
    </form>
</div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('solicitudCodificado', () => ({
                firmado: {{ $op->codificado_elaborado_id ? 'true' : 'false' }},
                
                async handleFirmaElaborado(detail) {
                    console.log("--- FIRMA ELABORADO CAPTURADA (CODIFICADO) ---", detail);
                    try {
                        const response = await axios.post('{{ route('op.solicitud_codificado.firmar', $op->lote) }}', {
                            username: detail.username,
                            password: detail.password,
                            type: 'elaborado'
                        });

                        if (response.data.success) {
                            this.firmado = true;
                            Swal.fire('Éxito', 'Firma de elaboración registrada correctamente.', 'success');
                        } else {
                            throw new Error(response.data.message || 'Error al registrar la firma.');
                        }
                    } catch (error) {
                        Swal.fire('Error', error.message || 'Error de comunicación', 'error');
                    }
                }
            }));
        });
    </script>
@endsection
