@extends('layouts.app')

@section('header_title', 'Wizard de Creación - Orden de Maquila (ODM / SDM)')

@section('content')
<div class="max-w-6xl mx-auto pb-12 animate-fade-in" x-data="maquilaWizard()">
    
    <!-- Title Banner -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0A2540] uppercase tracking-tight">Nueva Orden de Maquila Externa</h1>
            <p class="text-xs text-slate-500 font-medium">Formulario Inteligente de Emisión ODM / SDM bajo norma Res. ICA 062542</p>
        </div>
        <a href="{{ route('maquila.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition">
            &larr; Volver al Dashboard
        </a>
    </div>

    <!-- Stepper Header -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 mb-6 flex items-center justify-around text-xs font-black">
        <div class="flex items-center space-x-2" :class="step >= 1 ? 'text-[#04BFAD]' : 'text-slate-300'">
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-white font-bold" :class="step >= 1 ? 'bg-[#04BFAD]' : 'bg-slate-200 text-slate-500'">1</span>
            <span>Paso 1: Datos Generales</span>
        </div>
        <div class="h-0.5 w-16 bg-slate-200"></div>
        <div class="flex items-center space-x-2" :class="step >= 2 ? 'text-[#04BFAD]' : 'text-slate-300'">
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-white font-bold" :class="step >= 2 ? 'bg-[#04BFAD]' : 'bg-slate-200 text-slate-500'">2</span>
            <span>Paso 2: Detalle de Ítems</span>
        </div>
        <div class="h-0.5 w-16 bg-slate-200"></div>
        <div class="flex items-center space-x-2" :class="step >= 3 ? 'text-[#04BFAD]' : 'text-slate-300'">
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-white font-bold" :class="step >= 3 ? 'bg-[#04BFAD]' : 'bg-slate-200 text-slate-500'">3</span>
            <span>Paso 3: Confirmación</span>
        </div>
    </div>

    <!-- Form Main Container -->
    <form action="{{ route('maquila.store') }}" method="POST" id="odm-form">
        @csrf

        <!-- PASO 1: Datos Generales -->
        <div x-show="step === 1" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-6">
            <h3 class="text-sm font-black text-[#0A2540] uppercase tracking-wider border-b border-slate-100 pb-3">Información de la Orden (ODM / SDM)</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- N° ODM -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Número ODM (Autogenerado) <span class="text-red-500">*</span></label>
                    <input type="text" name="numero_odm" value="{{ old('numero_odm', $nextOdm) }}" required readonly class="w-full bg-slate-100 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:outline-none">
                </div>

                <!-- N° SDM -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Número SDM (Solicitud) <span class="text-red-500">*</span></label>
                    <input type="text" name="numero_sdm" value="{{ old('numero_sdm', $nextSdm) }}" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-[#04BFAD]">
                </div>

                <!-- Tipo de Producto -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Tipo de Producto <span class="text-red-500">*</span></label>
                    <select name="tipo_producto" x-model="tipoProducto" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-[#04BFAD]">
                        <option value="producto_terminado">PRODUCTO TERMINADO (Comercial)</option>
                        <option value="premezcla">PREMEZCLA (Insumo Medicado Intermedio)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Maquilador -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Maquilador Autorizado <span class="text-red-500">*</span></label>
                    <select name="maquilador_id" x-model="selectedMaquiladorId" @change="checkMaquiladorIca()" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-[#04BFAD]">
                        <option value="">-- Seleccione Maquilador --</option>
                        @foreach($maquiladores as $m)
                        <option value="{{ $m->id }}" 
                                data-nit="{{ $m->nit }}"
                                data-estado="{{ $m->estado_certificado_ica }}"
                                data-vencimiento="{{ $m->certificado_bpm_ica_vigente_hasta ? $m->certificado_bpm_ica_vigente_hasta->format('Y-m-d') : 'N/A' }}">
                            {{ $m->nombre }} (NIT: {{ $m->nit }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Fecha de Envió -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Fecha Estimada de Despacho</label>
                    <input type="date" name="fecha_envio_maquila" value="{{ old('fecha_envio_maquila', date('Y-m-d')) }}" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800">
                </div>
            </div>

            <!-- Banner Alerta BPM-ICA -->
            <template x-if="maquiladorIcaStatus === 'vencido'">
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl text-xs text-red-800 font-bold flex items-center space-x-3">
                    <i class="fa-solid fa-triangle-exclamation text-red-600 text-xl"></i>
                    <div>
                        <p class="uppercase font-black">¡ADVERTENCIA CRÍTICA DE REGULACIÓN ICA!</p>
                        <p>El certificado BPM-ICA de este maquilador se encuentra <strong>VENCIDO</strong> (<span x-text="maquiladorIcaVencimiento"></span>). Emitir esta ODM generará un registro de observación auditorable en el Audit Log.</p>
                    </div>
                </div>
            </template>
            <template x-if="maquiladorIcaStatus === 'proximo_a_vencer'">
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-xl text-xs text-amber-800 font-bold flex items-center space-x-3">
                    <i class="fa-solid fa-circle-exclamation text-amber-600 text-xl"></i>
                    <div>
                        <p class="uppercase font-black">ADVERTENCIA NORMATIVA ICA</p>
                        <p>El certificado BPM-ICA de este maquilador vence en menos de 60 días (<span x-text="maquiladorIcaVencimiento"></span>).</p>
                    </div>
                </div>
            </template>

            <!-- Observaciones -->
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Observaciones Generales</label>
                <textarea name="observaciones" rows="2" class="w-full border border-slate-300 rounded-xl p-3 text-xs font-medium" placeholder="Ingrese notas o especificaciones de despacho..."></textarea>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="goToStep(2)" class="bg-[#04BFAD] hover:bg-[#048ABF] text-slate-950 font-black px-6 py-3 rounded-xl shadow-md transition text-xs uppercase">
                    Siguiente: Detalle de Ítems &rarr;
                </button>
            </div>
        </div>

        <!-- PASO 2: Filas Dinámicas de Ítems -->
        <div x-show="step === 2" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-6">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-sm font-black text-[#0A2540] uppercase tracking-wider">Detalle de Ítems a Programar</h3>
                <button type="button" @click="addItem()" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-black px-3 py-1.5 rounded-lg transition">
                    + Agregar Ítem
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 font-black uppercase border-b border-slate-200">
                        <tr>
                            <th class="p-3">Código Ítem</th>
                            <th class="p-3">Descripción Producto</th>
                            <th class="p-3">Lote Físico</th>
                            <th class="p-3">Presentación</th>
                            <th class="p-3 text-right">Cant. Programada</th>
                            <th class="p-3">Unidad</th>
                            <th class="p-3 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b border-slate-100">
                                <td class="p-2 w-36">
                                    <input type="text" :name="`items[${index}][codigo_item]`" x-model="item.codigo_item" @blur="lookupItem(index)" placeholder="Ej. 102030" required class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 font-bold uppercase text-xs">
                                </td>
                                <td class="p-2">
                                    <input type="text" :name="`items[${index}][descripcion_producto]`" x-model="item.descripcion_producto" placeholder="Descripción autocompletada..." required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 font-bold text-xs">
                                </td>
                                <td class="p-2 w-32">
                                    <input type="text" :name="`items[${index}][lote_fisico]`" x-model="item.lote_fisico" placeholder="Lote 2026-X" required class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 font-bold text-xs">
                                </td>
                                <td class="p-2 w-36">
                                    <input type="text" :name="`items[${index}][presentacion]`" x-model="item.presentacion" placeholder="Bolsa x 25kg" required class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 font-medium text-xs">
                                </td>
                                <td class="p-2 w-32">
                                    <input type="number" step="0.001" :name="`items[${index}][cantidad_programada]`" x-model.number="item.cantidad_programada" placeholder="0.00" required class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 font-black text-right text-xs">
                                </td>
                                <td class="p-2 w-24">
                                    <select :name="`items[${index}][unidad_medida]`" x-model="item.unidad_medida" class="w-full border border-slate-300 rounded-lg px-2 py-1.5 font-bold text-xs">
                                        <option value="KG">KG</option>
                                        <option value="UND">UND</option>
                                    </select>
                                </td>
                                <td class="p-2 text-center">
                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-red-500 hover:text-red-700 font-bold p-1">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-between pt-4">
                <button type="button" @click="goToStep(1)" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-black px-6 py-3 rounded-xl text-xs uppercase">
                    &larr; Anterior
                </button>
                <button type="button" @click="goToStep(3)" class="bg-[#04BFAD] hover:bg-[#048ABF] text-slate-950 font-black px-6 py-3 rounded-xl shadow-md transition text-xs uppercase">
                    Siguiente: Resumen &rarr;
                </button>
            </div>
        </div>

        <!-- PASO 3: Resumen y Confirmación -->
        <div x-show="step === 3" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-6">
            <h3 class="text-sm font-black text-[#0A2540] uppercase tracking-wider border-b border-slate-100 pb-3">Resumen de la Orden de Maquila</h3>

            <div class="bg-slate-50 p-4 rounded-xl grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 font-bold uppercase block">N° ODM</span>
                    <span class="font-black text-[#0A2540] text-sm" x-text="document.querySelector('[name=numero_odm]')?.value"></span>
                </div>
                <div>
                    <span class="text-slate-400 font-bold uppercase block">N° SDM</span>
                    <span class="font-black text-slate-700 text-sm" x-text="document.querySelector('[name=numero_sdm]')?.value"></span>
                </div>
                <div>
                    <span class="text-slate-400 font-bold uppercase block">Tipo</span>
                    <span class="font-black uppercase text-purple-700 text-sm" x-text="tipoProducto"></span>
                </div>
                <div>
                    <span class="text-slate-400 font-bold uppercase block">Ítems a Despachar</span>
                    <span class="font-black text-slate-800 text-sm" x-text="items.length"></span>
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <button type="button" @click="goToStep(2)" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-black px-6 py-3 rounded-xl text-xs uppercase">
                    &larr; Modificar Ítems
                </button>
                <button type="submit" class="bg-[#0A2540] hover:bg-slate-800 text-white font-black px-8 py-3 rounded-xl shadow-xl transition text-xs uppercase tracking-wider flex items-center space-x-2">
                    <i class="fa-solid fa-paper-plane text-[#04BFAD]"></i>
                    <span>Emitir y Enviar a Maquila</span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function maquilaWizard() {
    return {
        step: 1,
        tipoProducto: 'producto_terminado',
        selectedMaquiladorId: '',
        maquiladorIcaStatus: '',
        maquiladorIcaVencimiento: '',
        items: [
            { codigo_item: '', descripcion_producto: '', lote_fisico: '', presentacion: '', cantidad_programada: 0, unidad_medida: 'KG' }
        ],
        checkMaquiladorIca() {
            let select = document.querySelector('[name=maquilador_id]');
            let option = select.options[select.selectedIndex];
            if (option && option.value) {
                this.maquiladorIcaStatus = option.getAttribute('data-estado');
                this.maquiladorIcaVencimiento = option.getAttribute('data-vencimiento');
            } else {
                this.maquiladorIcaStatus = '';
                this.maquiladorIcaVencimiento = '';
            }
        },
        addItem() {
            this.items.push({ codigo_item: '', descripcion_producto: '', lote_fisico: '', presentacion: '', cantidad_programada: 0, unidad_medida: 'KG' });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        async lookupItem(index) {
            let code = this.items[index].codigo_item;
            if (!code) return;

            try {
                let res = await fetch(`/api/maquilas/item-lookup/${encodeURIComponent(code)}`);
                let data = await res.json();
                if (data.found) {
                    this.items[index].descripcion_producto = data.descripcion;
                    this.items[index].presentacion = data.presentacion;
                    this.items[index].unidad_medida = data.unidad;
                } else {
                    alert('Atención: El código de ítem digitado no se encuentra en el catálogo maestro.');
                }
            } catch (e) {
                console.error(e);
            }
        },
        goToStep(s) {
            if (s === 2 && !this.selectedMaquiladorId) {
                alert('Por favor seleccione un maquilador antes de continuar.');
                return;
            }
            this.step = s;
        }
    }
}
</script>
@endsection
