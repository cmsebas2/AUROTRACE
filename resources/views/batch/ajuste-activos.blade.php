@extends(isset($is_pdf) && $is_pdf ? 'layouts.empty' : 'layouts.app')

@if(!isset($is_pdf) || !$is_pdf)
    @section('header_title', 'A4PPR0007 Ver 02 - Verificación Ajustes Principios Activos')
@endif

@section('content')
<style>
    /* FORMATO INDUSTRIAL RÍGIDO A4PPR0007 - VERSIÓN FINAL APROBADA */
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
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
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
    .fila-espaciadora td { border: none !important; height: 15px !important; }

    @@media print {
        .no-print { display: none !important; }
        .formato-a4ppr0007 { box-shadow: none !important; border: none !important; padding: 0 !important; width: 100% !important; }
        @@page { size: landscape; margin: 0.5cm; }
    }
</style>

<div class="formato-a4ppr0007">
    <!-- ENCABEZADO INSTITUCIONAL -->
    <table class="tabla-rigida">
        <colgroup>
            <col style="width: 20%;">
            <col style="width: 60%;">
            <col style="width: 20%;">
        </colgroup>
        <tr>
            <td><span class="txt-bold">CODIGO:</span> A4PPR0007</td>
            <td rowspan="4" class="txt-centro txt-bold txt-navy" style="font-size: 18px;">VERIFICACIÓN AJUSTES DE PRINCIPIOS ACTIVOS EN ORDENES DE PRODUCCIÓN</td>
            <td rowspan="4" class="txt-centro">
                <img src="{{ asset('img/logo.png') }}" alt="AUROFARMA" style="max-height: 45px;">
            </td>
        </tr>
        <tr><td><span class="txt-bold">VERSIÓN:</span> 02</td></tr>
        <tr><td><span class="txt-bold">Fecha:</span> 2026-04-16</td></tr>
        <tr><td><span class="txt-bold">Página</span> 1 de 1</td></tr>
    </table>

    <table class="tabla-rigida @if(isset($is_pdf) && $is_pdf) no-print @endif"><tr class="fila-espaciadora"><td></td></tr></table>

    <!-- SECCIÓN 1: IDENTIFICACIÓN (DATOS AUTOMÁTICOS) -->
    <table class="tabla-rigida">
        <colgroup>
            <col style="width: 15%;">
            <col style="width: 35%;">
            <col style="width: 20%;">
            <col style="width: 30%;">
        </colgroup>
        <tr>
            <td class="bg-gris">PRODUCTO:</td>
            <td><input type="text" class="input-invisible txt-bold uppercase" value="{{ $op->product->name }}" readonly></td>
            <td class="bg-gris">REGISTRO ICA:</td>
            <td><input type="text" class="input-invisible txt-bold" value="{{ $op->product->ica_license }}" readonly></td>
        </tr>
        <tr>
            <td class="bg-gris">NÚMERO DE LOTE:</td>
            <td><input type="text" class="input-invisible txt-bold txt-navy" value="{{ $op->lote }}" readonly></td>
            <td class="bg-gris">FECHA DE VENCIMIENTO:</td>
            <td><input type="text" class="input-invisible txt-bold" value="{{ \Carbon\Carbon::parse($op->expiration_date)->format('Y-m') }}" readonly></td>
        </tr>
        <tr>
            <td class="bg-gris">TAMAÑO DE LOTE:</td>
            <td colspan="3">
                <input type="text" class="input-invisible txt-bold" value="{{ number_format($op->bulk_size_kg, 2, '.', '') }} {{ $op->unit }}" readonly>
            </td>
        </tr>
    </table>

    <table class="tabla-rigida @if(isset($is_pdf) && $is_pdf) no-print @endif"><tr class="fila-espaciadora"><td></td></tr></table>

    <!-- SECCIÓN 2: TABLA DE AJUSTES -->
    <form id="form-ajuste" action="{{ route('op.ajuste_activos.store', $op->lote) }}" method="POST">
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
                @forelse($productApis as $api)
                    @php
                        $m = $op->opMaterialReconciliations->where('material_code', $api->material_code)->first();
                        $concentracion = $api->percentage ?? 0;
                        $cantidadTeorica = ($op->bulk_size_kg * $concentracion) / 100;
                        $unidad = $m->unit ?? 'KG';
                    @endphp
                <tr x-data="{ 
                    teorica: {{ $cantidadTeorica }}, 
                    bs: {{ $m->bs_valor ?? 0 }}, 
                    humedad: {{ $m->humedad_valor ?? 0 }}, 
                    get bh() { 
                        let val = (parseFloat(this.bs) * (1 - (parseFloat(this.humedad) / 100)));
                        return isNaN(val) || val <= 0 ? 0 : parseFloat(val.toFixed(2));
                    },
                    get porcentajeAjuste() {
                        if (this.bh <= 0) return 0;
                        let val = ((100 / this.bh) - 1) * 100;
                        return parseFloat(val.toFixed(2));
                    },
                    get final() {
                        let pAjuste = this.porcentajeAjuste;
                        let val = (this.teorica * (1 + (pAjuste / 100)));
                        return val.toFixed(2);
                    }
                }">
                    <td class="txt-bold uppercase">{{ $api->material_name }}</td>
                    <td class="txt-centro">{{ number_format($concentracion, 2, '.', '') }}%</td>
                    <td class="txt-centro">
                        <input type="text" name="ajustes[{{ $api->material_code }}][lote]" 
                               value="{{ $m->lote ?? '---' }}" class="input-invisible txt-centro font-mono" style="font-size: 9px;">
                    </td>
                    <td class="txt-centro txt-bold">{{ number_format($cantidadTeorica, 2, '.', '') }} {{ $unidad }}</td>
                    <td>
                        <div class="flex items-center justify-center">
                            @if(isset($is_pdf) && $is_pdf)
                                <span class="txt-bold">{{ number_format($m->bh_valor ?? 0, 2, '.', '') }}</span>
                            @else
                                <input type="text" class="input-invisible txt-centro font-bold" 
                                       name="ajustes[{{ $api->material_code }}][bh]" 
                                       :value="bh > 0 ? bh.toFixed(2) : '0.00'" readonly style="width: 70%;">
                            @endif
                            <span class="text-[10px] font-bold">%</span>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center justify-center">
                            <input type="number" step="0.01" class="input-invisible txt-centro" 
                                   name="ajustes[{{ $api->material_code }}][bs]" x-model="bs" placeholder="0.00" style="width: 70%;"
                                   {{ $op->verificado_id || $op->realizado_id ? 'disabled' : '' }}>
                            <span class="text-[10px] text-slate-400 font-bold">%</span>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center justify-center">
                            <input type="number" step="0.01" class="input-invisible txt-centro" 
                                   name="ajustes[{{ $api->material_code }}][humedad]" x-model="humedad" placeholder="0.00" style="width: 70%;"
                                   {{ $op->verificado_id || $op->realizado_id ? 'disabled' : '' }}>
                            <span class="text-[10px] text-slate-400 font-bold">%</span>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center justify-center">
                            @if(isset($is_pdf) && $is_pdf)
                                <span class="txt-bold">{{ number_format($m->ajuste_porcentaje ?? 0, 2, '.', '') }}%</span>
                            @else
                                <input type="text" class="input-invisible txt-centro font-bold" 
                                       name="ajustes[{{ $api->material_code }}][ajuste]" 
                                       :value="porcentajeAjuste.toFixed(2) + '%'" readonly>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center justify-center">
                            @if(isset($is_pdf) && $is_pdf)
                                <span class="txt-bold">{{ number_format($m->required_qty ?? 0, 2, '.', '') }}</span>
                            @else
                                <input type="hidden" name="ajustes[{{ $api->material_code }}][cantidad_final]" :value="final">
                                <span class="txt-bold" x-text="final"></span>
                            @endif
                            <span class="text-[9px] ml-1 font-bold">{{ $unidad }}</span>
                        </div>
                    </td>
                    <td><input type="text" class="input-invisible" name="ajustes[{{ $api->material_code }}][observaciones]" placeholder="..." {{ $op->verificado_id || $op->realizado_id ? 'disabled' : '' }}></td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="txt-centro py-8 text-slate-500 italic">No se encontraron principios activos (API) vinculados a este producto en el catálogo oficial.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- SECCIÓN DE ACCIONES (Movida al final por solicitud de Johann) -->
    @php /* El formulario continúa hasta después de las firmas */ @endphp
    @php /* Se eliminó el div de acciones de aquí */ @endphp

    <table class="tabla-rigida @if(isset($is_pdf) && $is_pdf) no-print @endif"><tr class="fila-espaciadora"><td></td></tr></table>

    <!-- SECCIÓN 3: NOTA Y FIRMAS -->
    <table class="tabla-rigida">
        <tr>
            <td class="bg-gris" style="width: 80px;">NOTA:</td>
            <td style="font-style: italic; font-size: 10px; line-height: 1.2;">
                Se debe realizar el ajuste de los principios activos trazadores para cada lote de acuerdo a la valoración de la materia prima. 
                El ajuste se realiza con base en la cantidad teórica establecida en la fórmula maestra. Cualquier desviación debe ser reportada 
                al Director Técnico.
            </td>
        </tr>
    </table>

    <table class="tabla-rigida"><tr class="fila-espaciadora" style="height: 30px !important;"><td></td></tr></table>

    <table class="tabla-rigida">
        <colgroup>
            <col style="width: 50%;">
            <col style="width: 50%;">
        </colgroup>
        <tr>
            <!-- FIRMA REALIZADO POR (DIRECTOR TÉCNICO) -->
            <td style="height: 160px; vertical-align: top; padding: 15px; border: 1px solid #000; background: #fff; position: relative;">
                <div class="text-[11px] font-bold text-[#0A2540] mb-2">Realizado por: Director Técnico y de Producción</div>
                
                <div id="firma-realizado-wrapper" class="firma-realizado-container flex items-center justify-center h-[110px] w-full">
                    @php
                        $rF = null;
                        if ($op->realizado_at) { $rF = $op->realizado_at; }
                        elseif ($op->realizado_fecha) {
                            try { $rF = \Carbon\Carbon::parse($op->realizado_fecha); } catch (\Exception $e) {}
                        }
                    @endphp

                    @if($op->realizado_id || auth()->id() == 1 || auth()->user()->hasRole(['Director Técnico', 'Director de Producción', 'Administrador', 'Super Admin']))
                        <x-cfr21-signature-flow 
                            module="PRODUCCION" 
                            action="Ajuste de Activos (realizado)" 
                            role="director_tecnico"
                            buttonText="Firmar como Director Técnico"
                            buttonClass="'btn-firmar-qa-v3 no-print w-full mt-4'"
                            :initialSigned="$op->realizado_id ? true : false"
                            :initialName="$op->realizado_por ?? ''"
                            :initialDate="$rF ? $rF->format('Y-m-d') : ''"
                            :initialHour="$rF ? $rF->format('H:i:s') : ''"
                            :initialHtml="$op->realizado_id ? app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->realizado_por, $op->realizado_at) : ''"
                            @signature-verified="
                                fetch('{{ route('op.ajuste_activos.firmar', $op->lote) }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                    body: JSON.stringify({ username: $event.detail.username, password: $event.detail.password })
                                }).then(() => enableGuardarBtn());
                            "
                        />
                    @else
                        <div class="absolute bottom-4 left-0 w-full text-center">
                            <span class="text-[8px] text-slate-300 font-bold uppercase tracking-widest italic">Esperando firma autorizada (DT)</span>
                        </div>
                    @endif
                </div>
            </td>

            <!-- FIRMA VERIFICADO POR (ASEGURAMIENTO) - DESACTIVADA EN ESTA VISTA -->
            <td style="height: 160px; vertical-align: top; padding: 15px; border: 1px solid #000; background: #fff; position: relative;">
                <div class="text-[11px] font-bold text-slate-300 mb-2">Verificado por: Aseguramiento de Calidad</div>
                
                <div class="flex flex-col items-center justify-center h-[110px] w-full text-center">
                    <svg class="w-8 h-8 text-slate-100 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    <span class="text-[9px] text-slate-200 font-bold uppercase tracking-widest">Bloqueado</span>
                    <p class="text-[8px] text-slate-100 mt-1">La verificación final se realiza en la OP principal (A3PPR0007)</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="txt-centro no-print" style="margin-top: 40px; display: flex; justify-content: center; gap: 20px; padding-bottom: 50px;">
        <button type="button" onclick="window.print()" class="btn-secundario">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            IMPRIMIR ACTA
        </button>
        
        <button type="submit" id="btn-guardar-ajuste" class="btn-primario" {{ $op->verificado_id || $op->realizado_id ? 'disabled' : '' }} style="{{ $op->verificado_id || $op->realizado_id ? 'opacity: 0.5; cursor: not-allowed;' : '' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
            GUARDAR AJUSTES
        </button>
    </div>
    </form>

    <style>
        .btn-firmar-qa-v3 { background: #0A2540; color: white; padding: 10px 24px; font-size: 11px; border-radius: 6px; border: none; cursor: pointer; font-weight: bold; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-firmar-qa-v3:hover { opacity: 0.85; transform: scale(1.02); }
        
        .btn-firmar-disabled { background: #f1f5f9; color: #cbd5e1; padding: 10px 24px; font-size: 11px; border-radius: 6px; border: none; cursor: not-allowed; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }

        .btn-primario { background: #0A2540; color: white; padding: 10px 25px; font-weight: bold; border: none; cursor: pointer; border-radius: 4px; display: flex; align-items: center; gap: 8px; font-size: 11px; transition: 0.2s; }
        .btn-primario:hover { opacity: 0.9; }
        .btn-secundario { background: #6c757d; color: white; padding: 10px 25px; font-weight: bold; border: none; cursor: pointer; border-radius: 4px; display: flex; align-items: center; gap: 8px; font-size: 11px; }
        
        .firma-final { border: 2px dashed #e2e8f0; padding: 10px; border-radius: 6px; background: #fafafa; text-align: center; width: 90%; position: relative; }
        .firma-final::before { content: 'OFICIAL'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-10deg); font-size: 14px; color: rgba(0,0,0,0.03); font-weight: 900; z-index: 0; pointer-events: none; }
        .firma-final div { position: relative; z-index: 1; font-family: 'Courier New', Courier, monospace; line-height: 1.3; }
        
        .bg-gris { background-color: #D9D9D9 !important; font-weight: bold !important; color: #000 !important; }

        @@media print {
            .no-print { display: none !important; }
            .only-print { display: block !important; }
        }
    </style>

    <script>
        function enableGuardarBtn() {
            const btnGuardar = document.getElementById('btn-guardar-ajuste');
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.style.opacity  = '1';
                btnGuardar.style.cursor   = 'pointer';
            }
        }
    </script>
</div>
@endsection
