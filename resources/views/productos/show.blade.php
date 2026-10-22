@extends('layouts.app')

@section('header_title', 'Ficha Maestra de Producto')

@section('content')
@php
    $imgSrc = !empty($product['image']) && str_starts_with($product['image'], 'data:')
        ? $product['image']
        : asset('img/productos/' . ($product['image'] ?? 'default.png'));

    $initialRawMaterials = [];
    foreach ($product['raw_materials'] as $idx => $rm) {
        $initialRawMaterials[] = [
            'uid'        => 'rm_' . $idx . '_' . rand(100, 999),
            'code'       => $rm['code'] ?? '',
            'name'       => $rm['description'] ?? '',
            'function'   => ($rm['function'] ?? '-') !== '-' ? $rm['function'] : 'PRINCIPIO ACTIVO',
            'unit'       => $rm['unit'] ?? 'KIL',
            'percentage' => $rm['quantity'] ?? '',
        ];
    }

    $initialPackaging = [];
    foreach ($product['packaging'] as $idx => $pk) {
        $initialPackaging[] = [
            'uid'            => 'pk_' . $idx . '_' . rand(100, 999),
            'code'           => $pk['code'] ?? '',
            'name'           => $pk['description'] ?? '',
            'type'           => $pk['tipo_material'] ?? 'MATERIAL DE EMPAQUE',
            'classification' => $pk['material_clasificacion'] ?? 'N.A.',
            'unit'           => $pk['unit'] ?? 'UND',
            'percentage'     => $pk['quantity'] ?? '',
        ];
    }
@endphp

<div class="space-y-5 max-w-7xl mx-auto"
     x-data="formulaMaestraBuilder()">

    <!-- Breadcrumb Superior -->
    <div class="flex flex-wrap justify-between items-center gap-3">
        <a href="{{ route('productos.index') }}" class="text-slate-600 hover:text-cyan-600 font-bold text-xs flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Catálogo de Productos
        </a>

        <div class="flex items-center gap-2">
            <a href="{{ route('productos.edit', $product['id']) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-black bg-white text-slate-700 hover:bg-slate-100 border border-slate-300 transition-all flex items-center shadow-sm">
                <i class="fas fa-pen-to-square mr-1.5 text-sky-600"></i>
                Editar Datos del Producto
            </a>

            <a href="{{ route('productos.imprimir', $product['id']) }}" target="_blank"
               class="px-4 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-[#005889] to-[#06B6D4] text-white shadow-3d-button hover:shadow-3d-cyan transition-all flex items-center">
                <i class="fas fa-print mr-1.5"></i>
                Imprimir Ficha
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0">
                    <i class="fas fa-check text-xs"></i>
                </span>
                <p class="text-xs sm:text-sm font-bold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <!-- =================================================================== -->
    <!-- BLOQUE 1 (ARRIBA): TODA LA INFORMACIÓN TÉCNICA DEL PRODUCTO         -->
    <!-- =================================================================== -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Cabecera: Imagen + Nombre del Producto + Especificaciones Clave -->
        <div class="flex flex-col lg:flex-row items-start gap-6">
            <!-- Imagen del Producto -->
            <div class="w-28 h-28 bg-gradient-to-b from-slate-50 to-white rounded-2xl flex-shrink-0 flex items-center justify-center border border-slate-200/80 overflow-hidden shadow-3d-card p-3">
                <img src="{{ $imgSrc }}"
                     alt="{{ $product['name'] }}"
                     class="max-h-24 max-w-full object-contain filter drop-shadow-[0_4px_10px_rgba(0,0,0,0.12)]"
                     onerror="this.onerror=null; this.outerHTML='<div class=\'w-12 h-12 text-cyan-600 flex items-center justify-center\'><svg class=\'w-10 h-10\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z\'></path></svg></div>';">
            </div>

            <!-- Información Principal del Producto -->
            <div class="flex-1 w-full space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="font-display text-2xl lg:text-3xl font-black text-slate-900 tracking-tight uppercase">
                            {{ $product['name'] }}
                        </h1>
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 text-[10px] font-black rounded-full border border-emerald-200 uppercase tracking-widest">
                            ● {{ $product['status'] ?? 'ACTIVO' }}
                        </span>
                    </div>

                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 font-black uppercase tracking-wider block">Lotes Fabricados (YTD)</span>
                        <span class="font-display text-xl font-black text-cyan-700">{{ $product['manufactured_lots'] }} <small class="text-[10px] text-slate-400 font-bold">OPs</small></span>
                    </div>
                </div>

                <!-- Grilla de Especificaciones: Registro ICA, Forma Farmacéutica, Vida Útil y Fórmula Maestra -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200/80">
                        <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block mb-0.5">
                            <i class="fas fa-certificate mr-1 text-amber-600"></i> Registro ICA
                        </span>
                        <span class="text-xs sm:text-sm font-black text-slate-900 uppercase">
                            {{ $product['ica_license'] ?: 'N/A' }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-cyan-50/70 border border-cyan-200/80">
                        <span class="text-[10px] font-black uppercase tracking-wider text-cyan-800 block mb-0.5">
                            <i class="fas fa-capsules mr-1 text-cyan-600"></i> Forma Farmacéutica
                        </span>
                        <span class="text-xs sm:text-sm font-black text-slate-900 uppercase">
                            {{ $product['pharmaceutical_form'] ?: 'N/A' }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-purple-50/70 border border-purple-200/80">
                        <span class="text-[10px] font-black uppercase tracking-wider text-purple-800 block mb-0.5">
                            <i class="fas fa-hourglass-half mr-1 text-purple-600"></i> Tiempo de Vida Útil
                        </span>
                        <span class="text-xs sm:text-sm font-black text-slate-900 uppercase">
                            {{ !empty($product['vigencia_meses']) ? $product['vigencia_meses'] . ' MESES' : 'N/A' }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-200/80">
                        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-800 block mb-0.5">
                            <i class="fas fa-flask mr-1 text-indigo-600"></i> Fórmula Maestra
                        </span>
                        <span class="text-xs sm:text-sm font-black text-slate-900 uppercase">
                            {{ $product['formula_maestra'] ?: 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- Principio(s) Activo(s) + Concentración y Presentaciones (PRODUCTO + PRESENTACIÓN) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 pt-1">
                    <!-- Principio(s) Activo(s) y su Concentración -->
                    <div class="lg:col-span-5 p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/80">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-emerald-900 mb-2.5 flex items-center gap-1.5">
                            <i class="fas fa-atom text-emerald-600"></i> Principio(s) Activo(s) y Concentración
                        </h4>
                        @if(!empty($product['active_ingredients']))
                            <div class="space-y-1.5">
                                @foreach($product['active_ingredients'] as $act)
                                    <div class="flex items-center justify-between bg-white px-3 py-2 rounded-xl border border-emerald-200/80 text-xs shadow-sm">
                                        <span class="font-bold text-slate-800">{{ $act['name'] ?? 'Principio Activo' }}</span>
                                        <span class="px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white font-black text-xs">
                                            {{ $act['concentration'] ?? '0' }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex items-center justify-between bg-white px-3 py-2 rounded-xl border border-emerald-200/80 text-xs">
                                <span class="font-bold text-slate-800">{{ $productDb->active_ingredient ?: 'Principio Activo' }}</span>
                                <span class="px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white font-black text-xs">
                                    {{ $productDb->active_ingredient_concentration ?? '0' }}%
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Presentaciones Comerciales: PRODUCTO + PRESENTACIÓN + TIPO DE ENVASE -->
                    <div class="lg:col-span-7 p-4 rounded-2xl bg-indigo-50/60 border border-indigo-200/80">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-indigo-900 mb-2.5 flex items-center gap-1.5">
                            <i class="fas fa-boxes-packing text-indigo-600"></i> Presentaciones del Producto (Producto + Presentación + Envase)
                        </h4>
                        @if($product['presentations'] && $product['presentations']->count() > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($product['presentations'] as $pItem)
                                    @php
                                        $unitNorm = ['G' => 'g', 'KG' => 'Kg', 'ML' => 'mL', 'L' => 'L'];
                                        $uDisp = $unitNorm[strtoupper(trim($pItem->unit_measure ?? ''))] ?? ($pItem->unit_measure ?? '');
                                        $qtyDisp = $pItem->quantity_value !== null
                                            ? rtrim(rtrim(number_format((float) $pItem->quantity_value, 2, '.', ''), '0'), '.') . ' ' . $uDisp
                                            : $pItem->name;
                                    @endphp
                                    <div class="flex items-center justify-between gap-2 bg-white px-3 py-2 rounded-xl border border-indigo-200/80 text-xs shadow-sm">
                                        <div class="truncate">
                                            <span class="font-black text-slate-900 uppercase">{{ $product['name'] }}</span>
                                            <span class="text-indigo-600 font-black mx-1">—</span>
                                            <span class="font-black text-indigo-700">{{ $qtyDisp }}</span>
                                        </div>
                                        @if($pItem->container_type)
                                            <span class="px-2 py-0.5 rounded-lg bg-indigo-100 text-indigo-800 font-bold text-[10px] shrink-0">
                                                {{ $pItem->container_type }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-white px-3 py-2 rounded-xl border border-indigo-200/80 text-xs font-bold text-slate-700">
                                {{ $product['name'] }} — {{ $product['presentation_name'] ?: 'Sin presentación registrada' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- BLOQUE 2 (ABAJO): MAQUILADORES Y CAPACIDAD MÍNIMA Y MÁXIMA          -->
    <!-- =================================================================== -->
    <div class="card-3d p-5 border border-slate-200/80 bg-white">
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <i class="fas fa-industry text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider text-slate-900">
                        Maquiladores Autorizados y Capacidad de Lote (Mínima y Máxima)
                    </h3>
                    <p class="text-[11px] text-slate-500 font-medium">Plantas fabricantes habilitadas y sus rangos operativos de tamaño de lote en Kg o L</p>
                </div>
            </div>
        </div>

        @if(!empty($product['maquiladores']))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                @foreach($product['maquiladores'] as $idx => $mq)
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-50/70 via-white to-slate-50 border border-amber-200/80 shadow-sm flex flex-col justify-between gap-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 text-xs font-black text-slate-900 uppercase">
                                <span class="w-5 h-5 rounded-md bg-amber-500 text-slate-950 text-[10px] font-black flex items-center justify-center">{{ $idx + 1 }}</span>
                                {{ $mq['name'] ?? 'PLANTA PRINCIPAL' }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-black uppercase">
                                Habilitado
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-amber-100">
                            <div class="bg-white px-3 py-2 rounded-xl border border-slate-200">
                                <span class="text-[9px] font-black uppercase text-slate-400 block">Capacidad Mínima</span>
                                <span class="text-xs sm:text-sm font-black text-amber-800">
                                    {{ ($mq['min_batch'] ?? '') !== '' && $mq['min_batch'] !== null ? $mq['min_batch'] : '—' }}
                                    <small class="font-bold">{{ $mq['min_unit'] ?? 'Kg' }}</small>
                                </span>
                            </div>
                            <div class="bg-white px-3 py-2 rounded-xl border border-slate-200">
                                <span class="text-[9px] font-black uppercase text-slate-400 block">Capacidad Máxima</span>
                                <span class="text-xs sm:text-sm font-black text-amber-900">
                                    {{ ($mq['max_batch'] ?? '') !== '' && $mq['max_batch'] !== null ? $mq['max_batch'] : '—' }}
                                    <small class="font-bold">{{ $mq['max_unit'] ?? 'Kg' }}</small>
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 uppercase">
                    <i class="fas fa-building mr-1.5 text-amber-600"></i> {{ $productDb->maquilador ?: 'PLANTA PRINCIPAL AUROFARMA' }}
                </span>
                <span class="font-black text-slate-800">Lote Base: {{ $product['base_batch_size'] }} {{ $product['base_unit'] }}</span>
            </div>
        @endif
    </div>

    <!-- =================================================================== -->
    <!-- BLOQUE 3: PESTAÑAS (FÓRMULA MAESTRA | INSTRUCTIVOS | LOTES | COAS)  -->
    <!-- =================================================================== -->
    <div class="card-3d border border-slate-200/80 overflow-hidden bg-white">
        <div class="border-b border-slate-200/80 bg-slate-50/50 px-6">
            <nav class="flex space-x-6 overflow-x-auto" aria-label="Tabs" id="productTabs">
                <button type="button" onclick="switchTab('formula')" id="tab-formula"
                        class="py-4 px-2 border-b-2 font-display text-xs font-black uppercase tracking-wider border-cyan-500 text-cyan-700 transition-all flex items-center space-x-2 shrink-0 cursor-pointer">
                    <i class="fas fa-flask"></i>
                    <span>Fórmula Maestra</span>
                </button>
                <button type="button" onclick="switchTab('instructivo')" id="tab-instructivo"
                        class="py-4 px-2 border-b-2 font-display text-xs font-black uppercase tracking-wider border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center space-x-2 shrink-0 cursor-pointer">
                    <i class="fas fa-file-invoice"></i>
                    <span>Instructivos Maestros (EBR)</span>
                </button>
                <button type="button" onclick="switchTab('produccion')" id="tab-produccion"
                        class="py-4 px-2 border-b-2 font-display text-xs font-black uppercase tracking-wider border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center space-x-2 shrink-0 cursor-pointer">
                    <i class="fas fa-industry"></i>
                    <span>Historial de Lotes</span>
                </button>
                <button type="button" onclick="switchTab('calidad')" id="tab-calidad"
                        class="py-4 px-2 border-b-2 font-display text-xs font-black uppercase tracking-wider border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center space-x-2 shrink-0 cursor-pointer">
                    <i class="fas fa-shield-alt"></i>
                    <span>Control de Calidad (COAs)</span>
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- =============================================================== -->
            <!-- PESTAÑA 1: FÓRMULA MAESTRA (CON BOTÓN AGREGAR FÓRMULA MAESTRA)  -->
            <!-- =============================================================== -->
            <div id="content-formula" class="block space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 class="font-display text-lg font-black text-slate-800 tracking-tight">
                            Fórmula Maestra: <span class="text-indigo-700">{{ $product['formula_maestra'] ?: 'Sin código asignado' }}</span>
                        </h3>
                        <p class="text-xs text-slate-500">Composición cuali-cuantitativa de materias primas a granel y materiales de envase/empaque</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- BOTÓN PARA AGREGAR / EDITAR FÓRMULA MAESTRA -->
                        <button type="button"
                                @click="showFormulaEditor = !showFormulaEditor"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-black uppercase tracking-wider shadow-md transition-all cursor-pointer">
                            <i class="fas" :class="showFormulaEditor ? 'fa-chevron-up' : 'fa-plus-circle'"></i>
                            <span x-text="showFormulaEditor ? 'Cerrar Editor de Fórmula Maestra' : 'Agregar / Configurar Fórmula Maestra'"></span>
                        </button>
                    </div>
                </div>

                <!-- CONSTRUCTOR DESPLEGABLE DE FÓRMULA MAESTRA (PASO 2) -->
                <div x-show="showFormulaEditor" x-cloak class="p-5 rounded-2xl bg-slate-50 border-2 border-emerald-200 space-y-5 shadow-inner">
                    <form action="{{ route('productos.formula.save', $product['id']) }}" method="POST" autocomplete="off" class="space-y-5">
                        @csrf

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
                            <div>
                                <h4 class="text-sm font-black uppercase text-emerald-900 flex items-center gap-2">
                                    <i class="fas fa-flask-vial text-emerald-600"></i>
                                    Configurador de Fórmula Maestra y Materiales de Empaque
                                </h4>
                                <p class="text-xs text-slate-500">Agrega los códigos de materia prima y material de empaque para guardar la receta en la base de datos</p>
                            </div>
                            <div class="w-full sm:w-64">
                                <label class="block text-[10px] font-black uppercase text-slate-600 mb-1">Código Fórmula Maestra</label>
                                <input type="text" name="formula_maestra" value="{{ $product['formula_maestra'] }}" autocomplete="off"
                                       class="w-full px-3 py-1.5 text-xs font-black text-slate-900 bg-white border border-slate-300 rounded-lg uppercase"
                                       placeholder="Ej. FM-0001">
                            </div>
                        </div>

                        <!-- 1. Materias Primas a Granel -->
                        <div class="bg-white p-4 rounded-xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <h5 class="text-xs font-black uppercase text-emerald-800">1. Materias Primas a Granel (APIs y Excipientes)</h5>
                                <button type="button" @click="addRawMaterial()"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black uppercase cursor-pointer">
                                    + Agregar Materia Prima
                                </button>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(rm, rIdx) in rawMaterials" :key="rm.uid">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-slate-50 p-2 rounded-lg border border-slate-200">
                                        <div class="sm:col-span-2">
                                            <input type="text" :name="'raw_materials[' + rIdx + '][code]'" x-model="rm.code"
                                                   @input="lookupItemCode(rm, 'raw')" autocomplete="off"
                                                   class="w-full px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 bg-white border border-slate-300 rounded uppercase"
                                                   placeholder="Código...">
                                        </div>
                                        <div class="sm:col-span-4">
                                            <input type="text" :name="'raw_materials[' + rIdx + '][name]'" x-model="rm.name" autocomplete="off"
                                                   class="w-full px-2.5 py-1.5 text-xs font-bold text-slate-800 bg-white border border-slate-300 rounded"
                                                   placeholder="Nombre de la materia prima...">
                                        </div>
                                        <div class="sm:col-span-3">
                                            <input type="text" :name="'raw_materials[' + rIdx + '][function]'" x-model="rm.function" autocomplete="off"
                                                   class="w-full px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded"
                                                   placeholder="Función (Ej. API / Excipiente)">
                                        </div>
                                        <div class="sm:col-span-1">
                                            <input type="text" :name="'raw_materials[' + rIdx + '][unit]'" x-model="rm.unit" autocomplete="off"
                                                   class="w-full px-2 py-1.5 text-xs font-black text-center text-slate-700 bg-white border border-slate-300 rounded uppercase"
                                                   placeholder="KIL">
                                        </div>
                                        <div class="sm:col-span-2 flex items-center gap-1.5">
                                            <input type="number" step="0.0001" min="0" :name="'raw_materials[' + rIdx + '][percentage]'" x-model="rm.percentage" autocomplete="off"
                                                   class="w-full px-2.5 py-1.5 text-xs font-black text-right text-emerald-900 bg-emerald-50 border border-emerald-300 rounded"
                                                   placeholder="% Comp.">
                                            <button type="button" @click="rawMaterials.splice(rIdx, 1)"
                                                    class="w-7 h-7 rounded bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0 cursor-pointer">
                                                <i class="fas fa-trash-can text-[10px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- 2. Materiales de Empaque y Acondicionamiento -->
                        <div class="bg-white p-4 rounded-xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <h5 class="text-xs font-black uppercase text-indigo-800">2. Material de Acondicionamiento y Empaque</h5>
                                <button type="button" @click="addPackagingMaterial()"
                                        class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-black uppercase cursor-pointer">
                                    + Agregar Material de Empaque
                                </button>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(pk, kIdx) in packagingMaterials" :key="pk.uid">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-slate-50 p-2 rounded-lg border border-slate-200">
                                        <div class="sm:col-span-2">
                                            <input type="text" :name="'packaging_materials[' + kIdx + '][code]'" x-model="pk.code"
                                                   @input="lookupItemCode(pk, 'pkg')" autocomplete="off"
                                                   class="w-full px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 bg-white border border-slate-300 rounded uppercase"
                                                   placeholder="Código...">
                                        </div>
                                        <div class="sm:col-span-4">
                                            <input type="text" :name="'packaging_materials[' + kIdx + '][name]'" x-model="pk.name" autocomplete="off"
                                                   class="w-full px-2.5 py-1.5 text-xs font-bold text-slate-800 bg-white border border-slate-300 rounded"
                                                   placeholder="Nombre insumo de empaque...">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <input type="text" :name="'packaging_materials[' + kIdx + '][type]'" x-model="pk.type" autocomplete="off"
                                                   class="w-full px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded"
                                                   placeholder="Tipo (Ej. Frasco)">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <input type="text" :name="'packaging_materials[' + kIdx + '][classification]'" x-model="pk.classification" autocomplete="off"
                                                   class="w-full px-2.5 py-1.5 text-xs font-bold text-amber-900 bg-amber-50 border border-amber-300 rounded uppercase"
                                                   placeholder="ETIQUETA / FRASCO">
                                        </div>
                                        <div class="sm:col-span-2 flex items-center gap-1.5">
                                            <input type="number" step="0.01" min="0" :name="'packaging_materials[' + kIdx + '][percentage]'" x-model="pk.percentage" autocomplete="off"
                                                   class="w-full px-2.5 py-1.5 text-xs font-black text-right text-indigo-900 bg-indigo-50 border border-indigo-300 rounded"
                                                   placeholder="Cant / %">
                                            <input type="hidden" :name="'packaging_materials[' + kIdx + '][unit]'" :value="pk.unit || 'UND'">
                                            <button type="button" @click="packagingMaterials.splice(kIdx, 1)"
                                                    class="w-7 h-7 rounded bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0 cursor-pointer">
                                                <i class="fas fa-trash-can text-[10px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="showFormulaEditor = false"
                                    class="px-4 py-2 rounded-xl bg-white border border-slate-300 text-xs font-bold text-slate-700">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-6 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-wider shadow-md cursor-pointer">
                                <i class="fas fa-floppy-disk mr-1"></i> Guardar Fórmula Maestra
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Materias Primas -->
                <div class="space-y-2">
                    <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                        <span>Materias Primas / Granel (APIs & Excipientes)</span>
                    </h4>
                    <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm">
                        <table class="min-w-full divide-y divide-slate-100 text-left">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Código Material</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Nombre del Insumo</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Función Farmacéutica</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider text-right">% Composición</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider text-center">Unidad</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @forelse($product['raw_materials'] as $ing)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs font-mono font-bold text-cyan-700">{{ $ing['code'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs font-bold text-slate-800">{{ $ing['description'] }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs text-slate-600">
                                        @if(str_contains(strtoupper($ing['function']), 'API') || str_contains(strtoupper($ing['function']), 'PRINCIPIO'))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-cyan-50 text-cyan-800 border border-cyan-200">{{ $ing['function'] }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">{{ $ing['function'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs text-right font-mono font-black text-slate-800 bg-slate-50/50">{{ number_format($ing['quantity'], 4, '.', '') }}%</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs text-center font-bold text-slate-600">{{ $ing['unit'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-xs text-slate-400 italic">
                                        No se encontraron materias primas registradas. Haz clic en <strong>"Agregar / Configurar Fórmula Maestra"</strong> arriba para agregarlas.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Materiales de Empaque -->
                <div class="space-y-2 pt-2">
                    <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Material de Acondicionamiento y Empaque</span>
                    </h4>
                    <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm">
                        <table class="min-w-full divide-y divide-slate-100 text-left">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Código Material</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Nombre Insumo Empaque</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Clasificación</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider text-center">U.M.</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider text-right">Porcentaje (%)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @forelse($product['packaging'] as $ing)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs font-mono font-bold text-cyan-700">{{ $ing['code'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs font-bold text-slate-800">{{ $ing['description'] }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs text-slate-600">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">{{ $ing['tipo_material'] }}</span>
                                    </td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs text-slate-600">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-orange-50 text-orange-800 border border-orange-200 shadow-sm uppercase">{{ $ing['material_clasificacion'] }}</span>
                                    </td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs text-center font-bold text-slate-700">{{ $ing['unit'] }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs text-right font-mono font-black text-slate-800 bg-slate-50/50">{{ number_format($ing['quantity'], 2, '.', '') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-6 text-center text-xs text-slate-400 italic">
                                        No se encontraron materiales de empaque registrados.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- =============================================================== -->
            <!-- PESTAÑA 2: INSTRUCTIVOS MAESTROS (EBR)                          -->
            <!-- =============================================================== -->
            <div id="content-instructivo" class="hidden space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h3 class="font-display text-lg font-black text-slate-800 tracking-tight">Instructivos Maestros de Manufactura</h3>
                        <p class="text-xs text-slate-500">Documento base para la generación de Órdenes de Producción y pasos parametrizados</p>
                    </div>
                    @if(!$product['active_plan'])
                        <a href="{{ route('productos.instructivo.edit', $product['id']) }}"
                           class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-cyan-500 to-aurofarma shadow-3d-button hover:shadow-3d-cyan transition-all">
                            <i class="fas fa-plus mr-1.5"></i> Crear Instructivo Maestro
                        </a>
                    @endif
                </div>

                @if($product['active_plan'])
                    <div class="card-3d overflow-hidden border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-left">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3.5 text-[10px] font-black text-slate-500 uppercase">Documento Maestro</th>
                                        <th class="px-6 py-3.5 text-[10px] font-black text-slate-500 uppercase">Cód. Maestro</th>
                                        <th class="px-6 py-3.5 text-[10px] font-black text-slate-500 uppercase">Cód. Interno</th>
                                        <th class="px-6 py-3.5 text-center text-[10px] font-black text-slate-500 uppercase">Versión</th>
                                        <th class="px-6 py-3.5 text-center text-[10px] font-black text-slate-500 uppercase">F. Emisión</th>
                                        <th class="px-6 py-3.5 text-right text-[10px] font-black text-slate-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-inner">
                                                    <i class="fas fa-file-invoice text-lg"></i>
                                                </div>
                                                <div class="ml-3.5">
                                                    <div class="text-xs font-black text-slate-900">INSTRUCTIVO MAESTRO APROBADO</div>
                                                    <div class="text-[10px] text-slate-500 uppercase">Batch Base: {{ number_format($product['active_plan']->master_batch_size, 2, '.', '') }} KG</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs font-mono font-bold text-cyan-700">{{ $product['active_plan']->master_code_header ?? '---' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs font-mono font-bold text-slate-600">{{ $product['active_plan']->internal_code ?? '---' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2.5 py-1 text-[10px] font-black bg-blue-50 text-blue-700 rounded-md border border-blue-200">V{{ $product['active_plan']->version }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-slate-500 font-medium">
                                            {{ \Carbon\Carbon::parse($product['active_plan']->issue_date)->format('Y-m-d') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-bold space-x-2">
                                            <a href="{{ route('productos.instructivo.edit', $product['id']) }}" class="inline-flex items-center px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-colors border border-slate-300 shadow-sm">
                                                <i class="fas fa-print mr-1.5 text-cyan-600"></i> Ver / Imprimir
                                            </a>
                                            <a href="{{ route('productos.instructivo.edit', $product['id']) }}" class="inline-flex items-center px-3 py-1.5 bg-cyan-50 text-cyan-700 rounded-xl hover:bg-cyan-100 transition-colors border border-cyan-200 shadow-sm">
                                                <i class="fas fa-edit mr-1.5"></i> Editar
                                            </a>
                                            <button type="button" onclick="confirmDeletePlan({{ $product['active_plan']->id }})" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-colors border border-red-200 shadow-sm">
                                                <i class="fas fa-trash-alt mr-1.5"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12 bg-slate-50 rounded-2xl border-2 border-slate-200 border-dashed">
                        <div class="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-3 border border-slate-200 text-slate-400">
                            <i class="fas fa-file-medical text-2xl text-cyan-600"></i>
                        </div>
                        <h3 class="text-sm font-black text-slate-800 uppercase">Sin Instructivo Maestro Configurado</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Para iniciar la producción de este producto bajo protocolo EBR, defina primero los procesos, tiempos y parámetros en un instructivo maestro.</p>
                        <a href="{{ route('productos.instructivo.edit', $product['id']) }}"
                           class="mt-4 inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-cyan-500 to-aurofarma shadow-3d-button hover:shadow-3d-cyan transition-all">
                            <i class="fas fa-plus-circle mr-2"></i> Comenzar Configuración EBR
                        </a>
                    </div>
                @endif
            </div>

            <!-- =============================================================== -->
            <!-- PESTAÑA 3: HISTORIAL DE LOTES                                   -->
            <!-- =============================================================== -->
            <div id="content-produccion" class="hidden text-center py-12 text-slate-400">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-2 text-sm font-black text-slate-800 uppercase">Historial de Lotes</h3>
                <p class="text-xs text-slate-500 mt-1">Consulte los registros históricos de órdenes cerradas para esta fórmula.</p>
            </div>

            <!-- =============================================================== -->
            <!-- PESTAÑA 4: CONTROL DE CALIDAD (COAS)                            -->
            <!-- =============================================================== -->
            <div id="content-calidad" class="hidden text-center py-12 text-slate-400">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-black text-slate-800 uppercase">Historial Analítico & COAs</h3>
                <p class="text-xs text-slate-500 mt-1">Módulo de trazabilidad y liberación analítica por control de calidad.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const catalogItemsList = @json($all_items ?? []);

    function formulaMaestraBuilder() {
        const initRaw = @json($initialRawMaterials);
        const initPkg = @json($initialPackaging);

        return {
            showFormulaEditor: false,
            rawMaterials: initRaw.length > 0 ? initRaw : [
                { uid: 'rm_init_1', code: '', name: '', function: 'PRINCIPIO ACTIVO', unit: 'KIL', percentage: '' }
            ],
            packagingMaterials: initPkg.length > 0 ? initPkg : [
                { uid: 'pk_init_1', code: '', name: '', type: 'MATERIAL DE EMPAQUE', classification: 'FRASCO', unit: 'UND', percentage: '' }
            ],

            addRawMaterial() {
                this.rawMaterials.push({
                    uid: 'rm_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6),
                    code: '',
                    name: '',
                    function: 'EXCIPIENTE',
                    unit: 'KIL',
                    percentage: ''
                });
            },

            addPackagingMaterial() {
                this.packagingMaterials.push({
                    uid: 'pk_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6),
                    code: '',
                    name: '',
                    type: 'MATERIAL DE EMPAQUE',
                    classification: 'ETIQUETA',
                    unit: 'UND',
                    percentage: ''
                });
            },

            async lookupItemCode(rowObj, mode) {
                const code = String(rowObj.code || '').trim().toUpperCase();
                if (!code) return;

                const found = catalogItemsList.find(i => String(i.codigo || '').trim().toUpperCase() === code);
                if (found) {
                    rowObj.name = found.nombre;
                    if (found.unidad) rowObj.unit = found.unidad;
                    return;
                }

                try {
                    const resp = await fetch(`/api/items/${encodeURIComponent(code)}`);
                    if (resp.ok) {
                        const data = await resp.json();
                        if (data.success) {
                            rowObj.name = data.description || data.name || '';
                            if (data.unit) rowObj.unit = data.unit;
                        }
                    }
                } catch (e) {}
            }
        };
    }

    function switchTab(tabName) {
        document.getElementById('content-formula').classList.add('hidden');
        document.getElementById('content-instructivo').classList.add('hidden');
        document.getElementById('content-produccion').classList.add('hidden');
        document.getElementById('content-calidad').classList.add('hidden');

        const tabs = ['formula', 'instructivo', 'produccion', 'calidad'];
        tabs.forEach(t => {
            const el = document.getElementById('tab-' + t);
            el.classList.remove('border-cyan-500', 'text-cyan-700');
            el.classList.add('border-transparent', 'text-slate-500');
        });

        document.getElementById('content-' + tabName).classList.remove('hidden');
        const activeEl = document.getElementById('tab-' + tabName);
        activeEl.classList.remove('border-transparent', 'text-slate-500');
        activeEl.classList.add('border-cyan-500', 'text-cyan-700');
    }

    function confirmDeletePlan(planId) {
        Swal.fire({
            title: '¿Confirmar eliminación?',
            text: "Esta acción eliminará el Instructivo Maestro y sus fases configuradas. No se puede revertir.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DE2021',
            cancelButtonColor: '#64748B',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/instructivo/${planId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Eliminado!', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'No se pudo procesar la solicitud', 'error');
                });
            }
        });
    }
</script>
@endpush
@endsection
