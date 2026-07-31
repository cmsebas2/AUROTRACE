@extends(isset($is_pdf) && $is_pdf ? 'layouts.empty' : 'layouts.app')

@if(!isset($is_pdf) || !$is_pdf)
    @section('header_title', 'A3PPR0007 Ver 03 - Orden de Producción (Verificación)')
@endif

@section('content')
<div class="formato-a3ppr0007" x-data="verificarOp()">

<style>
    /* FORMATO INDUSTRIAL RÍGIDO A3PPR0007 - CLONACIÓN EXACTA PARA VERIFICACIÓN */
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
    .fila-espaciadora td { border: none !important; height: 10px !important; }

    @media print {
        .no-print { display: none !important; }
        .formato-a3ppr0007 { box-shadow: none !important; padding: 0 !important; width: 100% !important; }
        @page { size: landscape; margin: 0.5cm; }
    }
    .btn-firmar-dt { background: #007bff; color: white; border: none; cursor: pointer; }
    .btn-firmar-dt:hover { background: #0056b3; }
    .btn-guardar-op { background: #0A2540; color: white; padding: 10px 40px; font-weight: bold; border: none; cursor: pointer; border-radius: 4px; }
    .btn-guardar-op:hover { background: #061a2d; }
</style>

    <form id="form-verificar-op" @submit.prevent="submitFinal">
        @csrf
        <input type="hidden" name="op_number" value="{{ $op->op_number }}">
        <input type="hidden" name="lote" value="{{ $op->lote }}">
        <input type="hidden" name="product_id" value="{{ $op->product_id }}">
        <input type="hidden" name="bulk_size_kg" value="{{ $op->bulk_size_kg }}">
        <input type="hidden" name="verificado_por" id="hidden_verificado_por" :value="verificadoPor.name">
        <input type="hidden" name="verificado_id" id="hidden_verificado_id" :value="verificadoPor.id">
        <input type="hidden" name="verificado_fecha" id="hidden_verificado_fecha" :value="verificadoPor.date + ' ' + verificadoPor.hour">
        <input type="hidden" name="explosion_data" :value="JSON.stringify(explosionData)">
        <input type="hidden" name="status" value="VERIFICADO">
        
        {{-- Persistencia de presentaciones --}}
        @foreach($op->opPresentations as $index => $p)
            <input type="hidden" name="presentations[{{ $index }}][id]" value="{{ $p->presentation_id }}">
            <input type="hidden" name="presentations[{{ $index }}][quantity]" value="{{ $p->units_to_produce }}">
        @endforeach
    <!-- ENCABEZADO CLONADO -->
    <table class="tabla-rigida">
        <colgroup>
            <col style="width: 20%;">
            <col style="width: 60%;">
            <col style="width: 20%;">
        </colgroup>
        <tr>
            <td><span class="txt-bold">CODIGO:</span> A3PPR0007</td>
            <td rowspan="4" class="txt-centro txt-bold txt-navy" style="font-size: 18px;">ORDEN DE PRODUCCIÓN</td>
            <td rowspan="4" class="txt-centro">
                <img src="{{ asset('img/logo.png') }}" alt="AUROFARMA" style="max-height: 45px; display: inline-block;">
            </td>
        </tr>
        <tr><td><span class="txt-bold">VERSIÓN:</span> 03</td></tr>
        <tr><td><span class="txt-bold">Fecha de emisión:</span> <span class="txt-bold" style="font-size: 11px;">{{ \Carbon\Carbon::parse($op->manufacturing_date)->format('Y-m-d') }}</span></td></tr>
        <tr><td><span class="txt-bold">Página</span> 1 de 1</td></tr>
    </table>

    <table class="tabla-rigida @if(isset($is_pdf) && $is_pdf) no-print @endif"><tr class="fila-espaciadora"><td></td></tr></table>

    <!-- SECCIÓN 1: IDENTIFICACIÓN (SOLO LECTURA) -->
    <table class="tabla-rigida">
        <colgroup>
            <col style="width: 15%;">
            <col style="width: 35%;">
            <col style="width: 5%;">
            <col style="width: 15%;">
            <col style="width: 30%;">
        </colgroup>
        <tr>
            <td colspan="5" class="bg-gris txt-centro">1. INFORMACION GENERAL DEL PRODUCTO:</td>
        </tr>
        <tr class="fila-espaciadora"><td colspan="5"></td></tr>
        <tr>
            <td class="bg-gris">MAQUILADOR:</td>
            <td><span class="txt-bold">LABORATORIOS AUROFARMA S.A.S</span></td>
            <td style="border:none !important;"></td>
            <td class="bg-gris">LIC. ICA:</td>
            <td><span class="txt-bold">{{ $op->product->ica_license ?? '---' }}</span></td>
        </tr>
        <tr>
            <td class="bg-gris">PRODUCTO:</td>
            <td><span class="txt-bold txt-navy" style="font-size: 12px;">{{ $op->product->name }}</span></td>
            <td style="border:none !important;"></td>
            <td class="bg-gris">FORMA FARMACEUTICA:</td>
            <td><span class="txt-bold">{{ $op->product->pharmaceutical_form ?? '---' }}</span></td>
        </tr>
        <tr>
            <td class="bg-gris">LOTE:</td>
            <td><span class="txt-bold txt-navy" style="font-size: 13px;">{{ $op->lote }}</span></td>
            <td style="border:none !important;"></td>
            <td class="bg-gris">VIDA UTIL:</td>
            <td><span class="txt-bold">{{ $op->product->vigencia_meses ? $op->product->vigencia_meses . ' Meses' : '---' }}</span></td>
        </tr>
        <tr>
            <td class="bg-gris">F. VENCIMIENTO:</td>
            <td><span class="txt-bold txt-navy">{{ \Carbon\Carbon::parse($op->expiration_date)->format('Y-m') }}</span></td>
            <td style="border:none !important;"></td>
            <td class="bg-gris">TAMAÑO LOTE:</td>
            <td>
                <div style="display:flex; width: 100%;">
                    <span class="txt-bold" style="width:70%;">{{ number_format($op->bulk_size_kg, 2, '.', '') }}</span>
                    <span style="width:30%; text-align:right;">U.M. <span class="txt-bold">{{ $op->unit }}</span></span>
                </div>
            </td>
        </tr>
        <tr>
            <td class="bg-gris">O.P. No:</td>
            <td><span class="txt-bold">{{ $op->op_number }}</span></td>
            <td style="border:none !important;"></td>
            <td class="bg-gris">FECHA EMISION:</td>
            <td><span class="txt-bold">{{ \Carbon\Carbon::parse($op->manufacturing_date)->format('Y-m-d') }}</span></td>
        </tr>
    </table>

    <table class="tabla-rigida @if(isset($is_pdf) && $is_pdf) no-print @endif"><tr class="fila-espaciadora"><td></td></tr></table>

    <!-- PRESENTACIONES Y FM CLONADO -->
    <table class="tabla-rigida">
        <colgroup>
            <col style="width: 10%;">
            <col style="width: 30%;">
            <col style="width: 10%;">
            <col style="width: 20%;">
            <col style="width: 15%;">
            <col style="width: 15%;">
        </colgroup>
        <tr>
            <td colspan="3" class="bg-gris">PRESENTACIONES:</td>
            <td rowspan="3" style="border:none !important;"></td>
            <td colspan="2" class="bg-gris txt-centro" style="vertical-align: middle;">FORMULA MAESTRA<br>No.</td>
        </tr>
        <tr class="bg-gris txt-centro">
            <td>CODIGO</td><td>DESCRIPCION</td><td>CANTIDAD</td>
            <td colspan="2" rowspan="2" class="txt-centro" style="vertical-align: middle;">
                <span class="txt-bold txt-navy" style="font-size: 18px;">{{ $op->product->formula_maestra ?? 'S/N' }}</span>
            </td>
        </tr>
        <tbody>
            @foreach($op->opPresentations as $pres)
            <tr>
                <td class="bg-gris txt-centro" style="font-size:10px; font-weight:bold;">{{ $pres->presentation->presentation_code }}</td>
                <td class="txt-bold uppercase">{{ $op->product->name }} x {{ $pres->presentation->name }}</td>
                <td class="txt-centro txt-bold">{{ number_format($pres->units_to_produce, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="tabla-rigida @if(isset($is_pdf) && $is_pdf) no-print @endif"><tr class="fila-espaciadora"><td></td></tr></table>

    <!-- 2. BALANCE GENERAL DE MATERIA PRIMA (VALORES PERSISTIDOS) -->
    @php
        $grupos = $op->opMaterialReconciliations->groupBy('type');
        $tipos = [
            'MATERIA PRIMA' => '2. BALANCE GENERAL DE MATERIA PRIMA',
            'ENVASE' => '3. BALANCE GENERAL DE MATERIAL DE ENVASE',
            'EMPAQUE' => '4. BALANCE GENERAL DE MATERIAL DE EMPAQUE'
        ];
    @endphp

    @foreach($tipos as $tipoKey => $tipoLabel)
    @php $materiales = $grupos->get($tipoKey, collect()); @endphp
    @if($materiales->count())
    <div style="margin-bottom: 15px;">
        <table class="tabla-rigida">
            <colgroup>
                <col style="width: 8%;"><col style="width: 16%;"><col style="width: 28%;"><col style="width: 5%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 9%;"><col style="width: 8%;"><col style="width: 5%;"><col style="width: 5%;">
            </colgroup>
            <tr><td colspan="10" class="bg-gris txt-centro">{{ $tipoLabel }}</td></tr>
            <tr class="bg-gris txt-centro">
                <td colspan="4">INFORMACION INSUMOS</td><td colspan="4">CANTIDAD</td><td colspan="2">RESPONSABLES</td>
            </tr>
            <tr class="bg-gris txt-centro" style="font-size: 9px !important;">
                <td>CODIGO</td><td>LOTE</td><td>MATERIA PRIMA</td><td>U.M.</td><td>REQUERIDA</td><td>ENTREGADA</td><td>DEVOLUCION</td><td>CONSUMO</td><td>ALISTA</td><td>VERIFICA</td>
            </tr>
            <tbody>
                @foreach($materiales as $mat)
                @php $isApi = str_contains(strtolower($mat->function ?? ''), 'api'); @endphp
                <tr>
                    <td class="txt-centro">{{ $mat->material_code }}</td>
                    <td class="txt-centro font-mono" style="font-size: 9px;">{{ $mat->lote ?? '---' }}</td>
                    <td class="txt-bold uppercase">{{ $mat->description }}</td>
                    <td class="txt-centro">{{ $mat->unit }}</td>
                    <td class="bg-gris txt-centro txt-bold" style="font-size: 11px;">
                        {{-- Johann v3.3: Forzado de visualización de reconciliación --}}
                        {{ number_format($mat->required_qty, 2, '.', '') }}
                    </td>
                    <td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endforeach

    <table class="tabla-rigida @if(isset($is_pdf) && $is_pdf) no-print @endif"><tr class="fila-espaciadora"><td></td></tr></table>

    <!-- SECCIÓN DE FIRMAS CLONADA (Consolidada v3.4) -->
    <table class="tabla-rigida tabla-firmas">
        <colgroup>
            <col style="width: 20%;">
            <col style="width: 80%;">
        </colgroup>
        <tr class="bg-gris txt-centro">
            <td>ETIQUETA</td>
            <td>FIRMA ELECTRÓNICA, FECHA Y HORA</td>
        </tr>
        <tr>
            <td class="bg-gris">REALIZADO POR:</td>
            <td class="txt-centro">
                @if($op->realizado_por)
                    <div class="flex flex-col items-center justify-center py-1">
                        {!! app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->realizado_por, $op->realizado_at ?? $op->realizado_fecha) !!}
                    </div>
                @else 
                    <span class="italic text-gray-400">--- Sin Firma ---</span> 
                @endif
            </td>
        </tr>
        <tr>
            <td class="bg-gris">VERIFICADO POR:</td>
            <td class="txt-centro">
                @if($op->status === 'VERIFICADO' && $op->verificado_por)
                    <div class="flex flex-col items-center justify-center py-1">
                        {!! app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->verificado_por, $op->verificado_at ?? $op->verificado_fecha) !!}
                    </div>
                @else
                    <x-cfr21-signature-flow 
                        module="PRODUCCION" 
                        action="Sello de Verificación DT (A3PPR0007)" 
                        role="aseguramiento_calidad"
                        buttonText="FIRMAR COMO ASEGURAMIENTO DE CALIDAD"
                        buttonClass="'btn-firmar-dt px-4 py-2 rounded text-[11px] no-print uppercase transition-all shadow-md w-full font-bold'"
                    />
                @endif
            </td>
        </tr>
        <tr>
            <td class="bg-gris" style="height: 40px !important;">OBSERVACIONES:</td>
            <td><span class="italic text-gray-500">{{ $op->observations ?? 'Registro validado bajo estándares BPM.' }}</span></td>
        </tr>
    </table>

    <div style="text-align: right; margin-top: 30px;" class="no-print">
        <a href="{{ route('op.ejecucion') }}" style="background-color: #f2f2f2; color: #333; padding: 10px 25px; font-weight: bold; border: 1px solid #000; text-decoration: none; margin-right: 15px; display: inline-block;">VOLVER</a>
        <button type="button" onclick="window.print()" style="background-color: #6c757d; color: white; padding: 10px 30px; font-weight: bold; border: none; cursor: pointer; margin-right: 15px;">IMPRIMIR</button>
        <button type="button" @click="submitFinal" id="btn-guardar-op" class="btn-guardar-op">GUARDAR OP</button>
    </div>
    </form>
</div>


<script>
// Johann v10.0: Definición global para máxima compatibilidad con Alpine.js
window.verificarOp = function() {
    @php
        $explosionData = $op->opMaterialReconciliations->map(function($m) {
            return [
                'material_code' => $m->material_code,
                'type' => $m->type,
                'material_name' => $m->description,
                'function' => $m->function,
                'unit' => $m->unit,
                'required_qty' => $m->required_qty,
                'lots' => $m->lote ? [['numero' => $m->lote]] : [],
                'lote' => $m->lote
            ];
        });
    @endphp

    return {
        opStatus: @json($op->status),
        opNumber: @json($op->op_number),
        lote: @json($op->lote),
        productId: @json($op->product_id),
        bulkSize: @json($op->bulk_size_kg),
        verificadoPor: {
            signed: {{ ($op->verificado_id) ? 'true' : 'false' }},
            name: @json($op->verificado_por),
            id: @json($op->verificado_id),
            date: @json($op->verificado_at ? $op->verificado_at->format("Y-m-d") : ""),
            hour: @json($op->verificado_at ? $op->verificado_at->format("H:i:s") : ""),
            html: @json($op->verificado_id ? app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->verificado_por, $op->verificado_at) : "")
        },
        explosionData: @json($explosionData),

        init() {
            console.log("ESPEJO DE VERIFICACIÓN BLINDADO (v10.0)");

            // Johann v9.5: Registro robusto del listener de firma
            window.addEventListener('signature-verified', (event) => {
                if (event.detail.role === 'aseguramiento_calidad') {
                    this.handleVerificado(event.detail);
                }
            });
        },
        
        async submitFinal() {
            if (!this.verificadoPor.signed) {
                return Swal.fire('Acción Bloqueada', 'Debe capturar la firma del Director Técnico antes de guardar.', 'warning');
            }

            const result = await Swal.fire({
                title: '¿Confirmar Guardado de OP?',
                text: "Se registrará el cierre de la orden de producción con las firmas capturadas.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0A2540',
                confirmButtonText: 'Sí, Guardar',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Procesando...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                let form = document.getElementById('form-verificar-op');
                let formData = new FormData(form);
                
                if (@json($op->realizado_por)) formData.set('realizado_por', @json($op->realizado_por));
                if (@json($op->realizado_id)) formData.set('realizado_id', @json($op->realizado_id));
                if (@json($op->realizado_at ? $op->realizado_at->format("Y-m-d H:i:s") : ($op->realizado_fecha ?? ""))) {
                    formData.set('realizado_fecha', @json($op->realizado_at ? $op->realizado_at->format("Y-m-d H:i:s") : ($op->realizado_fecha ?? "")));
                }
                
                if (this.verificadoPor.name) formData.set('verificado_por', this.verificadoPor.name);
                if (this.verificadoPor.id) formData.set('verificado_id', this.verificadoPor.id);
                if (this.verificadoPor.date) {
                    formData.set('verificado_fecha', this.verificadoPor.date + ' ' + this.verificadoPor.hour);
                }
                formData.set('status', 'VERIFICADO');

                try {
                    const response = await axios.post('{{ route("op.store") }}', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                            'Accept': 'application/json'
                        }
                    });

                    if (response.data.success) {
                        Swal.fire('✅ Guardado Exitoso', response.data.message, 'success')
                            .then(() => {
                                window.location.href = response.data.redirect || '{{ route("op.ejecucion") }}';
                            });
                    } else {
                        throw new Error(response.data.message || 'Error al guardar');
                    }
                } catch (error) {
                    const msg = error.response?.data?.message || error.message || 'Error desconocido';
                    Swal.fire('Error', msg, 'error');
                }
            }
        },

        handleVerificado(detail) {
            console.log('Firma recibida:', detail);
            this.verificadoPor.signed = true;
            this.verificadoPor.name = detail.user_name;
            this.verificadoPor.id = detail.user_id || '';
            
            if (detail.timestamp) {
                const parts = detail.timestamp.split(' ');
                this.verificadoPor.date = parts[0] || '';
                this.verificadoPor.hour = parts[1] || '';
            }
            
            this.verificadoPor.html = detail.signature_html;

            if (detail.new_token) {
                document.querySelector('meta[name="csrf-token"]').content = detail.new_token;
                axios.defaults.headers.common['X-CSRF-TOKEN'] = detail.new_token;
            }

            Swal.fire({
                title: 'Verificación Exitosa',
                text: 'Sello de Aseguramiento registrado. Ahora puede GUARDAR la orden.',
                icon: 'success',
                confirmButtonColor: '#0A2540'
            });
        }
    };
};
</script>
@endsection
