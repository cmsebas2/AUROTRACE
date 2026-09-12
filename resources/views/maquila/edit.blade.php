@extends('layouts.app')

@section('header_title', 'Edición Integral de Expediente / Orden de Maquila')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="maquilaEditWizard({{ json_encode($order) }}, {{ json_encode($order->items) }})">
    
    <!-- Header y Navegación -->
    <div class="flex items-center justify-between">
        <a href="{{ route('maquila.index') }}" class="text-xs font-black uppercase tracking-wider text-slate-500 hover:text-cyan-600 flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Dashboard
        </a>
        <div class="flex items-center space-x-2">
            <span class="px-3 py-1 rounded-full bg-cyan-900 text-cyan-200 font-mono text-[10px] font-black uppercase tracking-widest shadow-sm">
                Edición Habilitada · Audit Trail CFR 21 Part 11
            </span>
        </div>
    </div>

    @if (session('error'))
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-sm flex items-center space-x-3">
            <div class="p-1.5 rounded-lg bg-red-100 text-red-600 flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-sm"></i>
            </div>
            <div class="text-xs font-bold">{{ session('error') }}</div>
        </div>
    @endif

    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm flex items-center space-x-3">
            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-600 flex-shrink-0">
                <i class="fas fa-check-circle text-sm"></i>
            </div>
            <div class="text-xs font-bold">{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-sm">
            <div class="font-bold text-xs mb-1 flex items-center space-x-2">
                <i class="fas fa-info-circle text-red-600"></i>
                <span>Verifique los siguientes campos:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-0.5 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario Edición de Orden de Maquila -->
    <form action="{{ route('maquila.update', $order->id, false) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Bloque 1: Identificación y Documentación de la Orden -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-6">
            <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-600 to-blue-700 flex items-center justify-center text-white shadow-3d-cyan">
                    <i class="fas fa-edit text-lg"></i>
                </div>
                <div>
                    <h2 class="font-display text-lg font-black text-slate-900 tracking-tight">1. Datos Generales de la Orden & Estado</h2>
                    <p class="text-xs text-slate-500">Modifique el número de OP, Pre-Orden, ODM, fecha de creación y estado del ciclo</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5">
                
                <!-- Fecha de Creación -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha de Creación <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha_creacion" required value="{{ old('fecha_creacion', $order->fecha_creacion ? \Carbon\Carbon::parse($order->fecha_creacion)->format('Y-m-d') : date('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800">
                </div>

                <!-- Pre Orden -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Pre Orden
                    </label>
                    <input type="text" name="pre_orden" value="{{ old('pre_orden', $order->pre_orden) }}" placeholder="PL-01-G"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-black uppercase tracking-wider text-slate-800">
                </div>

                <!-- Número de OP -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Número de OP <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="op" required value="{{ old('op', $order->op) }}" placeholder="OP-2026-001"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-black uppercase text-cyan-900">
                </div>

                <!-- Número de ODM -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Número ODM
                    </label>
                    <input type="text" name="numero_odm" value="{{ old('numero_odm', $order->numero_odm) }}" placeholder="ODM-2026-001"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold uppercase text-slate-800">
                </div>

                <!-- Estado Ciclo de Vida -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Estado del Ciclo <span class="text-red-500">*</span>
                    </label>
                    <select name="estado" required class="w-full px-3 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-900 bg-white">
                        <option value="OP CREADA" {{ old('estado', $order->estado) === 'OP CREADA' ? 'selected' : '' }}>OP CREADA</option>
                        <option value="OP EN PRODUCCION" {{ old('estado', $order->estado) === 'OP EN PRODUCCION' ? 'selected' : '' }}>OP EN PRODUCCION</option>
                        <option value="OP TERMINADA - BR PENDIENTE" {{ old('estado', $order->estado) === 'OP TERMINADA - BR PENDIENTE' ? 'selected' : '' }}>OP TERMINADA - BR PENDIENTE</option>
                        <option value="BR REVISION DT" {{ old('estado', $order->estado) === 'BR REVISION DT' ? 'selected' : '' }}>BR REVISION DT</option>
                        <option value="BR REVISION CALIDAD" {{ old('estado', $order->estado) === 'BR REVISION CALIDAD' ? 'selected' : '' }}>BR REVISION CALIDAD</option>
                        <option value="BR CERRADO" {{ old('estado', $order->estado) === 'BR CERRADO' ? 'selected' : '' }}>BR CERRADO</option>
                        <option value="BR ABIERTO" {{ old('estado', $order->estado) === 'BR ABIERTO' ? 'selected' : '' }}>BR ABIERTO</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Bloque 2: Información del Producto Farmacéutico -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-6">
            <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-cyan-50 border border-cyan-200 text-cyan-600 flex items-center justify-center shadow-sm">
                    <i class="fas fa-pills text-lg"></i>
                </div>
                <div>
                    <h2 class="font-display text-lg font-black text-slate-900 tracking-tight">2. Especificaciones del Producto & Lote</h2>
                    <p class="text-xs text-slate-500">Nombre comercial, forma farmacéutica, lote técnico y tamaño programado</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                
                <!-- Nombre del Producto -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Nombre del Producto Farmacéutico <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="producto_nombre" x-model="productoNombre" required
                           value="{{ old('producto_nombre', $order->producto_nombre) }}" placeholder="Ej: AMOXICILINA 500 mg"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold uppercase text-slate-900">
                </div>

                <!-- Forma Farmacéutica -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Forma Farmacéutica
                    </label>
                    <input type="text" name="forma_farmaceutica" x-model="formaFarmaceutica"
                           value="{{ old('forma_farmaceutica', $order->forma_farmaceutica) }}" placeholder="Ej: JARABE, TABLETAS"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold uppercase text-slate-800">
                </div>

                <!-- Número de Lote Físico -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        # Número de Lote Físico <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="lote" required value="{{ old('lote', $order->lote) }}" placeholder="Ej: LOTE-12345"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-mono font-black uppercase text-cyan-900">
                </div>

                <!-- Tamaño Lote Programado -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Tamaño de Lote Programado <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="any" name="tamano_lote" required value="{{ old('tamano_lote', $order->tamano_lote) }}" placeholder="10000"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold text-slate-900">
                </div>

                <!-- Unidad Medida Tamaño Lote -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Unidad de Medida Lote
                    </label>
                    <select name="unidad_medida" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold text-slate-900 bg-white">
                        <option value="UND" {{ old('unidad_medida', $order->unidad_medida) === 'UND' ? 'selected' : '' }}>UND (Unidades)</option>
                        <option value="KG" {{ old('unidad_medida', $order->unidad_medida) === 'KG' ? 'selected' : '' }}>KG (Kilogramos)</option>
                        <option value="L" {{ old('unidad_medida', $order->unidad_medida) === 'L' ? 'selected' : '' }}>L (Litros)</option>
                        <option value="ML" {{ old('unidad_medida', $order->unidad_medida) === 'ML' ? 'selected' : '' }}>ML (Mililitros)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Bloque 3: Maquilador & Fechas del Ciclo -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-6">
            <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center shadow-sm">
                    <i class="fas fa-industry text-lg"></i>
                </div>
                <div>
                    <h2 class="font-display text-lg font-black text-slate-900 tracking-tight">3. Maquilador & Cronograma Técnico</h2>
                    <p class="text-xs text-slate-500">Asignación del laboratorio maquilador y fechas clave de fabricación / vencimiento</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
                
                <!-- Maquilador Asignado -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Maquilador Asignado <span class="text-red-500">*</span>
                    </label>
                    <select name="maquilador_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold text-slate-900 bg-white">
                        <option value="">-- Seleccionar Laboratorio Maquilador --</option>
                        @foreach($maquiladores as $maq)
                            <option value="{{ $maq->id }}" {{ old('maquilador_id', $order->maquilador_id) == $maq->id ? 'selected' : '' }}>
                                {{ $maq->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Fecha Fabricación -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha Fabricación (AAAA-MM) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="fecha_fabricacion" required value="{{ old('fecha_fabricacion', $order->fecha_fabricacion) }}" placeholder="YYYY-MM"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-mono font-bold text-slate-800">
                </div>

                <!-- Fecha Vencimiento -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha Vencimiento (AAAA-MM) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="fecha_vencimiento" required value="{{ old('fecha_vencimiento', $order->fecha_vencimiento) }}" placeholder="YYYY-MM"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-mono font-bold text-slate-800">
                </div>

                <!-- Fecha Envío a Maquila -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha Envío a Maquila
                    </label>
                    <input type="date" name="fecha_envio_maquila" value="{{ old('fecha_envio_maquila', $order->fecha_envio_maquila ? \Carbon\Carbon::parse($order->fecha_envio_maquila)->format('Y-m-d') : '') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-medium text-slate-800">
                </div>

                <!-- Fecha Llegada BR -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha Llegada Batch Record
                    </label>
                    <input type="date" name="fecha_llegada_br" value="{{ old('fecha_llegada_br', $order->fecha_llegada_br ? \Carbon\Carbon::parse($order->fecha_llegada_br)->format('Y-m-d') : '') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-medium text-slate-800">
                </div>
            </div>
        </div>

        <!-- Bloque 4: Presentaciones del Producto (Ítems / SKU) -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center shadow-sm">
                        <i class="fas fa-boxes text-lg"></i>
                    </div>
                    <div>
                        <h2 class="font-display text-lg font-black text-slate-900 tracking-tight">4. Presentaciones Comertiales (Ítems / SKU)</h2>
                        <p class="text-xs text-slate-500">Agregue o edite las presentaciones comerciales asociadas a este lote</p>
                    </div>
                </div>

                <button type="button" @click="agregarFila()" class="px-3.5 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-slate-900 hover:bg-slate-800 shadow-md flex items-center space-x-1 transition-all">
                    <i class="fas fa-plus text-xs"></i>
                    <span>+ Agregar Presentación</span>
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(fila, index) in filas" :key="index">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 relative group hover:border-cyan-300 transition-colors">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
                            
                            <!-- Código Ítem -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-600 uppercase tracking-wider mb-1">
                                    Código Ítem (SKU)
                                </label>
                                <div class="relative">
                                    <input type="text" :name="'items[' + index + '][codigo_item]'" x-model="fila.codigo_item"
                                           @blur="buscarItem(fila, index)" placeholder="770..."
                                           class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono font-bold uppercase text-slate-900 focus:border-cyan-500">
                                    <div x-show="fila.cargando" class="absolute right-2 top-2 text-cyan-600 text-xs">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Presentación -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-slate-600 uppercase tracking-wider mb-1">
                                    Descripción / Presentación Comercial
                                </label>
                                <input type="text" :name="'items[' + index + '][presentacion]'" x-model="fila.presentacion"
                                       readonly placeholder="Se autocompleta con el ítem (# Código)..."
                                       class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-100 text-xs font-bold uppercase text-slate-900 cursor-not-allowed">
                            </div>

                            <!-- Cantidad Programada -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-600 uppercase tracking-wider mb-1">
                                    Cantidad Programada
                                </label>
                                <input type="number" step="any" :name="'items[' + index + '][cantidad_programada]'" x-model="fila.cantidad_programada"
                                       placeholder="10000"
                                       class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-900 focus:border-cyan-500">
                            </div>

                            <!-- Unidad de Medida & Botón Quitar -->
                            <div class="flex items-center space-x-2">
                                <div class="flex-1">
                                    <label class="block text-[10px] font-black text-slate-600 uppercase tracking-wider mb-1">Unidad</label>
                                    <select :name="'items[' + index + '][unidad_medida]'" x-model="fila.unidad_medida"
                                            class="w-full px-2 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-900 bg-white">
                                        <option value="UND">UND</option>
                                        <option value="CAJA">CAJA</option>
                                        <option value="FRASCO">FRASCO</option>
                                        <option value="BLISTER">BLISTER</option>
                                        <option value="AMPOLLA">AMPOLLA</option>
                                    </select>
                                </div>
                                <button type="button" @click="eliminarFila(index)" x-show="filas.length > 1"
                                        class="p-2.5 rounded-xl text-red-500 hover:bg-red-100 hover:text-red-700 transition-colors mt-4" title="Quitar Presentación">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <div x-show="fila.noEncontrado" class="text-[10px] text-amber-700 font-bold bg-amber-50 px-3 py-1 rounded-lg border border-amber-200">
                            Ítem no encontrado en catálogo maestro. Puede digitar la descripción manualmente.
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Bloque 5: Rendimiento, Ubicación Física & Observaciones -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-6">
            <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center shadow-sm">
                    <i class="fas fa-archive text-lg"></i>
                </div>
                <div>
                    <h2 class="font-display text-lg font-black text-slate-900 tracking-tight">5. Rendimiento Real, Ubicación & Observaciones</h2>
                    <p class="text-xs text-slate-500">Defina el rendimiento del lote, la ubicación física del expediente y observaciones finales</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                
                <!-- Total Producto Fabricado -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Total Fabricado Real
                    </label>
                    <input type="number" step="any" name="total_producto_terminado_fabricado" value="{{ old('total_producto_terminado_fabricado', $order->total_producto_terminado_fabricado) }}" placeholder="10000"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold text-slate-900">
                </div>

                <!-- Rendimiento Real % -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Rendimiento Real (%)
                    </label>
                    <input type="number" step="0.01" name="rendimiento_real" value="{{ old('rendimiento_real', $order->rendimiento_real) }}" placeholder="98.5"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-mono font-black text-emerald-800">
                </div>

                <!-- Ubicación Física -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Ubicación del Expediente Físico
                    </label>
                    <input type="text" name="posicion_archivo_fisico" value="{{ old('posicion_archivo_fisico', $order->posicion_archivo_fisico) }}" placeholder="Ej: R 1 N 1 A 123 S 1"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-mono font-bold uppercase text-cyan-900">
                </div>

                <!-- Observaciones -->
                <div class="sm:col-span-2 md:col-span-3">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Observaciones Generales de la Orden
                    </label>
                    <textarea name="observaciones" rows="3" placeholder="Observaciones técnicas, novedades de producción o acuerdos comerciales..."
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-medium text-slate-800">{{ old('observaciones', $order->observaciones) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white flex items-center justify-between">
            <a href="{{ route('maquila.index') }}" class="px-6 py-3 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                Cancelar
            </a>
            
            <button type="submit" class="px-8 py-3 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-cyan-600 via-[#005889] to-[#003B5C] shadow-3d-button hover:shadow-3d-cyan transition-all transform hover:-translate-y-0.5 flex items-center space-x-2">
                <i class="fas fa-save text-sm"></i>
                <span>Guardar Cambios de Expediente</span>
            </button>
        </div>
    </form>
</div>

<script>
function maquilaEditWizard(orderObj, existingItems) {
    return {
        productoNombre: orderObj ? orderObj.producto_nombre : '',
        formaFarmaceutica: orderObj ? orderObj.forma_farmaceutica : '',
        filas: (existingItems && existingItems.length > 0)
            ? existingItems.map(it => ({
                codigo_item: it.codigo_item || '',
                presentacion: it.presentacion || '',
                cantidad_programada: it.cantidad_programada || '',
                unidad_medida: it.unidad_medida || 'UND',
                sdm: it.sdm || '',
                cargando: false,
                noEncontrado: false
            }))
            : [
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
                fila.cargando = false;
                return;
            }

            fila.cargando = true;
            fila.noEncontrado = false;
            
            fetch(`/maquilas/item-lookup/${encodeURIComponent(codigo)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(r => {
                    if (!r.ok) {
                        return fetch(`/api/maquilas/item-lookup/${encodeURIComponent(codigo)}`, {
                            headers: { 'Accept': 'application/json' }
                        }).then(r2 => r2.json());
                    }
                    return r.json();
                })
                .then(data => {
                    fila.cargando = false;
                    if (data && data.found) {
                        fila.presentacion = data.referencia_completa || data.presentacion || data.descripcion || fila.presentacion;
                        fila.unidad_medida = data.unidad || fila.unidad_medida || 'UND';
                        fila.noEncontrado = false;

                        if (data.producto_nombre && !this.productoNombre) {
                            this.productoNombre = data.producto_nombre;
                        }
                        if (data.forma_farmaceutica && !this.formaFarmaceutica) {
                            this.formaFarmaceutica = data.forma_farmaceutica;
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
