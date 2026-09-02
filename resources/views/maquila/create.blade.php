@extends('layouts.app')

@section('header_title', 'Crear Orden de Maquila (ODM)')

@section('content')
<div class="max-w-6xl mx-auto pb-12 animate-fade-in" x-data="maquilaWizard()">
    
    <!-- Title Banner -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0A2540] uppercase tracking-tight">Nueva Orden de Maquila Externa</h1>
            <p class="text-xs text-slate-500 font-medium">Formulario de Registro de Orden de Maquila (ODM)</p>
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
            <h3 class="text-sm font-black text-[#0A2540] uppercase tracking-wider border-b border-slate-100 pb-3">Información de la Orden</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- N° ODM (Editable) -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Número ODM <span class="text-red-500">*</span></label>
                    <input type="text" name="numero_odm" x-model="odmNumber" placeholder="Ej. ODM-2026-0001" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-[#04BFAD]">
                </div>

                <!-- OP -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">N° OP (Orden de Producción)</label>
                    <input type="text" name="op" x-model="opNumber" placeholder="Ej. OP-99482" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-[#04BFAD]">
                </div>

                <!-- LOTE -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Lote Físico</label>
                    <input type="text" name="lote" x-model="loteNumber" placeholder="Ej. LOTE-2026-X" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-[#04BFAD]">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tipo de Producto -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Tipo de Producto <span class="text-red-500">*</span></label>
                    <select name="tipo_producto" x-model="tipoProducto" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-[#04BFAD]">
                        <option value="producto_terminado">PRODUCTO TERMINADO (Comercial)</option>
                        <option value="premezcla">PREMEZCLA (Insumo Medicado Intermedio)</option>
                    </select>
                </div>

                <!-- Maquilador (Solo nombre) -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Maquilador Autorizado <span class="text-red-500">*</span></label>
                    <select name="maquilador_id" x-model="selectedMaquiladorId" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-[#04BFAD]">
                        <option value="">-- Seleccione Maquilador --</option>
                        @foreach($maquiladores as $m)
                        <option value="{{ $m->id }}">{{ $m->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Observaciones -->
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Observaciones Generales</label>
                <textarea name="observaciones" rows="2" class="w-full border border-slate-300 rounded-xl p-3 text-xs font-medium" placeholder="Ingrese notas o especificaciones adicionales..."></textarea>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="goToStep(2)" class="bg-[#04BFAD] hover:bg-[#048ABF] text-slate-950 font-black px-6 py-3 rounded-xl shadow-md transition text-xs uppercase">
                    Siguiente: Detalle de Ítems &rarr;
                </button>
            </div>
        </div>

        <!-- PASO 2: Filas Dinámicas de Ítems con SDM por ítem -->
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
                            <th class="p-3 w-36">SDM</th>
                            <th class="p-3 w-36">Código Ítem</th>
                            <th class="p-3">Descripción Producto</th>
                            <th class="p-3 text-right w-36">Cant. Programada</th>
                            <th class="p-3 w-28">Unidad</th>
                            <th class="p-3 text-center w-16">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b border-slate-100">
                                <td class="p-2">
                                    <input type="text" :name="`items[${index}][sdm]`" x-model="item.sdm" placeholder="Ej. SDM-001" class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 font-bold uppercase text-xs">
                                </td>
                                <td class="p-2">
                                    <input type="text" :name="`items[${index}][codigo_item]`" x-model="item.codigo_item" @blur="lookupItem(index)" placeholder="Ej. 102030" required class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 font-bold uppercase text-xs">
                                </td>
                                <td class="p-2">
                                    <input type="text" :name="`items[${index}][descripcion_producto]`" x-model="item.descripcion_producto" placeholder="Descripción del producto..." required class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 font-bold text-xs">
                                </td>
                                <td class="p-2">
                                    <input type="number" step="0.001" :name="`items[${index}][cantidad_programada]`" x-model.number="item.cantidad_programada" placeholder="0.00" required class="w-full border border-slate-300 rounded-lg px-2.5 py-1.5 font-black text-right text-xs">
                                </td>
                                <td class="p-2">
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
                    <span class="font-black text-[#0A2540] text-sm" x-text="odmNumber"></span>
                </div>
                <div>
                    <span class="text-slate-400 font-bold uppercase block">N° OP</span>
                    <span class="font-black text-slate-700 text-sm" x-text="opNumber || 'N/A'"></span>
                </div>
                <div>
                    <span class="text-slate-400 font-bold uppercase block">Lote Físico</span>
                    <span class="font-black text-slate-700 text-sm" x-text="loteNumber || 'N/A'"></span>
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
        odmNumber: 'ODM-',
        opNumber: '',
        loteNumber: '',
        tipoProducto: 'producto_terminado',
        selectedMaquiladorId: '',
        items: [
            { sdm: '', codigo_item: '', descripcion_producto: '', cantidad_programada: 0, unidad_medida: 'KG' }
        ],
        addItem() {
            this.items.push({ sdm: '', codigo_item: '', descripcion_producto: '', cantidad_programada: 0, unidad_medida: 'KG' });
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
                    this.items[index].unidad_medida = data.unidad || 'KG';
                }
            } catch (e) {
                console.error(e);
            }
        },
        goToStep(s) {
            if (s === 2) {
                if (!this.odmNumber || this.odmNumber.trim() === 'ODM-') {
                    alert('Por favor digite un Número ODM válido.');
                    return;
                }
                if (!this.selectedMaquiladorId) {
                    alert('Por favor seleccione un maquilador antes de continuar.');
                    return;
                }
            }
            this.step = s;
        }
    }
}
</script>
@endsection
