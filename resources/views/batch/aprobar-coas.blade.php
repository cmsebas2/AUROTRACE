@extends(isset($is_pdf) && $is_pdf ? 'layouts.empty' : 'layouts.app')

@if(!isset($is_pdf) || !$is_pdf)
    @section('header_title', 'A3PPR0007 Ver 03 - Aprobación de COAs')
@endif

@section('content')
<style>
    /* FORMATO INDUSTRIAL RÍGIDO A3PPR0007 - VERSIÓN FINAL APROBADA */
    .formato-a3ppr0007 { 
        width: 100% !important; 
        max-width: 1150px !important; 
        margin: 0 auto !important; 
        background-color: #ffffff !important; 
        color: #000000 !important; 
        font-family: Arial, sans-serif !important; 
        font-size: 11px !important; 
        padding: 20px !important;
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
    .input-invisible:focus { background-color: #e6f2ff !important; }
    .fila-espaciadora td { border: none !important; height: 10px !important; }

    @media print {
        .no-print { display: none !important; }
        .formato-a3ppr0007 { box-shadow: none !important; padding: 0 !important; width: 100% !important; }
        @page { size: landscape; margin: 0.5cm; }
    }
</style>

<div class="formato-a3ppr0007" x-data="aprobarCoas()" @signature-verified.window="if($event.detail.role === 'aprobado') handleSignature($event.detail)">
    <form action="{{ route('op.aprobar_coas.store', $op->lote) }}" method="POST" id="coas-form" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <input type="hidden" name="op_id" value="{{ $op->id }}">

        <!-- ENCABEZADO -->
        <table class="tabla-rigida">
            <colgroup>
                <col style="width: 20%;">
                <col style="width: 60%;">
                <col style="width: 20%;">
            </colgroup>
            <tr>
                <td><span class="txt-bold">CODIGO:</span> A3PPR0007</td>
                <td rowspan="4" class="txt-centro txt-bold txt-navy" style="font-size: 18px;">APROBACIÓN DE CERTIFICADOS DE ANÁLISIS (COAS)</td>
                <td rowspan="4" class="txt-centro">
                    <img src="{{ asset('img/logo.png') }}" alt="AUROFARMA" style="max-height: 45px; display: inline-block;">
                </td>
            </tr>
            <tr>
                <td><span class="txt-bold">VERSIÓN:</span> 03</td>
            </tr>
            <tr>
                <td><span class="txt-bold">FECHA EMISIÓN:</span> 06/05/2026</td>
            </tr>
            <tr>
                <td><span class="txt-bold">PÁGINA:</span> 1 de 1</td>
            </tr>
        </table>

        <table class="tabla-rigida fila-espaciadora"><tr><td></td></tr></table>

        <!-- 1. GENERALIDADES -->
        <table class="tabla-rigida">
            <colgroup>
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
            </colgroup>
            <tr>
                <td colspan="4" class="bg-gris txt-centro txt-bold">1. GENERALIDADES DE LA ORDEN DE PRODUCCIÓN</td>
            </tr>
            <tr>
                <td class="bg-gris">MAQUILADOR:</td>
                <td>{{ $op->maquilador ?? 'N/A' }}</td>
                <td class="bg-gris">LICENCIA ICA N°:</td>
                <td>{{ $op->product->ica_license }}</td>
            </tr>
            <tr>
                <td class="bg-gris">PRODUCTO:</td>
                <td>{{ $op->product->name }}</td>
                <td class="bg-gris">FECHA MANUFACTURA:</td>
                <td>{{ $op->manufacturing_date->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="bg-gris">FORMA FARMACÉUTICA:</td>
                <td>{{ mb_strtoupper($op->product->pharmaceutical_form) }}</td>
                <td class="bg-gris">N° DE LOTE:</td>
                <td><span class="txt-bold" style="font-size: 13px;">{{ $op->lote }}</span></td>
            </tr>
            <tr>
                <td class="bg-gris">TAMAÑO DEL LOTE:</td>
                <td>{{ number_format($op->bulk_size_kg, 2) }} {{ $op->unit }}</td>
                <td class="bg-gris">FECHA VENCIMIENTO:</td>
                <td>{{ $op->expiration_date->format('m/Y') }}</td>
            </tr>
            <tr>
                <td class="bg-gris">PRESENTACIONES:</td>
                <td colspan="3">
                    @foreach($op->opPresentations as $pres)
                        {{ $pres->units_to_produce }} UND de {{ $pres->presentation->name }}
                        @if(!$loop->last) <br> @endif
                    @endforeach
                </td>
            </tr>
        </table>

        <table class="tabla-rigida fila-espaciadora"><tr><td></td></tr></table>

        <!-- 2. COAS MATERIALES -->
        <table class="tabla-rigida">
            <colgroup>
                <col style="width: 8%;">
                <col style="width: 10%;">
                <col style="width: 25%;">
                <col style="width: 5%;">
                <col style="width: 8%;">
                <col style="width: 12%;">
                <col style="width: 12%;">
                <col style="width: 20%;">
            </colgroup>
            <tr>
                <td colspan="8" class="bg-gris txt-centro txt-bold">2. COAS MATERIALES</td>
            </tr>
            <tr class="bg-gris txt-centro">
                <td>CODIGO</td>
                <td>LOTE</td>
                <td>MATERIAL</td>
                <td>U.M.</td>
                <td>REQUERIDA</td>
                <td>N. ANALISIS</td>
                <td>VENCIMIENTO</td>
                <td>COA PDF</td>
            </tr>
            @foreach($op->opMaterialReconciliations as $mat)
            <tr>
                <td class="txt-centro">{{ $mat->material_code }}</td>
                <td class="txt-centro">{{ $mat->lote }}</td>
                <td>{{ $mat->description }}</td>
                <td class="txt-centro">{{ $mat->unit }}</td>
                <td class="txt-centro">{{ number_format($mat->required_qty, 2) }}</td>
                <td>
                    <input type="text" name="materials[{{ $mat->id }}][n_analisis]" class="input-invisible txt-centro" value="{{ $mat->n_analisis }}" readonly>
                </td>
                <td>
                    <input type="date" name="materials[{{ $mat->id }}][fecha_vencimiento_coa]" class="input-invisible txt-centro" value="{{ $mat->fecha_vencimiento_coa ? \Carbon\Carbon::parse($mat->fecha_vencimiento_coa)->format('Y-m-d') : '' }}" readonly>
                </td>
                <td class="txt-centro">
                    @if($mat->coa_pdf_path)
                        <a href="{{ Storage::url($mat->coa_pdf_path) }}" target="_blank" class="text-aurofarma-blue font-bold hover:underline" style="font-size: 10px;">
                            VER PDF
                        </a>
                    @else
                        <span class="text-gray-400">N/A</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>

        <table class="tabla-rigida fila-espaciadora"><tr><td></td></tr></table>

        <!-- OBSERVACIONES -->
        <table class="tabla-rigida">
            <tr>
                <td class="bg-gris" style="width: 15%;">OBSERVACIONES:</td>
                <td>
                    <textarea name="observaciones" class="input-invisible" rows="3" {{ $op->coas_aprobado_id ? 'readonly' : '' }}>{{ $op->coas_observaciones }}</textarea>
                </td>
            </tr>
        </table>

        <table class="tabla-rigida fila-espaciadora"><tr><td></td></tr></table>

        <!-- 3. RESPONSABLES Y FIRMAS -->
        <table class="tabla-rigida">
            <tr>
                <td colspan="2" class="bg-gris txt-centro txt-bold">3. RESPONSABLES</td>
            </tr>
            <tr class="bg-gris txt-centro">
                <td style="width: 50%;">REALIZADO POR:</td>
                <td style="width: 50%;">APROBADO POR:</td>
            </tr>
            <tr>
                <!-- REALIZADO POR -->
                <td class="bg-slate-50" style="height: 140px; vertical-align: top; padding: 10px !important;">
                    <div class="flex flex-col items-center justify-center h-full opacity-80">
                        {!! app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->coas_realizado_por, $op->coas_realizado_at) !!}
                    </div>
                </td>

                <!-- APROBADO POR -->
                <td class="bg-blanco" style="height: 140px; vertical-align: top; padding: 10px !important;">
                    <div class="flex flex-col items-center justify-center h-full">
                        @if($op->coas_aprobado_id)
                            {!! app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->coas_aprobado_por, $op->coas_aprobado_at) !!}
                        @else
                            @if(!isset($is_pdf) || !$is_pdf)
                                <div class="w-full flex flex-col items-center justify-center space-y-3">
                                    <x-cfr21-signature-flow 
                                        module="CALIDAD" action="Aprobación de COAs (A3PPR0007)" role="aprobado"
                                        :lote="$op->lote"
                                        @signature-verified="/* Manejado globalmente */"
                                    />
                                    <p class="text-[9px] text-gray-400 uppercase font-bold">Pendiente de Firma de Aseguramiento</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- BOTÓN DE GUARDAR GLOBAL -->
        @if((!isset($is_pdf) || !$is_pdf) && !$op->coas_aprobado_id)
            <div class="mt-6 flex justify-center space-x-4 no-print">
                <a href="{{ route('op.calidad') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl border border-gray-300">VOLVER</a>
                
                <a href="{{ route('op.coas.merge', $op->lote) }}" target="_blank" class="px-6 py-3 bg-slate-700 text-white font-bold rounded-xl shadow-lg hover:bg-slate-800 transition-all flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    VER COAS UNIFICADOS
                </a>

                <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition-all flex items-center" :disabled="!firmado" :style="!firmado ? 'opacity: 0.5; cursor: not-allowed;' : 'opacity: 1;'">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    FINALIZAR Y APROBAR COAS
                </button>
            </div>
        @endif

    </form>
</div>

@if(!isset($is_pdf) || !$is_pdf)
<x-cfr21-signature-flow />

<script>
    function aprobarCoas() {
        return {
            firmado: {{ $op->coas_aprobado_id ? 'true' : 'false' }},

            async handleSignature(detail) {
                console.log("--- FIRMA APROBADO CAPTURADA (COAS) ---", detail);
                try {
                    const response = await axios.post(`{{ route('op.coas.firmar', $op->lote) }}`, {
                        username: detail.username,
                        password: detail.password,
                        type: 'aprobado'
                    });

                    if (response.data.success) {
                        this.firmado = true;
                        Swal.fire('Éxito', 'Firma de aprobación registrada correctamente.', 'success');
                    } else {
                        throw new Error(response.data.message || 'Error al registrar la firma.');
                    }
                } catch (error) {
                    Swal.fire('Error', error.message || 'Error de comunicación', 'error');
                }
            }
        }
    }
</script>
@endif
@endsection
