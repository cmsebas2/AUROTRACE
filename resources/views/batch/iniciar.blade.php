@extends(isset($is_pdf) && $is_pdf ? 'layouts.empty' : 'layouts.app')

@if(!isset($is_pdf) || !$is_pdf)
    @section('header_title', 'A3PPR0007 Ver 03 - Orden de Producción')
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

    .btn-remove {
        color: #ff4d4d;
        background: none;
        border: none;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        margin-left: 5px;
        padding: 0 5px;
    }
    .btn-remove:hover { color: #cc0000; }

    @media print {
        .no-print { display: none !important; }
        .formato-a3ppr0007 { box-shadow: none !important; padding: 0 !important; width: 100% !important; }
        @page { size: landscape; margin: 0.5cm; }
    }
</style>

<div class="formato-a3ppr0007" x-data="ordenProduccion()" @signature-verified.window="if($event.detail.role === 'analista_produccion') handleRealizado($event.detail); if($event.detail.role === 'aseguramiento_calidad') handleVerificado($event.detail);">
    <form action="{{ route('op.store') }}" method="POST" id="op-form" @submit.prevent="submitFake" autocomplete="off">
        @csrf
        <input type="hidden" name="op_id" :value="opData ? opData.id : ''">
        <input type="hidden" name="explosion_data" :value="JSON.stringify(explodedMaterials)">
        <input type="hidden" name="bulk_size_kg" :value="bulkSizeTotal">
        <input type="hidden" name="expiration_date" :value="fechaVencimiento">
        <input type="hidden" name="destruction_date" :value="formData.destruction_date">
        <input type="hidden" name="realizado_por" :value="realizadoPor.name">
        <input type="hidden" name="realizado_fecha" :value="realizadoPor.date">
        <input type="hidden" name="verificado_por" :value="verificadoPor.name">
        <input type="hidden" name="verificado_fecha" :value="verificadoPor.date">

        <!-- ENCABEZADO -->
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
            <tr><td style="white-space: nowrap;"><span class="txt-bold">Fecha de emisión:</span> <input type="text" id="input-emision-header" class="input-invisible" value="{{ isset($is_pdf) && $is_pdf ? $op->manufacturing_date->format('Y-m-d') : '' }}" :value="fechaEmision" style="width: 75px; display:inline;" readonly></td></tr>
            <tr><td><span class="txt-bold">Página</span> 1 de 1</td></tr>
        </table>

        <table class="tabla-rigida @if(isset($is_pdf) && $is_pdf) no-print @endif"><tr class="fila-espaciadora"><td></td></tr></table>

        <!-- SECCIÓN 1: IDENTIFICACIÓN -->
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
                <td><input type="text" class="input-invisible txt-bold" value="LABORATORIOS AUROFARMA S.A.S" readonly></td>
                <td style="border:none !important;"></td>
                <td class="bg-gris">LIC. ICA:</td>
                <td><input type="text" id="input-ica" class="input-invisible txt-bold" value="{{ isset($is_pdf) && $is_pdf ? $op->product->ica_license : '' }}" :value="productData.ica_license" readonly></td>
            </tr>
            <tr>
                <td class="bg-gris">PRODUCTO:</td>
                <td>
                    <select id="select-producto" name="product_id" class="input-invisible txt-bold text-blue-900" style="cursor: pointer;" 
                            x-model="producto_id"
                            @change="fetchProductData"
                            :disabled="realizadoPor.signed">

                        <option value="">Seleccione Producto...</option>
                        @if(isset($is_pdf) && $is_pdf)
                            <option value="{{ $op->product_id }}" selected>{{ $op->product->name }}</option>
                        @else
                            @foreach($productos as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </td>
                <td style="border:none !important;"></td>
                <td class="bg-gris">FORMA FARMACEUTICA:</td>
                <td><input type="text" id="input-forma" class="input-invisible txt-bold" value="{{ isset($is_pdf) && $is_pdf ? $op->product->pharmaceutical_form : '' }}" :value="productData.pharmaceutical_form" readonly></td>
            </tr>
            <tr>
                <td class="bg-gris">LOTE:</td>
                <td><input type="text" id="input-lote" name="lote" class="input-invisible txt-bold txt-navy" value="{{ isset($is_pdf) && $is_pdf ? $op->lote : '' }}" x-model="lote" placeholder="N° Lote" required :disabled="realizadoPor.signed"></td>
                <td style="border:none !important;"></td>
                <td class="bg-gris">VIDA UTIL:</td>
                <td><input type="text" id="input-vida" class="input-invisible txt-bold" value="{{ isset($is_pdf) && $is_pdf ? ($op->product->vigencia_meses ? $op->product->vigencia_meses . ' Meses' : '---') : '' }}" :value="productData.vigencia_meses ? productData.vigencia_meses + ' Meses' : '---'" readonly></td>
            </tr>
            <tr>
                <td class="bg-gris">F. VENCIMIENTO:</td>
                <td><input type="text" id="input-vencimiento" class="input-invisible txt-bold text-blue-900" value="{{ isset($is_pdf) && $is_pdf ? $op->expiration_date->format('Y-m') : '' }}" x-model="fechaVencimiento" readonly placeholder="AAAA-MM"></td>
                <td style="border:none !important;"></td>
                <td class="bg-gris">TAMAÑO LOTE:</td>
                <td>
                    <div style="display:flex; width: 100%;">
                        <input type="text" id="tamaño_lote" name="bulk_size_kg" class="input-invisible txt-bold" style="width:70%;" value="{{ isset($is_pdf) && $is_pdf ? number_format($op->bulk_size_kg, 2, '.', '') : '' }}" readonly>
                        <span style="width:30%; text-align:right;">U.M. <span id="span-um" x-text="unidadLote" style="margin-left:2px;">{{ isset($is_pdf) && $is_pdf ? $op->unit : '---' }}</span></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="bg-gris">O.P. No:</td>
                <td><input type="text" id="input-op" name="op_number" class="input-invisible" value="{{ isset($is_pdf) && $is_pdf ? $op->op_number : '' }}" x-model="op" placeholder="Número OP" required :disabled="realizadoPor.signed" autocomplete="off"></td>
                <td style="border:none !important;"></td>
                <td class="bg-gris">FECHA EMISION:</td>
                <td><input type="text" id="input-emision" name="manufacturing_date" class="input-invisible txt-bold" value="{{ isset($is_pdf) && $is_pdf ? $op->manufacturing_date->format('Y-m-d') : '' }}" x-model="fechaEmision" readonly></td>
            </tr>
        </table>

        <table class="tabla-rigida"><tr class="fila-espaciadora"><td></td></tr></table>

        <!-- PRESENTACIONES Y FM -->
        <table class="tabla-rigida">
            <colgroup>
                <col style="width: 10%;">
                <col style="width: 35%;">
                <col style="width: 10%;">
                <col style="width: 15%;">
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
                    <input type="text" id="input-formula-maestra" class="input-invisible txt-centro txt-bold" style="font-size: 18px; color: #0A2540; width: 100%;" readonly :value="productData.formula_maestra || 'S/N'">
                </td>
            </tr>
            <tbody id="tabla-presentaciones-body">
                @if(isset($is_pdf) && $is_pdf)
                    @foreach($op->opPresentations as $pres)
                        <tr>
                            <td class="bg-gris txt-centro" style="font-size:10px; font-weight:bold;">{{ $pres->presentation->presentation_code }}</td>
                            <td class="txt-bold uppercase">{{ $op->product->name }} x {{ $pres->presentation->name }}</td>
                            <td class="txt-centro txt-bold">{{ number_format($pres->units_to_produce, 0) }}</td>
                        </tr>
                    @endforeach
                @else
                    <!-- Las filas se insertan dinámicamente vía Vanilla JS -->
                @endif
            </tbody>
            <tr class="no-print" x-show="!realizadoPor.signed">
                <td colspan="3" class="txt-centro">
                    <button type="button" onclick="agregarFilaNativa()" class="input-invisible text-blue-600 txt-bold" style="cursor:pointer;">+ AGREGAR FILA</button>
                </td>
            </tr>
        </table>

        <table class="tabla-rigida @if(isset($is_pdf) && $is_pdf) no-print @endif"><tr class="fila-espaciadora"><td></td></tr></table>

        <!-- 2. BALANCE GENERAL DE MATERIA PRIMA -->
        <div style="margin-bottom: 15px;">
            <table class="tabla-rigida">
                <colgroup>
<col style="width: 8%;"><col style="width: 14%;"><col style="width: 28%;"><col style="width: 6%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 6%;"><col style="width: 6%;">
                </colgroup>
                <tr><td colspan="10" class="bg-gris txt-centro">2. BALANCE GENERAL DE MATERIA PRIMA</td></tr>
                <tr class="bg-gris txt-centro">
                    <td colspan="4">INFORMACION INSUMOS</td><td colspan="4">CANTIDAD</td><td colspan="2">RESPONSABLES</td>
                </tr>
                <tr class="bg-gris txt-centro" style="font-size: 9px !important;">
                    <td>CODIGO</td><td>LOTE</td><td>MATERIA PRIMA</td><td>U.M.</td><td>REQUERIDA</td><td>ENTREGADA</td><td>DEVOLUCION</td><td>CONSUMO</td><td>ALISTA</td><td>VERIFICA</td>
                </tr>
                <tbody id="body-materia-prima">
                    @if(isset($is_pdf) && $is_pdf)
                        @foreach($op->opMaterialReconciliations->where('type', 'MATERIA PRIMA') as $mat)
                            <tr>
                                <td class="txt-centro">{{ $mat->material_code }}</td>
                                <td class="txt-centro font-mono" style="font-size: 9px;">{{ $mat->lote ?? '---' }}</td>
                                <td class="txt-bold uppercase">{{ $mat->description }}</td>
                                <td class="txt-centro">{{ $mat->unit }}</td>
                                <td class="bg-gris txt-centro txt-bold" style="font-size: 11px;">{{ number_format($mat->required_qty, 2, '.', '') }}</td>
                                <td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td>
                            </tr>
                        @endforeach
                    @else
                        <template x-for="mat in explodedMaterials.filter(m => m.type === 'MATERIA PRIMA')" :key="mat.material_code">
                            <tr class="hover:bg-slate-50">
                                <td class="txt-centro" x-text="mat.material_code"></td>
                                <td class="p-1 border-r-0">
                                    <template x-for="(lot, index) in mat.lots" :key="index">
                                        <div class="flex items-center mb-1 gap-2 border-b border-slate-50 pb-1 last:border-0">
                                            <input type="text" class="input-invisible txt-centro border border-slate-200 rounded-sm text-[10px] w-full bg-slate-50/50" 
                                                   x-model="lot.numero" placeholder="N° Lote"
                                                   :disabled="realizadoPor.signed">
                                            <div class="flex items-center gap-1 no-print">
                                                <button type="button" @click="addLot(mat.material_code)" x-show="index === 0 && !realizadoPor.signed"
                                                        class="w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold hover:bg-blue-800">+</button>
                                                <button type="button" @click="removeLot(mat.material_code, index)" x-show="index > 0 && !realizadoPor.signed"
                                                        class="w-4 h-4 rounded-full bg-red-500 text-white flex items-center justify-center text-[10px] font-bold hover:bg-red-700">&times;</button>
                                            </div>
                                        </div>
                                    </template>
                                </td>
                                <td class="txt-bold uppercase" x-text="mat.description"></td>
                                <td class="txt-centro" x-text="mat.unit"></td>
                                <td class="bg-gris txt-centro txt-bold" :id="'req-qty-' + mat.material_code" :class="mat.required_qty === 'PENDIENTE DE AJUSTE' ? 'text-amber-600 italic text-[9px]' : ''" x-text="mat.required_qty"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                            </tr>
                        </template>
                        <tr x-show="explodedMaterials.filter(m => m.type === 'MATERIA PRIMA').length === 0">
                            <td colspan="10" class="txt-centro text-slate-400 py-4 italic">Seleccione un producto con fórmula maestra...</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <table class="tabla-rigida"><tr class="fila-espaciadora"><td></td></tr></table>

        <!-- 3. BALANCE GENERAL DE MATERIAL DE ENVASE -->
        <div style="margin-bottom: 15px;">
            <table class="tabla-rigida">
                <colgroup>
<col style="width: 8%;"><col style="width: 14%;"><col style="width: 28%;"><col style="width: 6%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 6%;"><col style="width: 6%;">
                </colgroup>
                <tr><td colspan="10" class="bg-gris txt-centro">3. BALANCE GENERAL DE MATERIAL DE ENVASE</td></tr>
                <tr class="bg-gris txt-centro">
                    <td colspan="4">INFORMACION INSUMOS</td><td colspan="4">CANTIDAD</td><td colspan="2">RESPONSABLES</td>
                </tr>
                <tr class="bg-gris txt-centro" style="font-size: 9px !important;">
                    <td>CODIGO</td><td>LOTE</td><td>MATERIAL DE ENVASE</td><td>U.M.</td><td>REQUERIDA</td><td>ENTREGADA</td><td>DEVOLUCION</td><td>CONSUMO</td><td>ALISTA</td><td>VERIFICA</td>
                </tr>
                <tbody id="body-envase">
                    @if(isset($is_pdf) && $is_pdf)
                        @foreach($op->opMaterialReconciliations->where('type', 'ENVASE') as $mat)
                            <tr>
                                <td class="txt-centro">{{ $mat->material_code }}</td>
                                <td class="txt-centro font-mono" style="font-size: 9px;">{{ $mat->lote ?? '---' }}</td>
                                <td class="txt-bold uppercase">{{ $mat->description }}</td>
                                <td class="txt-centro">{{ $mat->unit }}</td>
                                <td class="bg-gris txt-centro txt-bold" style="font-size: 11px;">{{ number_format($mat->required_qty, 0, '.', '') }}</td>
                                <td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td>
                            </tr>
                        @endforeach
                    @else
                        <template x-for="mat in explodedMaterials.filter(m => m.type === 'ENVASE')" :key="mat.material_code">
                            <tr class="hover:bg-slate-50">
                                <td class="txt-centro" x-text="mat.material_code"></td>
                                <td class="p-1 border-r-0">
                                    <template x-for="(lot, index) in mat.lots" :key="index">
                                        <div class="flex items-center mb-1 gap-2 border-b border-slate-50 pb-1 last:border-0">
                                            <input type="text" class="input-invisible txt-centro border border-slate-200 rounded-sm text-[10px] w-full bg-slate-50/50" 
                                                   x-model="lot.numero" placeholder="N° Lote"
                                                   :disabled="realizadoPor.signed">

                                            <div class="flex items-center gap-1 no-print">
                                                <button type="button" @click="addLot(mat.material_code)" x-show="index === 0 && !realizadoPor.signed"
                                                        class="w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold hover:bg-blue-800">+</button>
                                                <button type="button" @click="removeLot(mat.material_code, index)" x-show="index > 0 && !realizadoPor.signed"
                                                        class="w-4 h-4 rounded-full bg-red-500 text-white flex items-center justify-center text-[10px] font-bold hover:bg-red-700">&times;</button>
                                            </div>
                                        </div>
                                    </template>
                                </td>
                                <td class="txt-bold uppercase" x-text="mat.description"></td>
                                <td class="txt-centro" x-text="mat.unit"></td>
                                <td class="bg-gris txt-centro txt-bold" :id="'req-qty-' + mat.material_code" x-text="mat.required_qty"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                            </tr>
                        </template>
                        <tr x-show="explodedMaterials.filter(m => m.type === 'ENVASE').length === 0">
                            <td colspan="10" class="txt-centro text-slate-400 py-4 italic">Sin material de envase registrado.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <table class="tabla-rigida"><tr class="fila-espaciadora"><td></td></tr></table>

        <!-- 4. BALANCE GENERAL DE MATERIAL DE EMPAQUE -->
        <div style="margin-bottom: 15px;">
            <table class="tabla-rigida">
                <colgroup>
                    <col style="width: 8%;"><col style="width: 14%;"><col style="width: 28%;"><col style="width: 6%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 8%;"><col style="width: 6%;"><col style="width: 6%;">
                </colgroup>
                <tr><td colspan="10" class="bg-gris txt-centro">4. BALANCE GENERAL DE MATERIAL DE EMPAQUE</td></tr>
                <tr class="bg-gris txt-centro">
                    <td colspan="4">INFORMACION INSUMOS</td><td colspan="4">CANTIDAD</td><td colspan="2">RESPONSABLES</td>
                </tr>
                <tr class="bg-gris txt-centro" style="font-size: 9px !important;">
                    <td>CODIGO</td><td>LOTE</td><td>MATERIAL DE EMPAQUE</td><td>U.M.</td><td>REQUERIDA</td><td>ENTREGADA</td><td>DEVOLUCION</td><td>CONSUMO</td><td>ALISTA</td><td>VERIFICA</td>
                </tr>
                <tbody id="body-empaque">
                    @if(isset($is_pdf) && $is_pdf)
                        @foreach($op->opMaterialReconciliations->where('type', 'EMPAQUE') as $mat)
                            <tr>
                                <td class="txt-centro">{{ $mat->material_code }}</td>
                                <td class="txt-centro font-mono" style="font-size: 9px;">{{ $mat->lote ?? '---' }}</td>
                                <td class="txt-bold uppercase">{{ $mat->description }}</td>
                                <td class="txt-centro">{{ $mat->unit }}</td>
                                <td class="bg-gris txt-centro txt-bold" style="font-size: 11px;">{{ number_format($mat->required_qty, 0, '.', '') }}</td>
                                <td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td><td class="bg-slate-50"></td>
                            </tr>
                        @endforeach
                    @else
                        <template x-for="mat in explodedMaterials.filter(m => m.type === 'EMPAQUE')" :key="mat.material_code">
                            <tr class="hover:bg-slate-50">
                                <td class="txt-centro" x-text="mat.material_code"></td>
                                <td class="p-1 border-r-0">
                                    <template x-for="(lot, index) in mat.lots" :key="index">
                                        <div class="flex items-center mb-1 gap-2 border-b border-slate-50 pb-1 last:border-0">
                                            <input type="text" class="input-invisible txt-centro border border-slate-200 rounded-sm text-[10px] w-full bg-slate-50/50" 
                                                   x-model="lot.numero" placeholder="N° Lote"
                                                   :disabled="realizadoPor.signed">

                                            <div class="flex items-center gap-1 no-print">
                                                <button type="button" @click="addLot(mat.material_code)" x-show="index === 0 && !realizadoPor.signed"
                                                        class="w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold hover:bg-blue-800">+</button>
                                                <button type="button" @click="removeLot(mat.material_code, index)" x-show="index > 0 && !realizadoPor.signed"
                                                        class="w-4 h-4 rounded-full bg-red-500 text-white flex items-center justify-center text-[10px] font-bold hover:bg-red-700">&times;</button>
                                            </div>
                                        </div>
                                    </template>
                                </td>
                                <td class="txt-bold uppercase" x-text="mat.description"></td>
                                <td class="txt-centro" x-text="mat.unit"></td>
                                <td class="bg-gris txt-centro txt-bold" :id="'req-qty-' + mat.material_code" x-text="mat.required_qty"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                                <td class="bg-slate-100"><input type="text" class="input-invisible txt-centro cursor-not-allowed" readonly placeholder="---"></td>
                            </tr>
                        </template>
                        <tr x-show="explodedMaterials.filter(m => m.type === 'EMPAQUE').length === 0">
                            <td colspan="10" class="txt-centro text-slate-400 py-4 italic">Sin material de empaque registrado.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>


        <table class="tabla-rigida"><tr class="fila-espaciadora"><td></td></tr></table>

        <!-- SECCIÓN DE FIRMAS (Johann v3.0) -->
        <table class="tabla-rigida tabla-firmas" style="table-layout: fixed;">
            <colgroup>
                <col style="width: 20%;">
                <col style="width: 80%;">
            </colgroup>
            <tr class="bg-gris txt-centro">
                <td style="border:none !important; background-color: transparent !important;"></td>
                <td>FIRMA (RESPONSABLE), FECHA Y HORA</td>
            </tr>
            <tr>
                <td class="bg-gris">REALIZADO POR:</td>
                <td class="txt-centro">
                    <x-cfr21-signature-flow 
                        module="PRODUCCION" 
                        action="Sello de Analista (A3PPR0007)" 
                        role="analista_produccion"
                        buttonText="FIRMAR REALIZADO"
                    />

                    @if(isset($is_pdf) && $is_pdf)
                        @php
                            $rF = null;
                            if ($op->realizado_at) { $rF = $op->realizado_at; }
                            elseif ($op->realizado_fecha) {
                                try { $rF = \Carbon\Carbon::parse($op->realizado_fecha); } catch (\Exception $e) {}
                            }
                        @endphp
                        @if($rF)
                            <div class="flex flex-col items-center justify-center py-1">
                                {!! app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->realizado_por, $rF) !!}
                            </div>
                        @else
                            <span class="text-slate-400 italic">--- Sin Firma ---</span>
                        @endif
                    @endif

                    <input type="hidden" name="realizado_por" :value="realizadoPor.name">
                    <input type="hidden" name="realizado_fecha" :value="realizadoPor.date">
                </td>
            </tr>
            <tr>
                <td class="bg-gris">VERIFICADO POR:</td>
                <td class="txt-centro">
                    <div x-data="{ 
                        get isAdjustmentPhase() {
                            // Si no hay materiales cargados aún, no podemos determinar, asumimos bloqueado por seguridad
                            if (explodedMaterials.length === 0) return true;
                            
                            return explodedMaterials.some(m => (m.function || '').toUpperCase() === 'API') && 
                                   (!opData || opData.status !== 'AJ_FIRM');
                        },
                        get canSign() { 
                            return producto_id && realizadoPor.signed && !this.isAdjustmentPhase;
                        } 
                    }">
                        <!-- BOTÓN INACTIVO (BLOQUEADO) -->
                        <div x-show="!canSign" 
                             class="w-full py-2 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-400 cursor-not-allowed border-dashed border-2 border-gray-300 opacity-60 flex items-center justify-center select-none">
                            <i class="fas fa-lock mr-2"></i> FIRMAR VERIFICADO
                        </div>

                        <!-- BOTÓN ACTIVO (SÓLO SI canSign ES TRUE) -->
                        <div x-show="canSign">
                            <x-cfr21-signature-flow 
                                module="PRODUCCION" 
                                action="Sello de Verificación DT (A3PPR0007)" 
                                role="aseguramiento_calidad"
                                buttonText="FIRMAR VERIFICADO"
                            />
                        </div>

                        <template x-if="isAdjustmentPhase && producto_id">
                            <div class="mt-1 text-[8px] text-amber-600 font-bold uppercase italic tracking-tighter">
                                <i class="fas fa-lock mr-1"></i> Requiere Ajuste de Activos Previo
                            </div>
                        </template>
                    </div>

                    @if(isset($is_pdf) && $is_pdf)
                        @php
                            $vF = null;
                            if ($op->verificado_at) { $vF = $op->verificado_at; }
                            elseif ($op->verificado_fecha) {
                                try { $vF = \Carbon\Carbon::parse($op->verificado_fecha); } catch (\Exception $e) {}
                            }
                        @endphp
                        @if($vF)
                            <div class="flex flex-col items-center justify-center py-1">
                                {!! app(\App\Services\Cfr21SignatureService::class)->renderSignatureHtml($op->verificado_por, $vF) !!}
                            </div>
                        @else
                            <span class="text-slate-400 italic">--- Sin Firma ---</span>
                        @endif
                    @endif

                    <input type="hidden" name="verificado_por" :value="verificadoPor.name">
                    <input type="hidden" name="verificado_fecha" :value="verificadoPor.date">
                </td>
            </tr>
            <tr>
                <td class="bg-gris" style="height: 40px !important;">OBSERVACIONES:</td>
                <td><input type="text" class="input-invisible" style="height: 100%;"></td>
            </tr>
        </table>


        <div style="text-align: right; margin-top: 30px;" class="no-print">
            <button type="button" onclick="window.print()" style="background-color: #f2f2f2; color: #333; padding: 10px 25px; font-weight: bold; border: 1px solid #000; cursor: pointer; margin-right: 15px;">IMPRIMIR FORMATO</button>
            <button type="button" @click="submitFake" 
                    style="background-color: #0A2540; color: white; padding: 10px 30px; font-weight: bold; border: none; cursor: pointer;"
                    x-text="(opData && opData.status === 'AJ_FIRM') ? 'VERIFICAR Y CERRAR ORDEN' : 'GENERAR ORDEN DE PRODUCCIÓN'">
                GENERAR ORDEN DE PRODUCCIÓN
            </button>
        </div>


    </form>
</div>

<script src="{{ asset('js/aurotrace-core.js') }}"></script>

<script>
/* =========================================================
   ⚠️ CRITICAL CORE LOGIC - DO NOT MODIFY - VER 1.0
   - Motor de Cálculo (Fuerza Bruta)
   - Fetch de Fórmula Maestra y Explosión de Materiales
   - Generación de Filas de Presentación
   Cualquier alteración a este flujo está ESTRICTAMENTE PROHIBIDA
   sin autorización de nivel ADMIN (Johann).
   ========================================================= */

window.productCatalog = @json($productos->keyBy('id'));

function agregarFilaNativa() {
    console.log("--- Agregando Fila de Presentación (Johann v2.1) ---");
    const container = document.getElementById('tabla-presentaciones-body');
    if (!container) return;

    // Obtener presentaciones del producto seleccionado (ya cargadas por fetchProductData)
    const alpine = window.alpineComponentContext;
    const presentaciones = (alpine && alpine.productData && alpine.productData.presentations)
        ? alpine.productData.presentations
        : [];

    const selectProducto = document.getElementById('select-producto');
    const nombreProducto = selectProducto ? selectProducto.options[selectProducto.selectedIndex].text : '';

    if (presentaciones.length === 0) {
        alert('Seleccione un producto primero para cargar sus presentaciones.');
        return;
    }

    const row = document.createElement('tr');
    row.className = 'fila-presentacion';

    // Construir opciones con código, nombre y peso extraido del nombre
    let opcionesHTML = '<option value="">Seleccione...</option>';
    presentaciones.forEach(pre => {
        const match = (pre.name || '').match(/(\d+(?:\.\d+)?)/);
        const peso = match ? parseFloat(match[1]) : 0;
        opcionesHTML += `<option value="${pre.id}" data-code="${pre.presentation_code || ''}" data-peso="${peso}">${nombreProducto} x ${pre.name}</option>`;
    });

    const isSigned = (alpine && alpine.realizadoPor && alpine.realizadoPor.signed);

    row.innerHTML = `
        <td class="bg-gris txt-centro codigo-presentacion" style="font-size:10px; font-weight:bold;">---</td>
        <td>
            <select name="presentaciones[][id]" class="input-invisible txt-bold peso-presentacion" style="width:100%;"
                    ${isSigned ? 'disabled' : ''}
                    onchange="window.actualizarFilaPresentacion(this); window.calcularFuerzaBruta();">
                ${opcionesHTML}
            </select>
        </td>
        <td style="position: relative;">
            <input type="number" name="presentaciones[][quantity]" class="input-invisible txt-centro cantidad-presentacion"
                   style="width:100%;" value="0" step="1"
                   ${isSigned ? 'readonly' : ''}
                   oninput="window.calcularFuerzaBruta()"
                   onkeyup="window.calcularFuerzaBruta()"
                   onchange="window.calcularFuerzaBruta()">
            
            ${!isSigned ? `<button type="button" class="btn-remove" style="position: absolute; right: -25px; top: 0;" onclick="this.closest('tr').remove(); window.calcularFuerzaBruta();" title="Eliminar">&times;</button>` : ''}
        </td>
    `;
    container.appendChild(row);
    if (typeof window.calcularFuerzaBruta === 'function') window.calcularFuerzaBruta();
}

// Actualiza el código de presentación en la celda de la fila cuando el usuario selecciona
window.actualizarFilaPresentacion = function(selectEl) {
    const option = selectEl.options[selectEl.selectedIndex];
    const code = option ? (option.getAttribute('data-code') || '---') : '---';
    const td = selectEl.closest('tr')?.querySelector('.codigo-presentacion');
    if (td) td.textContent = code;
};
// Carga las presentaciones guardadas en DB al regresar del Ajuste de Activos
window.cargarPresentacionesGuardadas = function(opPresentations) {
    if (!opPresentations || opPresentations.length === 0) return;

    const container = document.getElementById('tabla-presentaciones-body');
    if (!container) return;

    // Limpiar filas previas (evitar duplicados)
    container.innerHTML = '';

    opPresentations.forEach(opPres => {
        const pre = opPres.presentation;
        if (!pre) return;

        // Calcular peso desde el nombre (mismo regex que agregarFilaNativa)
        const match = (pre.name || '').match(/(\d+(?:\.\d+)?)/);
        const peso = match ? parseFloat(match[1]) : 0;
        const code = pre.presentation_code || '---';

        const isSigned = (window.alpineComponentContext && window.alpineComponentContext.realizadoPor && window.alpineComponentContext.realizadoPor.signed);

        const selectProducto = document.getElementById('select-producto');
        const nombreProducto = selectProducto ? selectProducto.options[selectProducto.selectedIndex].text : '';

        const row = document.createElement('tr');
        row.className = 'fila-presentacion';
        row.innerHTML = `
            <td class="bg-gris txt-centro codigo-presentacion" style="font-size:10px; font-weight:bold;">${code}</td>
            <td>
                <select name="presentaciones[][id]" class="input-invisible txt-bold peso-presentacion" style="width:100%;"
                        ${isSigned ? 'disabled' : ''}
                        onchange="window.actualizarFilaPresentacion(this); window.calcularFuerzaBruta();">
                    <option value="${pre.id}" data-code="${code}" data-peso="${peso}" selected>${nombreProducto} x ${pre.name}</option>
                </select>
            </td>
            <td style="position: relative;">
                <input type="number" name="presentaciones[][quantity]" class="input-invisible txt-centro cantidad-presentacion"
                       style="width:100%;" value="${opPres.units_to_produce || 0}" step="1"
                       ${isSigned ? 'readonly' : ''}
                       oninput="window.calcularFuerzaBruta()"
                       onkeyup="window.calcularFuerzaBruta()"
                       onchange="window.calcularFuerzaBruta()">
                
                ${!isSigned ? `<button type="button" class="btn-remove" style="position: absolute; right: -25px; top: 0;" onclick="this.closest('tr').remove(); window.calcularFuerzaBruta();" title="Eliminar">&times;</button>` : ''}
            </td>
        `;
        container.appendChild(row);
    });

    // Recalcular masa total y explosión con los datos recargados
    if (typeof window.calcularFuerzaBruta === 'function') window.calcularFuerzaBruta();
    console.log('--- Presentaciones guardadas restauradas: ' + opPresentations.length + ' filas ---');
};

    document.addEventListener('alpine:init', () => {
    Alpine.data('ordenProduccion', () => ({
        producto_id: '',
        fechaEmision: new Date().toISOString().split('T')[0],
        fechaVencimiento: '',
        lote: '',
        op: '',
        opData: @json($op ?? null),
        
        catalog: window.productCatalog,

        formData: {
            bulk_size_kg: 0,
            destruction_date: '',
            presentations: []
        },
        productData: {
            ica_license: '',
            pharmaceutical_form: '',
            vigencia_meses: 0,
            base_batch_size: 0,
            base_unit: 'KG',
            formula_maestra: '',
            ingredients: [],
            presentations: []
        },
        bulkSizeTotal: 0,
        unidadLote: '---',
        explodedMaterials: [],
        realizadoPor: { signed: false, name: '', id: '', date: '', hour: '', html: '' },
        verificadoPor: { signed: false, name: '', id: '', date: '', hour: '', html: '' },
        currentUser: {
            name: '{{ Auth::user()->name }}',
            role: '{{ Auth::user()->role }}'
        },

        // MANEJADORES UNIVERSALES DE FIRMA (Fase 1)
        handleRealizado(detail) {
            console.log('--- FIRMA REALIZADO CAPTURADA ---', detail);
            this.realizadoPor.signed = true;
            this.realizadoPor.name = detail.user_name;
            const tsParts = detail.timestamp ? detail.timestamp.split(' ') : ['', ''];
            this.realizadoPor.date = tsParts[0];
            this.realizadoPor.hour = tsParts[1];
            this.realizadoPor.html = detail.signature_html;
            
            // Refresco de Token (Protocolo de Rescate)
            if (detail.new_token) {
                document.querySelector('meta[name="csrf-token"]').content = detail.new_token;
            }

            this.updateCsrf(detail.new_token);
        },

        handleVerificado(detail) {
            this.verificadoPor.signed = true;
            this.verificadoPor.name = detail.user_name;
            this.verificadoPor.id = detail.user_id || '';
            const parts = detail.timestamp ? detail.timestamp.split(' ') : ['', ''];
            this.verificadoPor.date = parts[0];
            this.verificadoPor.hour = parts[1];
            this.verificadoPor.html = detail.signature_html;
            Swal.fire({
                title: 'Verificación Exitosa',
                text: 'Aseguramiento de Calidad ha sellado el registro.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        },

        validarFormularioCompleto() {
            if (!this.producto_id || !this.lote || !this.op || this.bulkSizeTotal <= 0) {
                Swal.fire('Acción Bloqueada', 'Faltan datos obligatorios (Producto, Lote, OP o Cantidades).', 'warning');
                return false;
            }
            return true;
        },

        // ASIGNACIÓN MULTILOTE (Johann v2.0)
        addLot(materialCode) {
            const mat = this.explodedMaterials.find(m => m.material_code === materialCode);
            if (mat && !this.realizadoPor.signed) {
                mat.lots.push({ numero: '' });
            }
        },
        removeLot(materialCode, index) {
            const mat = this.explodedMaterials.find(m => m.material_code === materialCode);
            if (mat && mat.lots.length > 1 && !this.realizadoPor.signed) {
                mat.lots.splice(index, 1);
            }
        },
        getMaterialBalance(materialCode) {
            const mat = this.explodedMaterials.find(m => m.material_code === materialCode);
            if (!mat) return 0;
            return mat.lots.reduce((sum, lot) => sum + (parseFloat(lot.cantidad) || 0), 0);
        },
        isBalanceOk(mat) {
            if (mat.required_qty === 'PENDIENTE A AJUSTE') return true;
            const req = parseFloat(mat.required_qty) || 0;
            const balance = this.getMaterialBalance(mat.material_code);
            return balance >= req;
        },
        get hayMaterialesPendientes() {
            // Se elimina la validación de balance (Requerido vs Entregado) 
            // ya que eso pertenece a la fase de Reconciliación.
            return false;
        },

        get mostrarBotonVerificado() {
            // Solo mostrar si NO hay APIs pendientes de ajuste
            const hayPendientes = this.explodedMaterials.some(m => m.required_qty === 'PENDIENTE A AJUSTE');
            return !hayPendientes;
        },

        getNowFormatted() {
            const now = new Date();
            const d = String(now.getDate()).padStart(2, '0');
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const y = now.getFullYear();
            const h = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            return `${d}/${m}/${y} ${h}:${min}:${s}`;
        },

        init() {
            window.alpineComponentContext = this;
            window.calcularFuerzaBruta = () => this.calcularFuerzaBruta();

            // Watcher de producto: carga fórmula e ingredientes al seleccionar
            this.$watch('producto_id', (id) => {
                if (!id) {
                    this.productData = { ica_license: '', pharmaceutical_form: '', vigencia_meses: 0, base_batch_size: 1, base_unit: 'KG', formula_maestra: '', ingredients: [], presentations: [] };
                    this.explodedMaterials = [];
                } else {
                    this.fetchProductData();
                }
            });
        },

        async fetchProductData() {
            if (!this.producto_id) return;
            console.log('--- FETCHING PRODUCT DATA ID: ' + this.producto_id + ' ---');
            try {
                const response = await fetch(`/api/products/${this.producto_id}/explosion-data`);
                const data = await response.json();
                
                this.productData.ica_license = data.ica_license;
                this.productData.pharmaceutical_form = data.pharmaceutical_form;
                this.productData.vigencia_meses = data.vigencia_meses;
                this.productData.base_batch_size = parseFloat(data.base_batch_size) || 1;
                this.productData.base_unit = data.base_unit;
                this.productData.formula_maestra = data.formula_maestra || 'S/N';
                this.productData.ingredients = data.ingredients || [];
                this.productData.presentations = data.presentations || [];
                
                console.log('--- PRODUCT DATA LOADED ---', this.productData);

                // Calcular vencimiento
                const hoy = new Date();
                if (this.productData.vigencia_meses) {
                    hoy.setMonth(hoy.getMonth() + parseInt(this.productData.vigencia_meses));
                    this.fechaVencimiento = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0');
                }

                this.explodeFormula();
            } catch (error) {
                console.error('Error fetching product data:', error);
            }
        },

        calcularFuerzaBruta() {
            let totalMasa = 0;
            let totalUnidades = 0;
            console.log("--- INICIANDO CÁLCULO JOHANN ---");
            
            const rows = document.querySelectorAll('.fila-presentacion');
            rows.forEach(row => {
                const select = row.querySelector('.peso-presentacion');
                const input = row.querySelector('.cantidad-presentacion');
                
                if (select && input) {
                    const option = select.options[select.selectedIndex];
                    const P = parseFloat(option?.getAttribute('data-peso') || 0);
                    const C = parseFloat(input.value || 0);
                    
                    if (!isNaN(P) && !isNaN(C)) {
                        totalMasa += (C * P);
                        totalUnidades += C;
                    }
                }
            });
            
            // BLINDAJE: AuroFormat puede no estar disponible si aurotrace-core.js no cargó
            const fmt = (n) => (window.AuroFormat && typeof window.AuroFormat.decimal === 'function')
                ? window.AuroFormat.decimal(n)
                : parseFloat(n).toFixed(2);

            this.bulkSizeTotal = parseFloat(fmt(totalMasa));
            const bulkInput = document.getElementById('tamaño_lote');
            if (bulkInput) bulkInput.value = fmt(this.bulkSizeTotal);

            // EXTRACCIÓN DINÁMICA DE UNIDAD desde la primera presentación válida
            let unidad = this.productData.base_unit || '---';
            const firstRow = document.querySelector('.fila-presentacion');
            if (firstRow) {
                const sel = firstRow.querySelector('.peso-presentacion');
                if (sel && sel.value) {
                    const txt = sel.options[sel.selectedIndex]?.text || '';
                    // Extraer sufijo: KG, G, L, mL, Und, etc. (todo lo que sigue al número)
                    const match = txt.match(/\d+(?:\.\d+)?\s*([A-Za-z]+)/);
                    if (match) unidad = match[1].toUpperCase();
                }
            }
            this.unidadLote = unidad;

            console.log("--- CÁLCULO FINALIZADO: " + this.bulkSizeTotal + " KG | Unidades: " + totalUnidades + " ---");
            
            this.explodeFormula(totalUnidades);
        },

        explodeFormula(totalUnidades = 0) {
            const batchSizeActual = parseFloat(this.bulkSizeTotal) || 0;
            const batchSizeBase   = parseFloat(this.productData.base_batch_size) || 1;

            // Blindaje AuroFormat
            const fmt = (n) => (window.AuroFormat && typeof window.AuroFormat.decimal === 'function')
                ? window.AuroFormat.decimal(n)
                : parseFloat(n).toFixed(2);

            // Johann v3.5: Preservar lotes actuales antes de mapear
            const prevMaterials = [...this.explodedMaterials];

            this.explodedMaterials = (this.productData.ingredients || []).map(ing => {
                let qtyRequerida = 0;
                let isAPI    = (ing.function || '').toUpperCase() === 'API';
                let typeKey  = this.normalizeType(ing.material_type);

                if (batchSizeActual > 0) {
                    if (typeKey === 'MATERIA PRIMA') {
                        const qtyBase = parseFloat(ing.quantity);
                        const pctBase = parseFloat(ing.percentage);
                        if (!isNaN(qtyBase) && qtyBase > 0) {
                            qtyRequerida = (batchSizeActual * qtyBase) / batchSizeBase;
                        } else if (!isNaN(pctBase)) {
                            qtyRequerida = (batchSizeActual * pctBase) / 100;
                        }
                    } else {
                        qtyRequerida = totalUnidades;
                    }
                }

                let displayQty = qtyRequerida > 0 ? fmt(qtyRequerida) : '0.00';

                if (isAPI) {
                    const recon = (this.opData?.op_material_reconciliations || [])
                        .find(r => String(r.material_code) === String(ing.material_code));
                    if (recon && parseFloat(recon.required_qty) > 0) {
                        displayQty = fmt(recon.required_qty);
                    } else {
                        displayQty = 'PENDIENTE DE AJUSTE';
                    }
                }

                // ACTUALIZACIÓN DEL DOM
                this.$nextTick(() => {
                    const matCell = document.getElementById('req-qty-' + ing.material_code);
                    if (matCell) matCell.textContent = displayQty;
                });

                // BLINDAJE DE LOTES (Johann v3.5)
                let currentLots = [{ numero: '' }];
                
                // 1. Intentar recuperar de lo que el usuario acaba de escribir (prevMaterials)
                const existingInMem = prevMaterials.find(m => String(m.material_code) === String(ing.material_code));
                if (existingInMem && existingInMem.lots) {
                    currentLots = existingInMem.lots;
                } else {
                    // 2. Si no está en memoria, buscar en la data original de la OP (si es edición)
                    const recon = (this.opData?.op_material_reconciliations || [])
                        .find(r => String(r.material_code) === String(ing.material_code));
                    if (recon && recon.lote) {
                        currentLots = recon.lote.split(', ').map(n => ({ numero: n }));
                    }
                }

                return {
                    type: typeKey,
                    material_code: ing.material_code,
                    description: ing.material_name,
                    specific_material_type: ing.specific_material_type,
                    unit: ing.unit,
                    function: ing.function,
                    required_qty: displayQty,
                    lots: currentLots 
                };
            });
        },

        normalizeType(type) {
            if (!type) return 'MATERIA PRIMA';
            const t = type.toUpperCase();
            if (t.includes('MATERIA') || t.includes('PRIMA')) return 'MATERIA PRIMA';
            if (t.includes('ENVASE')) return 'ENVASE';
            if (t.includes('EMPAQUE')) return 'EMPAQUE';
            return 'MATERIA PRIMA';
        },
        
        async submitFake() {
            // Diagnóstico Preciso (Johann v11.0)
            if (!this.producto_id) return Swal.fire('Atención', 'Debe seleccionar un producto.', 'warning');
            if (!this.lote) return Swal.fire('Atención', 'Falta ingresar el número de Lote.', 'warning');
            if (!this.op) return Swal.fire('Atención', 'Falta ingresar el número de Orden de Producción (O.P. No).', 'warning');
            if (this.bulkSizeTotal <= 0) return Swal.fire('Atención', 'Debe agregar al menos una presentación con cantidad para calcular el tamaño del lote.', 'warning');
            if (!this.realizadoPor.signed) return Swal.fire('Atención', 'Debe registrar la firma de REALIZADO POR antes de generar la orden.', 'warning');

            Swal.fire({
                title: '¿Generar Orden de Producción?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0A2540',
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Verificando seguridad y guardando firmas...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    // PROTOCOLO DE RESCATE (Johann v2.6): Refresco Automático de Token
                    let csrfToken = '';
                    try {
                        const refreshRes = await fetch('/refresh-csrf');
                        const refreshData = await refreshRes.json();
                        csrfToken = refreshData.token;
                        document.querySelector('meta[name="csrf-token"]').content = csrfToken;
                        console.log('--- TOKEN REFRESCHADO EXITOSAMENTE ---');
                    } catch (e) {
                        console.warn('--- Falló refresco silencioso, usando token del DOM ---');
                        csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                                    document.querySelector('input[name="_token"]')?.value;
                    }

                    let form = document.getElementById('op-form');
                    let formData = new FormData(form);
                    
                    // INYECCIÓN EXPLÍCITA DEL TOKEN (Doble Candado)
                    formData.set('_token', csrfToken);
                    formData.set('op_number', this.op); 
                    formData.set('lote', this.lote);
                    formData.set('product_id', this.producto_id);

                    // Inyección de Presentaciones (Formato Array para Laravel)
                    const presentations = this.getPresentationsData();
                    presentations.forEach((p, i) => {
                        formData.set(`presentations[${i}][id]`, p.id);
                        formData.set(`presentations[${i}][quantity]`, p.quantity);
                        if (p.code) formData.set(`presentations[${i}][code]`, p.code);
                    });

                    // Inyección forzada de datos reactivos al FormData
                    formData.set('explosion_data', JSON.stringify(this.explodedMaterials));
                    formData.set('bulk_size_kg', this.bulkSizeTotal);
                    formData.set('unidad_lote', this.unidadLote);
                    formData.set('realizado_por', this.realizadoPor.name);
                    formData.set('realizado_id', this.realizadoPor.id);
                    formData.set('realizado_fecha', this.realizadoPor.date + (this.realizadoPor.hour ? ' ' + this.realizadoPor.hour : ''));
                    
                    formData.set('verificado_por', this.verificadoPor.name);
                    formData.set('verificado_id', this.verificadoPor.id);
                    formData.set('verificado_fecha', this.verificadoPor.date + (this.verificadoPor.hour ? ' ' + this.verificadoPor.hour : ''));

                    fetch(form.action, {
                        method: 'POST',
                        credentials: 'include', 
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(async response => {
                        const data = await response.json();
                        if (response.ok && data.success) {
                            Swal.fire({
                                title: '\u2705 Orden Generada',
                                text: data.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.replace(data.redirect || '/ops');
                            });
                            return;
                        }
                        if (response.status === 419) {
                            throw new Error('La sesión ha expirado (419). Por favor, recargue la página (F5).');
                        }
                        throw new Error(data.message || 'Error en el servidor (' + response.status + ')');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error de Seguridad/Comunicación',
                            text: error.message,
                            icon: 'error',
                            confirmButtonColor: '#0A2540'
                        });
                    });
                }
            });
        },

        getPresentationsData() {
            const data = [];
            const rows = document.querySelectorAll('#tabla-presentaciones-body tr');
            rows.forEach(row => {
                const select = row.querySelector('select');
                const input = row.querySelector('input[type="number"]');
                if (select && input && select.value) {
                    const opt = select.options[select.selectedIndex];
                    data.push({
                        id:       select.value,
                        quantity: input.value,
                        code:     opt ? (opt.getAttribute('data-code') || '') : ''
                    });
                }
            });
            return data;
        }
    }));
    }); // fin alpine:init

    // DELEGACIÓN DE EVENTOS GLOBAL AGRESIVA (Regla de Oro: Johann)
    document.addEventListener('keyup', (e) => {
        if (e.target.name && e.target.name.startsWith('presentaciones')) {
            if (typeof window.calcularFuerzaBruta === 'function') window.calcularFuerzaBruta();
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target.name && e.target.name.startsWith('presentaciones')) {
            if (typeof window.calcularFuerzaBruta === 'function') window.calcularFuerzaBruta();
        }
    });
</script>
</div>
@endsection
