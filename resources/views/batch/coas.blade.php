@extends(isset($is_pdf) && $is_pdf ? 'layouts.empty' : 'layouts.app')

@section('header_title', 'A3PPR0007 Ver 03 - Certificados de Análisis')

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

<div class="formato-a3ppr0007" x-data="coasForm()" @signature-verified.window="if($event.detail.role === 'realizado') handleSignature($event.detail)">
    <form action="{{ route('op.coas.store', $op->lote) }}" method="POST" id="coas-form" enctype="multipart/form-data" autocomplete="off">
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
                <td rowspan="4" class="txt-centro txt-bold txt-navy" style="font-size: 18px;">CERTIFICADOS DE ANÁLISIS (COAS)</td>
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
                <td colspan="8" class="bg-gris txt-centro">2. COAS MATERIALES</td>
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
                    <input type="text" name="materials[{{ $mat->id }}][n_analisis]" class="input-invisible txt-centro" 
                           x-model="materials['{{ $mat->id }}'].n_analisis"
                           {{ $op->coas_realizado_id ? 'readonly' : '' }}>
                </td>
                <td>
                    <input type="date" name="materials[{{ $mat->id }}][fecha_vencimiento_coa]" class="input-invisible txt-centro" 
                           x-model="materials['{{ $mat->id }}'].vencimiento"
                           {{ $op->coas_realizado_id ? 'readonly' : '' }}>
                </td>
                <td class="txt-centro">
                    @if($op->coas_realizado_id)
                        @if($mat->coa_pdf_path)
                            <a href="{{ Storage::url($mat->coa_pdf_path) }}" target="_blank" class="text-aurofarma-blue font-bold hover:underline" style="font-size: 10px;">
                                VER PDF
                            </a>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    @else
                        @if($mat->coa_pdf_path)
                            <div class="mb-1 text-[9px] text-green-600 font-bold">PDF CARGADO</div>
                        @endif
                        <input type="file" name="materials[{{ $mat->id }}][coa_file]" accept="application/pdf" class="input-invisible" style="font-size: 9px;"
                               @change="materials['{{ $mat->id }}'].has_file = true">
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
                <td class="bg-blanco" style="height: 140px; vertical-align: top; padding: 10px !important;">
                    <div class="flex flex-col items-center justify-center h-full">
                        @if($op->coas_realizado_id)
                            {!! app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->coas_realizado_por, $op->coas_realizado_at) !!}
                        @else
                            <div class="w-full flex flex-col items-center justify-center space-y-3">
                                <button type="button" @click="checkAndOpenSignature()" class="bg-[#0A2540] text-white px-4 py-2 rounded text-[10px] hover:bg-blue-900 font-bold w-full shadow-sm transition-all">
                                    FIRMAR REALIZADO
                                </button>
                                <x-cfr21-signature-flow 
                                    module="CALIDAD" action="Carga de COAs (A3PPR0007)" role="realizado"
                                    :lote="$op->lote"
                                    buttonClass="'hidden'"
                                    @signature-verified="/* Manejado globalmente */"
                                />
                                <p class="text-[9px] text-gray-400 uppercase font-bold">Pendiente de Firma Electrónica</p>
                            </div>
                        @endif
                    </div>
                </td>

                <!-- APROBADO POR -->
                <td class="bg-slate-50" style="height: 140px; vertical-align: top; padding: 10px !important;">
                    <div class="flex flex-col items-center justify-center h-full opacity-60">
                        @if($op->coas_aprobado_id)
                            {!! app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->coas_aprobado_por, $op->coas_aprobado_at) !!}
                        @else
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                <span class="text-[10px] font-black tracking-widest uppercase">Bloqueado</span>
                                <p class="text-[8px] mt-1">Requiere aprobación en el módulo de Calidad</p>
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- BOTÓN DE GUARDAR GLOBAL -->
        @if(!$op->coas_aprobado_id)
            <div class="mt-6 flex justify-center space-x-4">
                <a href="{{ route('op.calidad') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl border border-gray-300">VOLVER</a>
                <button type="submit" class="px-8 py-3 bg-emerald-600 text-white font-bold rounded-xl shadow-lg hover:bg-emerald-700 transition-all flex items-center" :disabled="!firmado" :style="!firmado ? 'opacity: 0.5; cursor: not-allowed;' : 'opacity: 1;'">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    GUARDAR Y ENVIAR A REVISIÓN
                </button>
            </div>
        @endif

    </form>
</div>

<x-cfr21-signature-flow />

<script>
    function coasForm() {
        return {
            firmado: {{ $op->coas_realizado_id ? 'true' : 'false' }},
            materials: {
                @foreach($op->opMaterialReconciliations as $mat)
                '{{ $mat->id }}': {
                    n_analisis: '{{ $mat->n_analisis }}',
                    vencimiento: '{{ $mat->fecha_vencimiento_coa ? \Carbon\Carbon::parse($mat->fecha_vencimiento_coa)->format('Y-m-d') : '' }}',
                    has_pdf: {{ $mat->coa_pdf_path ? 'true' : 'false' }},
                    has_file: false
                },
                @endforeach
            },

            validateMaterials() {
                for (const id in this.materials) {
                    const m = this.materials[id];
                    if (!m.n_analisis || !m.vencimiento) return false;
                    if (!m.has_pdf && !m.has_file) return false;
                }
                return true;
            },

            checkAndOpenSignature() {
                if (this.validateMaterials()) {
                    this.$dispatch('open-cfr-modal', { role: 'realizado' });
                } else {
                    Swal.fire({
                        title: 'Datos Incompletos',
                        text: 'Debe completar el N° de Análisis, la Fecha de Vencimiento y cargar el PDF para TODOS los materiales antes de firmar.',
                        icon: 'warning',
                        confirmButtonColor: '#0A2540'
                    });
                }
            },

            async handleSignature(detail) {
                console.log("--- FIRMA REALIZADO CAPTURADA (COAS) ---", detail);
                try {
                    const response = await axios.post(`{{ route('op.coas.firmar', $op->lote) }}`, {
                        username: detail.username,
                        password: detail.password,
                        type: 'realizado'
                    });

                    if (response.data.success) {
                        this.firmado = true;
                        Swal.fire('Éxito', 'Firma de realización registrada correctamente.', 'success');
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
@endsection
