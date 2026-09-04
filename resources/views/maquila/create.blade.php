@extends('layouts.app')

@section('header_title', 'Emisión de Orden de Producción - Maquilas Externas')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="maquilaCreateWizard()">
    
    <!-- Header y Navegación -->
    <div class="flex items-center justify-between">
        <a href="{{ route('maquila.index') }}" class="text-xs font-black uppercase tracking-wider text-slate-500 hover:text-cyan-600 flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Dashboard
        </a>
        <span class="px-3 py-1 rounded-full bg-slate-900 text-white font-mono text-[10px] font-black uppercase tracking-widest shadow-sm">
            Fase 01 · Creación OP Maquila
        </span>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-sm">
            <div class="font-bold text-xs mb-1">Por favor revise los campos requeridos:</div>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario Maestro 3D -->
    <form action="{{ route('maquila.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Bloque 1: Datos Principales de la Orden -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-6">
            <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-aurofarma flex items-center justify-center text-white shadow-3d-cyan">
                    <i class="fas fa-file-invoice text-lg"></i>
                </div>
                <div>
                    <h2 class="font-display text-lg font-black text-slate-900 tracking-tight">Datos Generales de la Orden de Producción</h2>
                    <p class="text-xs text-slate-500">Defina la Pre-Orden, OP, lote técnico, tamaño y fechas de vigencia</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                
                <!-- 1. Fecha de Creación -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha de Creación <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha_creacion" required value="{{ old('fecha_creacion', date('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800">
                </div>

                <!-- 2. Pre Orden (Formato PL-XX-G) -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Pre Orden <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-stretch rounded-xl overflow-hidden border border-slate-300 focus-within:border-cyan-500 focus-within:ring-4 focus-within:ring-cyan-500/10 shadow-sm bg-white">
                        <span class="inline-flex items-center justify-center px-3.5 py-2.5 bg-slate-100 text-slate-700 font-mono font-black text-xs border-r border-slate-300 select-none flex-shrink-0">
                            PL-
                        </span>
                        <input type="text" name="pre_orden_numero" required value="{{ old('pre_orden_numero') }}" placeholder="01"
                               class="min-w-0 flex-1 px-3 py-2.5 text-xs font-black text-slate-900 text-center uppercase tracking-wider focus:outline-none border-0 ring-0">
                        <span class="inline-flex items-center justify-center px-3.5 py-2.5 bg-slate-100 text-slate-700 font-mono font-black text-xs border-l border-slate-300 select-none flex-shrink-0">
                            -G
                        </span>
                    </div>
                </div>

                <!-- 3. Número de OP -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Número de OP <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="op" required value="{{ old('op') }}" placeholder="Ej: OP-2026-001"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-black text-slate-900 uppercase">
                </div>

                <!-- 4. Número de ODM -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Número de ODM <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-stretch rounded-xl overflow-hidden border border-slate-300 focus-within:border-cyan-500 focus-within:ring-4 focus-within:ring-cyan-500/10 shadow-sm bg-white">
                        <span class="inline-flex items-center justify-center px-3.5 py-2.5 bg-slate-100 text-slate-700 font-mono font-black text-xs border-r border-slate-300 select-none flex-shrink-0">
                            ODM-
                        </span>
                        <input type="text" name="numero_odm_valor" required value="{{ old('numero_odm_valor', '') }}" placeholder="Ej: 2026-001"
                               class="min-w-0 flex-1 px-3.5 py-2.5 text-xs font-mono font-black text-cyan-800 uppercase focus:outline-none border-0 ring-0">
                    </div>
                </div>

                <!-- 5. Producto -->
                <div class="sm:col-span-2 md:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Producto a Fabricar <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" name="producto_nombre" id="producto_nombre" required value="{{ old('producto_nombre') }}"
                               x-model="productoNombre"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800 uppercase">
                        <input type="hidden" name="producto_id" id="producto_id" x-model="productoId">
                    </div>
                </div>

                <!-- 6. Forma Farmacéutica -->
                <div class="sm:col-span-1 md:col-span-1 lg:col-span-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Forma Farmacéutica
                    </label>
                    <input type="text" name="forma_farmaceutica" id="forma_farmaceutica" x-model="formaFarmaceutica"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800 uppercase">
                </div>

                <!-- 7. Número de Lote -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Número de Lote <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="lote" required value="{{ old('lote') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-black text-cyan-900 uppercase">
                </div>

                <!-- 8. Tamaño de Lote y Unidad (KG / UND) -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Tamaño de Lote <span class="text-red-500">*</span>
                    </label>
                    <div class="relative flex items-stretch">
                        <input type="number" step="0.001" min="0.001" name="tamano_lote" required value="{{ old('tamano_lote') }}"
                               class="w-2/3 px-3 py-2.5 rounded-l-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-mono font-black text-slate-900">
                        <select name="tamano_lote_unidad"
                                class="w-1/3 px-2 py-2.5 rounded-r-xl border border-l-0 border-slate-300 bg-slate-50 focus:border-cyan-500 text-xs font-black text-slate-700 uppercase cursor-pointer">
                            <option value="KG" {{ old('tamano_lote_unidad', 'KG') == 'KG' ? 'selected' : '' }}>KG</option>
                            <option value="UND" {{ old('tamano_lote_unidad') == 'UND' ? 'selected' : '' }}>UND</option>
                            <option value="L" {{ old('tamano_lote_unidad') == 'L' ? 'selected' : '' }}>L</option>
                        </select>
                    </div>
                </div>

                <!-- 9. Vigencia en Meses -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Vigencia (Meses)
                    </label>
                    <input type="number" name="vigencia_meses" id="vigencia_meses" x-model="vigenciaMeses" @input="calcularVencimiento()" min="1" max="120"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-mono font-bold text-slate-800 text-center">
                </div>

                <!-- 10. Fecha de Fabricación (AAAA-MM) -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha Fabricación (AAAA-MM) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="fecha_fabricacion" required 
                           x-model="fechaFabricacion"
                           @input="calcularVencimiento()"
                           pattern="\d{4}-\d{2}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-mono font-bold text-slate-800 text-center">
                </div>

                <!-- 11. Fecha de Vencimiento (AAAA-MM) (Autocalculada) -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha Vencimiento (AAAA-MM) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="fecha_vencimiento" required 
                           x-model="fechaVencimiento"
                           pattern="\d{4}-\d{2}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-mono font-bold text-slate-800 text-center bg-cyan-50/50">
                </div>

                <!-- 12. Laboratorio Maquilador -->
                <div class="sm:col-span-2 md:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Laboratorio Maquilador <span class="text-red-500">*</span>
                    </label>
                    <select name="maquilador_id" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800">
                        <option value="">-- Seleccione Laboratorio Maquilador --</option>
                        @foreach($maquiladores as $m)
                            <option value="{{ $m->id }}" {{ old('maquilador_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Bloque 2: Presentaciones del Producto (Repeater Inteligente) -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#028838] to-emerald-500 flex items-center justify-center text-white shadow-[0_4px_12px_rgba(2,136,56,0.3)]">
                        <i class="fas fa-boxes text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-display text-base font-black text-slate-900 tracking-tight">Presentaciones del Producto</h3>
                        <p class="text-xs text-slate-500">Ingrese el código de Ítem para arrastrar automáticamente la presentación, unidad, producto y vigencia</p>
                    </div>
                </div>

                <button type="button" @click="agregarFila()" 
                        class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-cyan-700 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 shadow-sm transition-all">
                    <i class="fas fa-plus mr-1.5"></i> Agregar Presentación
                </button>
            </div>

            <!-- Tabla Repeater de Presentaciones -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-900 text-white text-[10px] font-black uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-cyan-300"># Ítem (Código)</th>
                            <th class="px-4 py-3">Presentación / Nombre</th>
                            <th class="px-4 py-3">Cantidad Programada</th>
                            <th class="px-4 py-3">Unidad Medida</th>
                            <th class="px-4 py-3">Número SDM</th>
                            <th class="px-4 py-3 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="(fila, index) in filas" :key="index">
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- Código Ítem con Autocompletado Instantáneo -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="relative">
                                        <input type="text" :name="'items[' + index + '][codigo_item]'" 
                                               x-model="fila.codigo_item"
                                               @input.debounce.250ms="buscarItem(fila, index)"
                                               @change="buscarItem(fila, index)"
                                               @blur="buscarItem(fila, index)"
                                               @keydown.enter.prevent="buscarItem(fila, index)"
                                               placeholder="Ej: 10001"
                                               required
                                               class="w-36 px-3 py-1.5 rounded-lg border border-slate-300 focus:border-cyan-500 font-mono text-xs font-black text-cyan-900 uppercase">
                                        <span x-show="fila.cargando" class="absolute right-2 top-2 text-cyan-600">
                                            <i class="fas fa-spinner fa-spin text-xs"></i>
                                        </span>
                                    </div>
                                    <span x-show="fila.noEncontrado" class="text-[10px] text-amber-600 font-bold block mt-0.5" style="display: none;">
                                        <i class="fas fa-exclamation-circle text-[9px]"></i> Ítem no registrado
                                    </span>
                                </td>

                                <!-- Presentación Arrastrada (Campo Bloqueado / Solo Lectura) -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="relative">
                                        <input type="text" :name="'items[' + index + '][presentacion]'" 
                                               x-model="fila.presentacion"
                                               readonly
                                               tabindex="-1"
                                               required
                                               placeholder="Se autocompleta con el ítem..."
                                               class="w-full min-w-[220px] px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-100/90 text-slate-700 font-bold text-xs uppercase cursor-not-allowed select-none focus:outline-none focus:ring-0 shadow-inner">
                                        <span x-show="fila.presentacion" class="absolute right-2.5 top-2 text-emerald-500 text-xs" title="Autocompletado exitoso" style="display: none;">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    </div>
                                </td>

                                <!-- Cantidad Programada -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <input type="number" step="0.001" min="0.001" :name="'items[' + index + '][cantidad_programada]'" 
                                           x-model="fila.cantidad_programada"
                                           required
                                           class="w-32 px-3 py-1.5 rounded-lg border border-slate-300 focus:border-cyan-500 text-xs font-mono font-black text-slate-900 text-right">
                                </td>

                                <!-- Unidad de Medida -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <select :name="'items[' + index + '][unidad_medida]'" 
                                            x-model="fila.unidad_medida"
                                            class="px-3 py-1.5 rounded-lg border border-slate-300 focus:border-cyan-500 text-xs font-bold text-slate-800">
                                        <option value="UND">UND</option>
                                        <option value="KG">KG</option>
                                        <option value="L">L</option>
                                        <option value="FRASCOS">FRASCOS</option>
                                        <option value="CAJAS">CAJAS</option>
                                        <option value="BOLSAS">BOLSAS</option>
                                        <option value="JERINGAS">JERINGAS</option>
                                    </select>
                                </td>

                                <!-- Número SDM -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <input type="text" :name="'items[' + index + '][sdm]'" 
                                           x-model="fila.sdm"
                                           class="w-28 px-3 py-1.5 rounded-lg border border-slate-300 focus:border-cyan-500 font-mono text-xs font-bold text-slate-700 uppercase">
                                </td>

                                <!-- Eliminar Fila -->
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <button type="button" @click="eliminarFila(index)" 
                                            :disabled="filas.length <= 1"
                                            class="p-1.5 text-slate-400 hover:text-red-600 disabled:opacity-30 disabled:hover:text-slate-400 transition-colors">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Observaciones y Envío -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-4">
            <div>
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                    Observaciones Especiales de Producción
                </label>
                <textarea name="observaciones" rows="2"
                          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-medium text-slate-800">{{ old('observaciones') }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <span class="text-xs text-slate-500 font-medium flex items-center">
                    <i class="fas fa-shield-alt text-cyan-600 mr-2"></i>
                    La orden se creará con estado <strong class="text-slate-800 ml-1">OP CREADA</strong> y se redirigirá al dashboard.
                </span>

                <div class="flex items-center space-x-3">
                    <a href="{{ route('maquila.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-slate-600 hover:bg-slate-100 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-[#005889] to-[#06B6D4] shadow-3d-button hover:shadow-3d-cyan transition-all transform hover:-translate-y-0.5">
                        Guardar Orden de Producción (OP CREADA)
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function maquilaCreateWizard() {
    return {
        productoNombre: @json(old('producto_nombre', '')),
        productoId: @json(old('producto_id', '')),
        formaFarmaceutica: @json(old('forma_farmaceutica', '')),
        fechaFabricacion: @json(old('fecha_fabricacion', date('Y-m'))),
        vigenciaMeses: @json(old('vigencia_meses', 24)),
        fechaVencimiento: @json(old('fecha_vencimiento', date('Y-m', strtotime('+2 years')))),
        filas: [
            {
                codigo_item: '',
                presentacion: '',
                cantidad_programada: '',
                unidad_medida: 'UND',
                sdm: '',
                cargando: false,
                noEncontrado: false
            }
        ],

        init() {
            this.calcularVencimiento();
        },

        calcularVencimiento() {
            if (!this.fechaFabricacion) return;
            const parts = this.fechaFabricacion.split('-');
            if (parts.length !== 2) return;
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const vig = parseInt(this.vigenciaMeses, 10) || 24;

            if (isNaN(year) || isNaN(month)) return;

            const totalMonths = (year * 12) + (month - 1) + vig;
            const expYear = Math.floor(totalMonths / 12);
            const expMonth = (totalMonths % 12) + 1;

            this.fechaVencimiento = `${expYear}-${String(expMonth).padStart(2, '0')}`;
        },

        agregarFila() {
            this.filas.push({
                codigo_item: '',
                presentacion: '',
                cantidad_programada: '',
                unidad_medida: 'UND',
                sdm: '',
                cargando: false,
                noEncontrado: false
            });
        },

        eliminarFila(index) {
            if (this.filas.length > 1) {
                this.filas.splice(index, 1);
            }
        },

        buscarItem(fila, index) {
            const codigo = fila.codigo_item ? fila.codigo_item.trim() : '';
            if (!codigo) {
                fila.noEncontrado = false;
                return;
            }

            fila.cargando = true;
            fila.noEncontrado = false;
            fetch(`/api/maquilas/item-lookup/${encodeURIComponent(codigo)}`)
                .then(r => r.json())
                .then(data => {
                    fila.cargando = false;
                    if (data.found) {
                        fila.presentacion = data.presentacion || data.descripcion || '';
                        fila.unidad_medida = data.unidad || 'UND';
                        fila.noEncontrado = false;

                        // Arrastrar automáticamente el nombre del producto si aplica
                        if (data.producto_nombre) {
                            this.productoNombre = data.producto_nombre;
                            this.productoId = data.producto_id || '';
                        }
                        // Arrastrar automáticamente la forma farmacéutica
                        if (data.forma_farmaceutica) {
                            this.formaFarmaceutica = data.forma_farmaceutica;
                        }
                        // Arrastrar automáticamente la vigencia en meses y autocalcular fecha de vencimiento
                        if (data.vigencia_meses) {
                            this.vigenciaMeses = data.vigencia_meses;
                            this.calcularVencimiento();
                        }
                    } else {
                        fila.noEncontrado = true;
                    }
                })
                .catch(err => {
                    fila.cargando = false;
                    console.error('Error buscando ítem:', err);
                });
        }
    };
}
</script>
@endsection
