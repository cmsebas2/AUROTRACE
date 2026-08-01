@extends('layouts.app')

@section('header_title', 'Crear Orden de Maquila')

@section('content')
<div class="max-w-6xl mx-auto animate-fade-in" x-data="maquilaForm()">
    
    <!-- Cabecera -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter flex items-center gap-3">
                <span class="w-4 h-8 bg-slate-900 rounded-full"></span>
                Nueva Orden de Maquila (ODM)
            </h2>
            <p class="text-sm text-gray-500 mt-1">Estructura y registre la orden de maquilado externo con trazabilidad por lote y alertas de vencimiento.</p>
        </div>
        <a href="{{ route('maquila.index') }}" 
           class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition-colors text-xs uppercase tracking-wider">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al listado
        </a>
    </div>

    <!-- Formulario Principal -->
    <form action="{{ route('maquila.store') }}" method="POST" class="space-y-8">
        @csrf
        
        <!-- Bloque 1: Tipo de Producto -->
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 space-y-6">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Paso 1: Tipo de Producto</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Premezcla -->
                <label class="relative flex flex-col p-5 border-2 rounded-2xl cursor-pointer hover:bg-slate-50 transition-all"
                       :class="tipoProducto === 'PREMEZCLA' ? 'border-emerald-500 bg-emerald-50/20' : 'border-gray-200'">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-lg" :class="tipoProducto === 'PREMEZCLA' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 9.172V5L8 4z"></path></svg>
                            </span>
                            <span class="font-bold text-slate-700">Premezcla (Medicamento / Nutricional)</span>
                        </div>
                        <input type="radio" name="tipo_producto" value="PREMEZCLA" x-model="tipoProducto" class="sr-only">
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center" :class="tipoProducto === 'PREMEZCLA' ? 'border-emerald-500' : 'border-gray-300'">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500" x-show="tipoProducto === 'PREMEZCLA'"></div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 pl-11">Productos en polvo o líquidos destinados a la mezcla con alimentos o pre-dosificados en planta.</p>
                </label>

                <!-- Producto Terminado -->
                <label class="relative flex flex-col p-5 border-2 rounded-2xl cursor-pointer hover:bg-slate-50 transition-all"
                       :class="tipoProducto === 'PRODUCTO_TERMINADO' ? 'border-blue-500 bg-blue-50/20' : 'border-gray-200'">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-lg" :class="tipoProducto === 'PRODUCTO_TERMINADO' ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-500'">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </span>
                            <span class="font-bold text-slate-700">Producto Terminado (Comercializable)</span>
                        </div>
                        <input type="radio" name="tipo_producto" value="PRODUCTO_TERMINADO" x-model="tipoProducto" class="sr-only">
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center" :class="tipoProducto === 'PRODUCTO_TERMINADO' ? 'border-blue-500' : 'border-gray-300'">
                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500" x-show="tipoProducto === 'PRODUCTO_TERMINADO'"></div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 pl-11">Productos finales envasados y etiquetados listos para distribución e inventario final.</p>
                </label>
            </div>
        </div>

        <!-- Bloque 2: Datos Generales -->
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 space-y-6">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Paso 2: Datos Generales de la Orden</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Fecha Creación -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha Creación</label>
                    <input type="date" name="fecha_creacion" value="{{ date('Y-m-d') }}" required
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-slate-900 focus:border-transparent text-sm font-semibold text-slate-700">
                </div>

                <!-- ODM -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Orden de Maquila (ODM)</label>
                    <input type="text" name="odm" required placeholder="Eje: ODM-2026-001"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-slate-900 focus:border-transparent text-sm font-bold text-slate-800 placeholder-gray-300 uppercase">
                </div>

                <!-- SDM -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Solicitud de Maquila (SDM)</label>
                    <input type="text" name="sdm" placeholder="Eje: SDM-2026-042"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-slate-900 focus:border-transparent text-sm font-bold text-slate-800 placeholder-gray-300 uppercase">
                </div>

                <!-- Maquilador -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Maquilador Autorizado</label>
                    <select name="maquilador" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-slate-900 focus:border-transparent text-sm font-semibold text-slate-700">
                        <option value="">Seleccione Maquilador...</option>
                        @foreach($maquiladores as $maquilador)
                            <option value="{{ $maquilador }}">{{ $maquilador }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Bloque 3: Detalle de Productos (Dinámico) -->
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Paso 3: Detalle de Productos y Trazabilidad</h3>
                <button type="button" @click="addItem()" 
                        class="inline-flex items-center gap-1 px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl shadow hover:bg-slate-800 transition-colors uppercase tracking-wider">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Añadir Fila
                </button>
            </div>

            <!-- Listado de filas dinámicas -->
            <div class="space-y-6">
                <template x-for="(item, index) in items" :key="index">
                    <div class="p-6 rounded-2xl border transition-all"
                         :class="checkExpiry(index) ? 'border-red-300 bg-red-50/20' : 'border-gray-200 bg-slate-50/30'">
                        
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest" x-text="'Producto #' + (index + 1)"></span>
                            <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                    class="text-red-500 hover:text-red-700 text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Remover
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-5">
                            <!-- Producto Select -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Producto Catalogo</label>
                                <select :name="'items['+index+'][product_id]'" required @change="updateProductDetails(index, $event.target.value)"
                                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                                    <option value="">Seleccione Producto...</option>
                                    <template x-for="p in availableProducts" :key="p.id">
                                        <option :value="p.id" x-text="p.name + ' (' + (p.presentation || 'Sin pres.') + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Lote -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Lote Físico</label>
                                <input type="text" :name="'items['+index+'][lote]'" required placeholder="Eje: 604MT02"
                                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-slate-800 placeholder-gray-300 uppercase focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                            </div>

                            <!-- Cantidad -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Cant. Recibida</label>
                                <div class="relative">
                                    <input type="number" :name="'items['+index+'][cantidad]'" required step="0.01" min="0.01" placeholder="0.00"
                                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                                    <span class="absolute right-3 top-2.5 text-[10px] font-bold text-gray-400" x-text="item.uom"></span>
                                </div>
                            </div>

                            <!-- Cantidad Programada -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Cant. Programada</label>
                                <div class="relative">
                                    <input type="number" :name="'items['+index+'][cantidad_programada]'" required step="0.01" min="0.01" placeholder="0.00"
                                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                                    <span class="absolute right-3 top-2.5 text-[10px] font-bold text-gray-400" x-text="item.uom"></span>
                                </div>
                            </div>

                            <!-- Fecha Fabricación -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Fecha Fabricación</label>
                                <input type="date" :name="'items['+index+'][fecha_fabricacion]'" required x-model="item.fecha_fabricacion"
                                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-4">
                            <!-- Fecha Vencimiento -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Fecha Vencimiento</label>
                                <input type="date" :name="'items['+index+'][fecha_vencimiento]'" required x-model="item.fecha_vencimiento"
                                       class="w-full px-4 py-2.5 rounded-xl border text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:border-transparent"
                                       :class="checkExpiry(index) ? 'border-red-400 text-red-700 bg-red-50' : 'border-gray-200 text-slate-700 bg-white'">
                            </div>

                            <!-- Alerta de Vencimiento Próximo -->
                            <div class="md:col-span-2 flex items-end">
                                <template x-if="checkExpiry(index)">
                                    <div class="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs font-bold animate-pulse mb-0.5">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <span>¡Alerta! La fecha de vencimiento es menor a 3 meses respecto a la fabricación o la fecha actual.</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </template>
            </div>
        </div>

        <!-- Botones de Enviar -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('maquila.index') }}" 
               class="px-8 py-3.5 border border-gray-300 text-gray-700 font-bold rounded-2xl hover:bg-gray-50 transition-colors text-sm uppercase tracking-wider">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-8 py-3.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-2xl shadow-xl hover:-translate-y-0.5 transition-all text-sm uppercase tracking-wider">
                Guardar Orden de Maquila
            </button>
        </div>
    </form>
</div>

<script>
    function maquilaForm() {
        return {
            tipoProducto: 'PREMEZCLA',
            availableProducts: @json($products),
            items: [
                { product_id: '', uom: 'KG', fecha_fabricacion: '', fecha_vencimiento: '' }
            ],
            addItem() {
                this.items.push({ product_id: '', uom: 'KG', fecha_fabricacion: '', fecha_vencimiento: '' });
            },
            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                }
            },
            updateProductDetails(index, productId) {
                let product = this.availableProducts.find(p => p.id == productId);
                if (product) {
                    this.items[index].uom = product.base_unit || 'KG';
                    
                    // Si el producto tiene meses de vigencia configurados, calcular automáticamente la fecha de vencimiento
                    if (product.vigencia_meses && this.items[index].fecha_fabricacion) {
                        let fabDate = new Date(this.items[index].fecha_fabricacion);
                        fabDate.setMonth(fabDate.getMonth() + parseInt(product.vigencia_meses));
                        
                        // Formatear fecha a YYYY-MM-DD
                        let year = fabDate.getFullYear();
                        let month = String(fabDate.getMonth() + 1).padStart(2, '0');
                        let day = String(fabDate.getDate()).padStart(2, '0');
                        this.items[index].fecha_vencimiento = `${year}-${month}-${day}`;
                    }
                }
            },
            checkExpiry(index) {
                let item = this.items[index];
                if (!item.fecha_vencimiento) return false;
                
                let fab = item.fecha_fabricacion ? new Date(item.fecha_fabricacion) : new Date();
                let venc = new Date(item.fecha_vencimiento);
                
                // Diferencia en días
                let diffTime = venc.getTime() - fab.getTime();
                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                // Si la diferencia es menor a 90 días (3 meses), alertar
                return diffDays < 90;
            }
        }
    }
</script>

<style>
    @keyframes fade-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in { animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
@endsection
