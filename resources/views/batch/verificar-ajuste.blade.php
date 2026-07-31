@extends('layouts.app')

@section('header_title', 'A4PPR0007 Ver 02 - Verificación de Ajustes (Calidad)')

@section('content')
<style>
    /* FORMATO INDUSTRIAL RÍGIDO A4PPR0007 - ESPEJO DE VERIFICACIÓN */
    .formato-a4ppr0007 { 
        width: 100% !important; 
        max-width: 1150px !important; 
        margin: 0 auto !important; 
        background-color: #ffffff !important; 
        color: #000000 !important; 
        font-family: Arial, sans-serif !important; 
        font-size: 11px !important; 
        padding: 20px !important;
        border: 1px solid #eaeaea;
    }
    .tabla-rigida { 
        width: 100% !important; 
        border-collapse: collapse !important; 
        table-layout: fixed !important; 
        margin-bottom: 0 !important; 
    }
    .tabla-rigida th, .tabla-rigida td { 
        border: 1px solid #000000 !important; 
        padding: 3px 5px !important; 
        vertical-align: middle !important; 
        height: 20px !important; 
    }
    .bg-gris { 
        background-color: #D9D9D9 !important; 
        font-weight: bold !important; 
        color: #000 !important;
    }
    .txt-centro { text-align: center !important; }
    .txt-bold { font-weight: bold !important; }
    .txt-navy { color: #0A2540 !important; }
    
    .input-invisible { 
        width: 100% !important; 
        border: none !important; 
        outline: none !important; 
        background: transparent !important; 
        font-size: 11px !important; 
        font-family: Arial, sans-serif !important; 
        padding: 0 !important; 
        margin: 0 !important; 
        color: #000 !important; 
    }
    .fila-espaciadora td { border: none !important; height: 15px !important; }

    @media print {
        .no-print { display: none !important; }
        .formato-a4ppr0007 { box-shadow: none !important; border: none !important; padding: 0 !important; width: 100% !important; }
        @page { size: landscape; margin: 0.5cm; }
    }
</style>

<div class="formato-a4ppr0007" x-data="verificarAjuste()" @signature-verified.window="if($event.detail.role === 'aseguramiento_calidad') handleFirmaQa($event.detail)">
    <!-- ENCABEZADO -->
    <table class="tabla-rigida">
        <colgroup>
            <col style="width: 20%;">
            <col style="width: 60%;">
            <col style="width: 20%;">
        </colgroup>
        <tr>
            <td><span class="txt-bold">CODIGO:</span> A4PPR0007</td>
            <td rowspan="4" class="txt-centro txt-bold txt-navy" style="font-size: 18px;">VERIFICACIÓN AJUSTES DE PRINCIPIOS ACTIVOS</td>
            <td rowspan="4" class="txt-centro">
                <img src="{{ asset('img/logo.png') }}" alt="AUROFARMA" style="max-height: 45px;">
            </td>
        </tr>
        <tr><td><span class="txt-bold">VERSIÓN:</span> 02</td></tr>
        <tr><td><span class="txt-bold">Fecha:</span> 2026-04-16</td></tr>
        <tr><td><span class="txt-bold">Página</span> 1 de 1</td></tr>
    </table>

    <table class="tabla-rigida"><tr class="fila-espaciadora"><td></td></tr></table>

    <!-- SECCIÓN 1: IDENTIFICACIÓN -->
    <table class="tabla-rigida">
        <colgroup>
            <col style="width: 15%;">
            <col style="width: 35%;">
            <col style="width: 20%;">
            <col style="width: 30%;">
        </colgroup>
        <tr>
            <td class="bg-gris">PRODUCTO:</td>
            <td><span class="txt-bold uppercase">{{ $op->product->name }}</span></td>
            <td class="bg-gris">REGISTRO ICA:</td>
            <td><span class="txt-bold">{{ $op->product->ica_license }}</span></td>
        </tr>
        <tr>
            <td class="bg-gris">NÚMERO DE LOTE:</td>
            <td><span class="txt-bold txt-navy">{{ $op->lote }}</span></td>
            <td class="bg-gris">FECHA DE VENCIMIENTO:</td>
            <td><span class="txt-bold">{{ \Carbon\Carbon::parse($op->expiration_date)->format('Y-m') }}</span></td>
        </tr>
        <tr>
            <td class="bg-gris">TAMAÑO DE LOTE:</td>
            <td colspan="3">
                <span class="txt-bold">{{ number_format($op->bulk_size_kg, 2, '.', '') }} {{ $op->unit }}</span>
            </td>
        </tr>
    </table>

    <table class="tabla-rigida"><tr class="fila-espaciadora"><td></td></tr></table>

    <!-- SECCIÓN 2: TABLA DE AJUSTES (READONLY) -->
    <form id="form-verificar-ajuste" action="{{ route('op.verificar_ajuste.store', $op->lote) }}" method="POST">
        @csrf
        <table class="tabla-rigida">
            <thead>
                <tr class="bg-gris">
                    <th rowspan="2" style="width: 18%;">Principios activos trazadores</th>
                    <th rowspan="2" style="width: 10%;">Concentración en el PT</th>
                    <th rowspan="2" style="width: 10%;">Lote de la MP</th>
                    <th rowspan="2" style="width: 10%;">Cantidad teórica</th>
                    <th colspan="2" style="width: 14%;">Valoración de la MP</th>
                    <th rowspan="2" style="width: 8%;">Humedad</th>
                    <th rowspan="2" style="width: 10%;">Ajuste realizado</th>
                    <th rowspan="2" style="width: 10%;">Cantidad final</th>
                    <th rowspan="2" style="width: 10%;">Observaciones</th>
                </tr>
                <tr class="bg-gris">
                    <th style="width: 7%;">BH</th>
                    <th style="width: 7%;">BS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productApis as $api)
                    @php
                        $m = $op->opMaterialReconciliations->where('material_code', $api->material_code)->first();
                        $concentracion = $api->percentage ?? 0;
                        $cantidadTeorica = ($op->bulk_size_kg * $concentracion) / 100;
                        $unidad = $m->unit ?? 'KG';
                    @endphp
                <tr>
                    <td class="txt-bold uppercase">{{ $api->material_name }}</td>
                    <td class="txt-centro">{{ number_format($concentracion, 2, '.', '') }}%</td>
                    <td class="txt-centro font-mono" style="font-size: 9px;">{{ $m->lote ?? '---' }}</td>
                    <td class="txt-centro txt-bold">{{ number_format($cantidadTeorica, 2, '.', '') }} {{ $unidad }}</td>
                    <td class="txt-centro bg-slate-50">{{ number_format($m->bh_valor ?? 0, 2, '.', '') }}</td>
                    <td class="txt-centro bg-slate-50">{{ number_format($m->bs_valor ?? 0, 2, '.', '') }}</td>
                    <td class="txt-centro bg-slate-50">{{ number_format($m->humedad_valor ?? 0, 2, '.', '') }}</td>
                    <td class="txt-centro font-bold text-blue-800">{{ number_format($m->ajuste_porcentaje ?? 0, 2, '.', '') }}%</td>
                    <td class="txt-centro txt-bold bg-gris" style="font-size: 12px;">{{ number_format($m->required_qty, 2, '.', '') }} {{ $unidad }}</td>
                    <td class="text-[9px]">{{ $m->observations }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="tabla-rigida"><tr class="fila-espaciadora" style="height: 30px !important;"><td></td></tr></table>

        <!-- SECCIÓN DE FIRMAS -->
        <table class="tabla-rigida">
            <colgroup><col style="width: 50%;"><col style="width: 50%;"></colgroup>
            <tr>
                <td style="height: 160px; vertical-align: top; padding: 15px; border: 1px solid #000; background: #fafafa;">
                    <div class="text-[11px] font-bold text-slate-500 mb-2">Realizado por: Director Técnico y de Producción</div>
                    <div class="flex items-center justify-center h-[110px] w-full">
                        {!! app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->realizado_por, $op->realizado_at) !!}
                    </div>
                </td>
                <td style="height: 160px; vertical-align: top; padding: 15px; border: 1px solid #000; background: #fff; position: relative;">
                    <div class="text-[11px] font-bold text-[#0A2540] mb-2">Verificado por: Aseguramiento de Calidad</div>
                    <div id="firma-verificado-wrapper" class="flex items-center justify-center h-[110px] w-full">
                        @php
                            $vF = null;
                            if ($op->verificado_at) { $vF = $op->verificado_at; }
                            elseif ($op->verificado_fecha) { try { $vF = \Carbon\Carbon::parse($op->verificado_fecha); } catch (\Exception $e) {} }
                        @endphp
                        <x-cfr21-signature-flow 
                            module="PRODUCCION" 
                            action="Verificación de Ajuste (Calidad)" 
                            role="aseguramiento_calidad"
                            buttonText="Firmar como Aseguramiento de Calidad"
                            buttonClass="'btn-firmar-qa-v3 no-print w-full mt-4'"
                            :initialSigned="$op->verificado_id ? true : false"
                            :initialName="$op->verificado_por ?? ''"
                            :initialDate="$vF ? $vF->format('Y-m-d') : ''"
                            :initialHour="$vF ? $vF->format('H:i:s') : ''"
                            :initialHtml="$op->verificado_id ? app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->verificado_por, $op->verificado_at) : ''"
                        />
                    </div>
                </td>
            </tr>
        </table>

        <div class="txt-centro no-print" style="margin-top: 40px; display: flex; justify-content: center; gap: 15px; padding-bottom: 50px;">
            <a href="{{ route('op.ejecucion') }}" class="btn-secundario">VOLVER</a>
            <button type="submit" id="btn-guardar-verificacion" class="btn-primario" :disabled="!firmado" :style="!firmado ? 'opacity:0.4; cursor:not-allowed;' : ''">
                GUARDAR VERIFICACIÓN
            </button>
        </div>
    </form>

    <style>
        .btn-firmar-qa-v3 { background: #0A2540; color: white; padding: 10px 24px; font-size: 11px; border-radius: 6px; border: none; cursor: pointer; font-weight: bold; text-transform: uppercase; }
        .btn-primario { background: #0A2540; color: white; padding: 10px 25px; font-weight: bold; border: none; cursor: pointer; border-radius: 4px; font-size: 11px; }
        .btn-secundario { background: #6c757d; color: white; padding: 10px 25px; font-weight: bold; border: none; cursor: pointer; border-radius: 4px; font-size: 11px; text-decoration: none; }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('verificarAjuste', () => ({
                firmado: {{ $op->verificado_id ? 'true' : 'false' }},
                
                async handleFirmaQa(detail) {
                    console.log("--- FIRMA QA CAPTURADA (AJUSTE) ---", detail);
                    try {
                        const response = await axios.post('{{ route('op.verificar_ajuste.firmar', $op->lote) }}', {
                            username: detail.username,
                            password: detail.password,
                            reason: 'Verificación de Ajuste A4PPR0007'
                        });

                        if (response.data.success) {
                            this.firmado = true;
                            Swal.fire('Éxito', 'Firma de verificación registrada correctamente.', 'success');
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
</div>
@endsection
