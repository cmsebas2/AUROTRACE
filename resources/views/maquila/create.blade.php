@extends('layouts.app')

@section('header_title', 'Crear Orden de Maquila')

@section('content')
<div class="max-w-7xl mx-auto animate-fade-in" x-data="maquilaForm()">

    <!-- Cabecera de Página -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-[#0A2540] tracking-tighter flex items-center gap-3">
                <span class="w-3.5 h-8 bg-[#0A2540] rounded-full"></span>
                <i class="fa-solid fa-industry text-aurofarma-teal"></i>
                Nueva Orden de Maquila (ODM)
            </h2>
            <p class="text-sm text-gray-500 mt-1 font-medium">Estructure y registre la orden de fabricación maquilada por presentación y trazabilidad.</p>
        </div>
        <a href="{{ route('maquila.index') }}" 
           class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 text-slate-700 font-bold rounded-xl hover:bg-gray-100 transition-colors text-xs uppercase tracking-wider">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al listado</span>
        </a>
    </div>

    <!-- Formulario Principal -->
    <form action="{{ route('maquila.store') }}" method="POST" class="space-y-8">
        @csrf
        
        <!-- Bloque 1: Tipo de Producto (Opciones limpias sin descripciones inferiores) -->
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 space-y-6">
            <h3 class="text-xs font-black text-[#0A2540] uppercase tracking-widest flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#04BFAD]"></span>
                Paso 1: Tipo de Producto
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Premezcla -->
                <label class="relative flex items-center justify-between p-6 border-2 rounded-2xl cursor-pointer hover:border-emerald-500 transition-all group"
                       :class="tipoProducto === 'PREMEZCLA' ? 'border-emerald-500 bg-emerald-50/30 shadow-md' : 'border-slate-200 bg-white'">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors"
                             :class="tipoProducto === 'PREMEZCLA' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200'">
                            <i class="fa-solid fa-flask text-xl"></i>
                        </div>
                        <span class="font-black text-slate-800 text-lg tracking-tight">Premezcla</span>
                    </div>
                    <input type="radio" name="tipo_producto" value="PREMEZCLA" x-model="tipoProducto" class="sr-only">
                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all"
                         :class="tipoProducto === 'PREMEZCLA' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'">
                        <i class="fa-solid fa-check text-white text-xs" x-show="tipoProducto === 'PREMEZCLA'"></i>
                    </div>
                </label>

                <!-- Maquila -->
                <label class="relative flex items-center justify-between p-6 border-2 rounded-2xl cursor-pointer hover:border-indigo-500 transition-all group"
                       :class="tipoProducto === 'MAQUILA' ? 'border-indigo-500 bg-indigo-50/30 shadow-md' : 'border-slate-200 bg-white'">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors"
                             :class="tipoProducto === 'MAQUILA' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200'">
                            <i class="fa-solid fa-vial text-xl"></i>
                        </div>
                        <span class="font-black text-slate-800 text-lg tracking-tight">Maquila</span>
                    </div>
                    <input type="radio" name="tipo_producto" value="MAQUILA" x-model="tipoProducto" class="sr-only">
                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all"
                         :class="tipoProducto === 'MAQUILA' ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300'">
                        <i class="fa-solid fa-check text-white text-xs" x-show="tipoProducto === 'MAQUILA'"></i>
                    </div>
                </label>
            </div>
        </div>

        <!-- Bloque 2: Datos Generales de la ODM -->
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 space-y-6">
            <h3 class="text-xs font-black text-[#0A2540] uppercase tracking-widest flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#04BFAD]"></span>
                Paso 2: Datos Generales de la ODM
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Casilla PRODUCTO (Antes de Fecha de Creación) -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Producto <span class="text-red-500">*</span></label>
                    <input type="text" name="producto" required placeholder="Ej: CABATEL NF"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-[#0A2540] focus:border-transparent text-sm font-black text-[#0A2540] uppercase tracking-wide">
                </div>

                <!-- Fecha de Creación -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Fecha de Creación <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_creacion" value="{{ date('Y-m-d') }}" required
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-[#0A2540] focus:border-transparent text-sm font-bold text-slate-800">
                </div>

                <!-- Orden de Maquila (ODM) -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Orden de Maquila (ODM) <span class="text-red-500">*</span></label>
                    <input type="text" name="odm" required placeholder="Ej: ODM-2026-001"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-[#0A2540] focus:border-transparent text-sm font-black text-[#0A2540] placeholder-gray-300 uppercase tracking-wide">
                </div>

                <!-- Maquilador Autorizado -->
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Maquilador Autorizado <span class="text-red-500">*</span></label>
                    <select name="maquilador" required
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-[#0A2540] focus:border-transparent text-sm font-bold text-slate-800">
                        <option value="">Seleccione Maquilador...</option>
                        @foreach($maquiladores as $maquiladorOption)
                            <option value="{{ $maquiladorOption }}">{{ $maquiladorOption }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Bloque 3: Detalle por Presentación y Trazabilidad (Filas Dinámicas) -->
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <h3 class="text-xs font-black text-[#0A2540] uppercase tracking-widest flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#04BFAD]"></span>
                    Paso 3: Detalle por Presentación y Trazabilidad
                </h3>
                <button type="button" @click="addItem()" 
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0A2540] text-white text-xs font-bold rounded-xl shadow-lg hover:bg-[#071b30] transition-all uppercase tracking-wider border border-slate-700/30">
                    <i class="fa-solid fa-plus text-[#04BFAD]"></i>
                    <span>Añadir Fila</span>
                </button>
            </div>

            <!-- Listado de filas dinámicas -->
            <div class="space-y-6">
                <template x-for="(item, index) in items" :key="index">
                    <div class="p-6 rounded-2xl border transition-all relative"
                         :class="checkExpiry(index) ? 'border-red-300 bg-red-50/30 shadow-md' : 'border-slate-200 bg-slate-50/40'">
                        
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-black text-[#0A2540] uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-solid fa-cube text-aurofarma-teal"></i>
                                <span x-text="'Presentación #' + (index + 1)"></span>
                            </span>
                            <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                    class="text-red-500 hover:text-red-700 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 px-3 py-1 bg-white rounded-lg border border-red-200 shadow-sm hover:bg-red-50 transition-colors">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Remover</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                            <!-- SDM (Solicitud de Maquila - Implementado ANTES de la Referencia) -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">SDM (Solicitud)</label>
                                <input type="text" :name="'items['+index+'][sdm]'" x-model="item.sdm" placeholder="Ej: SDM-2026-01"
                                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 uppercase focus:ring-2 focus:ring-[#0A2540] focus:border-transparent">
                            </div>

                            <!-- Referencia (Input manual de texto libre) -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">
                                    Referencia <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" :name="'items['+index+'][referencia]'" required x-model="item.referencia"
                                           @input.debounce.300ms="lookupReference(index)" @change="lookupReference(index)" @blur="lookupReference(index)"
                                           placeholder="Ej: 106, A11119..."
                                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-black text-[#0A2540] focus:ring-2 focus:ring-[#0A2540] focus:border-transparent uppercase">
                                    <div x-show="item.searching" class="absolute right-3 top-2.5">
                                        <i class="fa-solid fa-circle-notch fa-spin text-[#0A2540]"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Producto (Descripción Autocompletada) -->
                            <div class="md:col-span-4">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Producto</label>
                                <input type="text" readonly x-model="item.description" placeholder="Ingresa el código o referencia para buscar..."
                                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-100/80 text-xs font-bold text-slate-700">
                                <input type="hidden" :name="'items['+index+'][product_id]'" x-model="item.product_id">
                            </div>

                            <!-- Lote Físico -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Lote Físico <span class="text-red-500">*</span></label>
                                <input type="text" :name="'items['+index+'][lote_fisico]'" required placeholder="Ej: 604MT02"
                                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 placeholder-gray-300 uppercase focus:ring-2 focus:ring-[#0A2540] focus:border-transparent">
                            </div>

                            <!-- Cant. Programada y U.M. -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Cant. / U.M. <span class="text-red-500">*</span></label>
                                <div class="flex gap-1.5">
                                    <input type="number" :name="'items['+index+'][cantidad_programada]'" required step="0.01" min="0.01" placeholder="0.00"
                                           class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs font-black text-slate-800 focus:ring-2 focus:ring-[#0A2540] focus:border-transparent">
                                    <select :name="'items['+index+'][unidad_medida]'" x-model="item.unidad_medida" required
                                            class="w-20 px-2 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white focus:ring-2 focus:ring-[#0A2540] focus:border-transparent">
                                        <option value="KG">KG</option>
                                        <option value="UND">UND</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-4 pt-4 border-t border-slate-200/60">
                            <!-- Fecha Fabricación -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Fecha Fabricación <span class="text-red-500">*</span></label>
                                <input type="date" :name="'items['+index+'][fecha_fabricacion]'" required x-model="item.fecha_fabricacion" @change="onFabDateChange(index)"
                                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#0A2540] focus:border-transparent">
                            </div>

                            <!-- Fecha Vencimiento (Formato numérico MM-AAAA Ej: 03-2026) -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Fecha Vencimiento (MM-AAAA) <span class="text-red-500">*</span></label>
                                <div class="flex gap-2">
                                    <!-- Mes numérico 01 - 12 -->
                                    <select x-model="item.venc_mes" @change="updateVencDate(index)" required
                                            class="w-full px-3 py-2.5 rounded-xl border text-xs font-black text-slate-800 focus:ring-2 focus:ring-[#0A2540] focus:border-transparent"
                                            :class="checkExpiry(index) ? 'border-red-400 text-red-700 bg-red-50' : 'border-slate-300 bg-white'">
                                        <option value="">Mes</option>
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08">08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                    </select>
                                    <!-- Año numérico 2026 - 2040 -->
                                    <select x-model="item.venc_ano" @change="updateVencDate(index)" required
                                            class="w-full px-3 py-2.5 rounded-xl border text-xs font-black text-slate-800 focus:ring-2 focus:ring-[#0A2540] focus:border-transparent"
                                            :class="checkExpiry(index) ? 'border-red-400 text-red-700 bg-red-50' : 'border-slate-300 bg-white'">
                                        <option value="">Año</option>
                                        @for($y = (int)date('Y'); $y <= (int)date('Y') + 15; $y++)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <input type="hidden" :name="'items['+index+'][fecha_vencimiento]'" :value="item.fecha_vencimiento">
                            </div>

                            <!-- Alerta de Vencimiento Próximo (< 3 meses) -->
                            <div class="flex items-end">
                                <template x-if="checkExpiry(index)">
                                    <div class="flex items-center gap-2 p-3 bg-red-50 border border-red-300 rounded-xl text-red-700 text-xs font-black animate-pulse w-full">
                                        <i class="fa-solid fa-triangle-exclamation text-base text-red-600"></i>
                                        <span>¡Alerta! Expiración menor a 3 meses.</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </template>
            </div>
        </div>

        <!-- Botón de Acción Final -->
        <div class="flex items-center justify-end gap-4 pt-4">
            <a href="{{ route('maquila.index') }}" 
               class="px-8 py-3.5 border border-gray-300 text-slate-700 font-bold rounded-2xl hover:bg-gray-100 transition-colors text-sm uppercase tracking-wider">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-8 py-3.5 bg-[#0A2540] hover:bg-[#071b30] text-white font-bold rounded-2xl shadow-xl hover:-translate-y-0.5 transition-all text-sm uppercase tracking-wider flex items-center gap-2 border border-slate-700/30">
                <i class="fa-solid fa-floppy-disk text-aurofarma-teal"></i>
                <span>Guardar Orden de Maquila</span>
            </button>
        </div>
    </form>
</div>

<script>
    function maquilaForm() {
        return {
            tipoProducto: 'PREMEZCLA',
            items: [
                { sdm: '', referencia: '', description: '', product_id: '', unidad_medida: 'KG', fecha_fabricacion: '', venc_mes: '', venc_ano: '', fecha_vencimiento: '', vigencia_meses: null, searching: false }
            ],
            addItem() {
                this.items.push({ sdm: '', referencia: '', description: '', product_id: '', unidad_medida: 'KG', fecha_fabricacion: '', venc_mes: '', venc_ano: '', fecha_vencimiento: '', vigencia_meses: null, searching: false });
            },
            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                }
            },
            async lookupReference(index) {
                let itemObj = this.items[index];
                if (!itemObj) return;

                let ref = (itemObj.referencia || '').trim();
                if (!ref) {
                    itemObj.description = '';
                    itemObj.product_id = '';
                    itemObj.vigencia_meses = null;
                    return;
                }

                // Consulta EXCLUSIVA a la base de datos Supabase
                itemObj.searching = true;
                try {
                    let response = await fetch(`{{ route('maquila.api_lookup') }}?reference=${encodeURIComponent(ref)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        let text = await response.text();
                        console.error('API HTTP error:', response.status, text);
                        itemObj.description = `Error de servidor HTTP ${response.status}`;
                        return;
                    }

                    let data = await response.json();

                    if (data && data.found && data.description) {
                        itemObj.description = data.description;
                        itemObj.product_id = data.product_id || '';
                        itemObj.unidad_medida = data.unidad_medida || 'KG';
                        itemObj.vigencia_meses = data.vigencia_meses || null;

                        if (data.vigencia_meses && itemObj.fecha_fabricacion) {
                            this.calculateExpiry(index, data.vigencia_meses);
                        }
                    } else {
                        itemObj.description = data && data.description ? data.description : 'Referencia no encontrada en Supabase';
                        itemObj.product_id = '';
                        itemObj.vigencia_meses = null;
                    }
                } catch (e) {
                    console.error('Error al buscar referencia en Supabase DB:', e);
                    itemObj.description = 'Error de conexión o parsing JS';
                } finally {
                    itemObj.searching = false;
                }
            },
            onFabDateChange(index) {
                if (this.items[index].vigencia_meses) {
                    this.calculateExpiry(index, this.items[index].vigencia_meses);
                }
            },
            updateVencDate(index) {
                let item = this.items[index];
                if (item.venc_mes && item.venc_ano) {
                    item.fecha_vencimiento = `${item.venc_mes}-${item.venc_ano}`;
                } else {
                    item.fecha_vencimiento = '';
                }
            },
            calculateExpiry(index, vigenciaMeses) {
                if (!this.items[index].fecha_fabricacion) return;
                let fabDate = new Date(this.items[index].fecha_fabricacion);
                fabDate.setMonth(fabDate.getMonth() + parseInt(vigenciaMeses));
                
                let year = String(fabDate.getFullYear());
                let month = String(fabDate.getMonth() + 1).padStart(2, '0');
                
                this.items[index].venc_mes = month;
                this.items[index].venc_ano = year;
                this.items[index].fecha_vencimiento = `${month}-${year}`;
            },
            checkExpiry(index) {
                let item = this.items[index];
                if (!item.venc_mes || !item.venc_ano) return false;
                
                let fab = item.fecha_fabricacion ? new Date(item.fecha_fabricacion) : new Date();
                let venc = new Date(`${item.venc_ano}-${item.venc_mes}-01`);
                
                let diffTime = venc.getTime() - fab.getTime();
                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                return diffDays < 90;
            }
        }
    }
</script>

<style>
    @keyframes fade-in { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in { animation: fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
@endsection
