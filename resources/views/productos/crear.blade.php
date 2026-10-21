@extends('layouts.app')

@section('header_title', 'Crear Nuevo Producto')

@section('styles')
<style>
    .step-badge-active {
        background: linear-gradient(135deg, #0a192f 0%, #0284c7 100%);
        color: #ffffff;
        box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.4);
    }
    .glass-section {
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow:
            0 18px 38px -12px rgba(15, 23, 42, 0.07),
            0 4px 12px -2px rgba(15, 23, 42, 0.03),
            inset 0 1px 0 0 rgba(255, 255, 255, 1);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .glass-section:hover {
        border-color: rgba(0, 210, 255, 0.35);
        box-shadow:
            0 22px 45px -12px rgba(15, 23, 42, 0.11),
            0 0 20px 0 rgba(0, 210, 255, 0.06);
    }
    .input-3d {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.85rem;
        transition: all 0.2s ease;
        box-shadow: inset 0 2px 4px 0 rgba(15, 23, 42, 0.03);
    }
    .input-3d:focus {
        background: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12), inset 0 1px 2px 0 rgba(15, 23, 42, 0.02);
        outline: none;
    }
    .row-card-3d {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.04), inset 0 1px 0 0 #ffffff;
        transition: all 0.2s ease;
    }
    .row-card-3d:hover {
        border-color: #bae6fd;
        box-shadow: 0 8px 20px -4px rgba(2, 132, 199, 0.1);
    }
    .unit-pill-selector {
        display: inline-flex;
        background: #f1f5f9;
        padding: 0.25rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
    }
    .unit-pill-btn {
        padding: 0.35rem 0.65rem;
        border-radius: 0.55rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        transition: all 0.15s ease;
        color: #64748b;
    }
    .unit-pill-btn.active {
        background: linear-gradient(135deg, #0a192f 0%, #0284c7 100%);
        color: #ffffff;
        box-shadow: 0 3px 8px -2px rgba(2, 132, 199, 0.45);
    }
</style>
@endsection

@section('content')
<div class="py-6" x-data="productCreatorWizard()">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- TOP BREADCRUMB & STEPPER BANNER -->
        <div class="mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-sky-600 mb-1">
                    <a href="{{ route('productos.index') }}" class="hover:text-sky-800 transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-left"></i> Catálogo Maestro
                    </a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-500">Registro Inicial de Producto</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight font-outfit flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-900 via-sky-900 to-cyan-600 text-white inline-flex items-center justify-center shadow-lg shadow-sky-900/20">
                        <i class="fa-solid fa-flask-vial text-lg"></i>
                    </span>
                    Alta de Nuevo Producto Farmacéutico
                </h1>
            </div>

            <!-- Indicador de Flujo en 2 Etapas -->
            <div class="flex items-center gap-3 bg-white/90 backdrop-blur-md px-4 py-3 rounded-2xl border border-slate-200/80 shadow-sm">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg step-badge-active flex items-center justify-center font-black text-xs">1</span>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-wider text-slate-900 leading-tight">Paso 1: Ficha Técnica</p>
                        <p class="text-[10px] font-semibold text-sky-600">Activos, Presentaciones y Maquila</p>
                    </div>
                </div>
                <div class="w-6 h-[2px] bg-slate-200 rounded-full"></div>
                <div class="flex items-center gap-2.5 opacity-60">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 text-slate-500 flex items-center justify-center font-black text-xs">2</span>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 leading-tight">Paso 2: Posterior</p>
                        <p class="text-[10px] text-slate-400">Fórmula Granel y Empaque (BOM)</p>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-rose-50/95 backdrop-blur-md p-5 rounded-2xl border border-rose-200 shadow-lg shadow-rose-500/5">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-rose-900 uppercase tracking-wide">Por favor verifica los siguientes campos:</h3>
                        <ul class="mt-1.5 text-xs font-semibold text-rose-700 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('productos.store') }}" method="POST" id="product_form" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- =================================================================== -->
            <!-- BLOQUE 1: IDENTIFICACIÓN, REGISTRO ICA, FORMA FARMACÉUTICA Y VIDA ÚTIL -->
            <!-- =================================================================== -->
            <div class="glass-section rounded-3xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-slate-900 via-slate-800 to-sky-950 text-white flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-cyan-400/15 border border-cyan-400/30 flex items-center justify-center text-cyan-300">
                            <i class="fa-solid fa-id-card-clip"></i>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-black tracking-wide uppercase font-outfit">1. Identificación y Especificación Regulatoria</h2>
                            <p class="text-[11px] text-slate-300 font-medium">Datos principales del producto, forma farmacéutica libre y tiempo de vida útil</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-400/30">
                        Registro Maestro EBR
                    </span>
                </div>

                <div class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <!-- Nombre del Producto -->
                        <div class="md:col-span-5">
                            <label for="name" class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">
                                Nombre del Producto <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                                    <i class="fa-solid fa-prescription-bottle-medical"></i>
                                </span>
                                <input type="text"
                                       name="name"
                                       id="name"
                                       x-model="productName"
                                       value="{{ old('name') }}"
                                       required
                                       class="input-3d w-full pl-10 pr-4 py-3 text-sm font-bold text-slate-900 placeholder-slate-400 uppercase"
                                       placeholder="Ej. Q-MUTIN MAX 20%">
                            </div>
                        </div>

                        <!-- Registro ICA -->
                        <div class="md:col-span-3">
                            <label for="ica_license" class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">
                                Registro ICA
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                                    <i class="fa-solid fa-certificate"></i>
                                </span>
                                <input type="text"
                                       name="ica_license"
                                       id="ica_license"
                                       x-model="icaLicense"
                                       value="{{ old('ica_license') }}"
                                       class="input-3d w-full pl-10 pr-4 py-3 text-sm font-bold text-slate-900 placeholder-slate-400 uppercase"
                                       placeholder="Ej. 10452-MV">
                            </div>
                        </div>

                        <!-- Tiempo de Vida Útil -->
                        <div class="md:col-span-4">
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">
                                Tiempo de Vida Útil
                            </label>
                            <div class="flex items-stretch gap-2">
                                <div class="relative flex-1">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                                        <i class="fa-solid fa-hourglass-half"></i>
                                    </span>
                                    <input type="number"
                                           min="0"
                                           step="1"
                                           x-model="shelfLifeInput"
                                           @input="syncShelfLife()"
                                           class="input-3d w-full pl-10 pr-3 py-3 text-sm font-black text-slate-900 placeholder-slate-400"
                                           placeholder="Ej. 24">
                                    <!-- Valor real enviado al backend (en meses o según unidad) -->
                                    <input type="hidden" name="vigencia_meses" :value="computedVigenciaMeses">
                                    <input type="hidden" name="vida_util_unidad" :value="shelfLifeUnit">
                                </div>
                                <div class="unit-pill-selector shrink-0 items-center">
                                    <button type="button"
                                            @click="shelfLifeUnit = 'MESES'; syncShelfLife()"
                                            :class="shelfLifeUnit === 'MESES' ? 'active' : ''"
                                            class="unit-pill-btn">
                                        MESES
                                    </button>
                                    <button type="button"
                                            @click="shelfLifeUnit = 'AÑOS'; syncShelfLife()"
                                            :class="shelfLifeUnit === 'AÑOS' ? 'active' : ''"
                                            class="unit-pill-btn">
                                        AÑOS
                                    </button>
                                </div>
                            </div>
                            <p class="mt-1.5 text-[11px] font-semibold text-slate-400" x-show="shelfLifeInput">
                                Equivalencia de estabilidad: <span class="text-sky-700 font-bold" x-text="computedVigenciaMeses + ' meses'"></span>
                            </p>
                        </div>

                        <!-- Forma Farmacéutica (Input de Texto Libre + Sugerencias rápidas al clic) -->
                        <div class="md:col-span-12">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <label for="pharmaceutical_form" class="block text-xs font-black uppercase tracking-wider text-slate-700">
                                    Forma Farmacéutica <span class="text-rose-500">*</span>
                                    <span class="ml-1 text-[10px] font-semibold text-sky-600 normal-case">(Escribe libremente la forma farmacéutica)</span>
                                </label>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase mr-1">Sugerencias rápidas:</span>
                                    <template x-for="suggestion in ['Polvo Oral Soluble', 'Solución Oral', 'Solución Inyectable', 'Premix / Premezcla', 'Suspensión Oral', 'Ungüento Tópico', 'Granulado Dispersable']" :key="suggestion">
                                        <button type="button"
                                                @click="pharmaceuticalForm = suggestion"
                                                class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-600 hover:bg-sky-50 hover:text-sky-700 hover:border-sky-200 transition-colors"
                                                x-text="suggestion"></button>
                                    </template>
                                </div>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                                    <i class="fa-solid fa-capsules"></i>
                                </span>
                                <input type="text"
                                       name="pharmaceutical_form"
                                       id="pharmaceutical_form"
                                       x-model="pharmaceuticalForm"
                                       required
                                       class="input-3d w-full pl-10 pr-4 py-3 text-sm font-bold text-slate-900 placeholder-slate-400"
                                       placeholder="Escribe la forma farmacéutica (Ej. Polvo Oral Soluble, Solución Inyectable, Premix, Emulsión...)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- BLOQUE 2: PRINCIPIOS ACTIVOS Y CONCENTRACIÓN (1 O VARIOS)           -->
            <!-- =================================================================== -->
            <div class="glass-section rounded-3xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-950 via-emerald-900 to-teal-900 text-white flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-400/15 border border-emerald-400/30 flex items-center justify-center text-emerald-300">
                            <i class="fa-solid fa-atom"></i>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-black tracking-wide uppercase font-outfit">2. Principios Activos y Concentración</h2>
                            <p class="text-[11px] text-emerald-200/90 font-medium">Agrega uno o múltiples activos con su respectiva concentración nominal</p>
                        </div>
                    </div>
                    <button type="button"
                            @click="addActiveIngredient()"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-950/30 transition-all hover:-translate-y-0.5 cursor-pointer">
                        <i class="fa-solid fa-plus"></i> Agregar Principio Activo
                    </button>
                </div>

                <div class="p-6 sm:p-8 space-y-4">
                    <template x-for="(act, idx) in activeIngredients" :key="act.uid">
                        <div class="row-card-3d p-4 sm:p-5 flex flex-col md:flex-row items-stretch md:items-center gap-4">
                            <!-- Badge Índice -->
                            <div class="flex items-center gap-3 md:w-auto">
                                <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 border border-emerald-200 font-black text-xs flex items-center justify-center shrink-0"
                                      x-text="'#' + (idx + 1)"></span>
                                <span class="md:hidden text-xs font-black uppercase text-slate-600">Principio Activo</span>
                            </div>

                            <!-- Nombre del Activo -->
                            <div class="flex-1">
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">
                                    Nombre del Principio Activo <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-emerald-600 pointer-events-none">
                                        <i class="fa-solid fa-vial-circle-check text-xs"></i>
                                    </span>
                                    <input type="text"
                                           :name="'active_ingredients[' + idx + '][name]'"
                                           x-model="act.name"
                                           required
                                           class="input-3d w-full pl-9 pr-3 py-2.5 text-sm font-bold text-slate-900 placeholder-slate-400"
                                           placeholder="Ej. Tiamulina Hidrógeno Fumarato / Ivermectina / Florfenicol...">
                                </div>
                            </div>

                            <!-- Concentración del Activo + Unidad -->
                            <div class="w-full md:w-80">
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">
                                    Concentración del Activo
                                </label>
                                <div class="flex items-stretch gap-2">
                                    <input type="number"
                                           step="0.001"
                                           min="0"
                                           :name="'active_ingredients[' + idx + '][concentration]'"
                                           x-model="act.concentration"
                                           class="input-3d w-full px-3.5 py-2.5 text-sm font-black text-emerald-900 placeholder-slate-400 text-right"
                                           placeholder="Ej. 10.50">
                                    <select :name="'active_ingredients[' + idx + '][unit]'"
                                            x-model="act.unit"
                                            class="input-3d px-3 py-2.5 text-xs font-black text-slate-700 bg-slate-100 cursor-pointer shrink-0">
                                        <option value="%">% (p/p o p/v)</option>
                                        <option value="mg/g">mg / g</option>
                                        <option value="mg/mL">mg / mL</option>
                                        <option value="g/100mL">g / 100 mL</option>
                                        <option value="g/kg">g / kg</option>
                                        <option value="UI/mL">UI / mL</option>
                                        <option value="UI/g">UI / g</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Botón Eliminar Activo -->
                            <div class="flex justify-end md:pt-5">
                                <button type="button"
                                        @click="removeActiveIngredient(idx)"
                                        x-show="activeIngredients.length > 1"
                                        class="w-10 h-10 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center transition-colors cursor-pointer"
                                        title="Quitar principio activo">
                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Campo oculto de compatibilidad para el primer activo -->
                    <input type="hidden" name="active_ingredient_concentration" :value="activeIngredients[0]?.concentration || 0">
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- BLOQUE 3: PRESENTACIONES COMERCIALES Y TIPO DE ENVASE               -->
            <!-- =================================================================== -->
            <div class="glass-section rounded-3xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-950 via-indigo-900 to-sky-900 text-white flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-400/15 border border-indigo-400/30 flex items-center justify-center text-indigo-300">
                            <i class="fa-solid fa-boxes-packing"></i>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-black tracking-wide uppercase font-outfit">3. Presentaciones Comerciales y Tipo de Envase</h2>
                            <p class="text-[11px] text-indigo-200/90 font-medium">Define una o varias presentaciones indicando cantidad, unidad (KG, L, UND, ML) y su tipo de envase</p>
                        </div>
                    </div>
                    <button type="button"
                            @click="addPresentation()"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-400 hover:bg-cyan-300 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-indigo-950/30 transition-all hover:-translate-y-0.5 cursor-pointer">
                        <i class="fa-solid fa-plus"></i> Agregar Presentación
                    </button>
                </div>

                <div class="p-6 sm:p-8 space-y-4">
                    <datalist id="container_types_list">
                        <option value="Frasco PEAD Blanco"></option>
                        <option value="Frasco Vidrio Ámbar"></option>
                        <option value="Garrafa PEAD"></option>
                        <option value="Bolsa Metalizada Trilaminada"></option>
                        <option value="Bolsa Kraft Multicapa"></option>
                        <option value="Jeringa Dosificadora PEAD"></option>
                        <option value="Tarro Plástico PEAD"></option>
                        <option value="Sachet Laminado"></option>
                        <option value="Tambor / Cuñete"></option>
                    </datalist>

                    <template x-for="(pres, pIdx) in presentations" :key="pres.uid">
                        <div class="row-card-3d p-4 sm:p-5">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                <!-- Número / Cantidad + Unidad (KG, L, UND, ML) -->
                                <div class="lg:col-span-5">
                                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1.5">
                                        Presentación #<span x-text="pIdx + 1"></span>: Cantidad y Unidad <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="flex items-stretch gap-2">
                                        <div class="relative flex-1">
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   :name="'presentations[' + pIdx + '][quantity]'"
                                                   x-model="pres.quantity"
                                                   required
                                                   class="input-3d w-full px-3.5 py-2.5 text-base font-black text-indigo-950 placeholder-slate-400"
                                                   placeholder="Ej. 500">
                                        </div>

                                        <!-- Selector de Unidad al lado del número: KG, L, UND, ML, G -->
                                        <input type="hidden" :name="'presentations[' + pIdx + '][unit]'" :value="pres.unit">
                                        <div class="unit-pill-selector shrink-0 items-center">
                                            <template x-for="u in ['KG', 'L', 'UND', 'ML', 'G']" :key="u">
                                                <button type="button"
                                                        @click="pres.unit = u"
                                                        :class="pres.unit === u ? 'active' : ''"
                                                        class="unit-pill-btn"
                                                        x-text="u"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tipo de Envase al lado de cada presentación -->
                                <div class="lg:col-span-5">
                                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1.5">
                                        Tipo de Envase Primaria / Empaque <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-indigo-500 pointer-events-none">
                                            <i class="fa-solid fa-bottle-droplet text-xs"></i>
                                        </span>
                                        <input type="text"
                                               list="container_types_list"
                                               :name="'presentations[' + pIdx + '][container_type]'"
                                               x-model="pres.container_type"
                                               required
                                               class="input-3d w-full pl-9 pr-4 py-2.5 text-sm font-bold text-slate-900 placeholder-slate-400"
                                               placeholder="Ej. Frasco PEAD, Bolsa Metalizada, Garrafa, Jeringa...">
                                    </div>
                                </div>

                                <!-- Vista Previa Etiqueta + Eliminar -->
                                <div class="lg:col-span-2 flex items-center justify-between lg:justify-end gap-2 lg:pt-5">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-800 border border-indigo-200/80 text-xs font-black truncate max-w-[160px]"
                                          x-show="pres.quantity || pres.container_type">
                                        <i class="fa-solid fa-tag text-[10px] text-indigo-500"></i>
                                        <span x-text="(pres.quantity ? pres.quantity + ' ' + pres.unit : '') + (pres.container_type ? ' · ' + pres.container_type : '')"></span>
                                    </span>

                                    <button type="button"
                                            @click="removePresentation(pIdx)"
                                            x-show="presentations.length > 1"
                                            class="w-10 h-10 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center transition-colors shrink-0 cursor-pointer"
                                            title="Eliminar presentación">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- BLOQUE 4: MAQUILADOR(ES) Y TAMAÑO DE LOTE MÍNIMO / MÁXIMO (KG / L)  -->
            <!-- =================================================================== -->
            <div class="glass-section rounded-3xl overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-amber-950 via-slate-900 to-amber-900 text-white flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-400/15 border border-amber-400/30 flex items-center justify-center text-amber-300">
                            <i class="fa-solid fa-industry"></i>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-black tracking-wide uppercase font-outfit">4. Maquilador(es) y Tamaño de Lote (Mínimo y Máximo)</h2>
                            <p class="text-[11px] text-amber-200/90 font-medium">Registra uno o varios maquiladores autorizados junto con su capacidad de lote mínimo y máximo en KG o L</p>
                        </div>
                    </div>
                    <button type="button"
                            @click="addMaquilador()"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-amber-950/30 transition-all hover:-translate-y-0.5 cursor-pointer">
                        <i class="fa-solid fa-plus"></i> Agregar Maquilador
                    </button>
                </div>

                <div class="p-6 sm:p-8 space-y-4">
                    <!-- Lista sugerida de maquiladores en BD -->
                    <datalist id="maquiladores_catalog">
                        @if(isset($maquiladoresList))
                            @foreach($maquiladoresList as $mq)
                                <option value="{{ $mq->name }}">{{ $mq->nit ? 'NIT: '.$mq->nit : '' }}</option>
                            @endforeach
                        @endif
                        <option value="AUROFARMA PLANTA PRINCIPAL"></option>
                        <option value="VECOL S.A."></option>
                        <option value="LABORATORIOS EDO"></option>
                        <option value="CARVAL S.A."></option>
                    </datalist>

                    <template x-for="(maq, mIdx) in maquiladores" :key="maq.uid">
                        <div class="row-card-3d p-4 sm:p-5">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                <!-- Nombre del Maquilador -->
                                <div class="lg:col-span-4">
                                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1.5">
                                        Maquilador #<span x-text="mIdx + 1"></span> (Planta Fabricante) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-amber-600 pointer-events-none">
                                            <i class="fa-solid fa-building-shield text-xs"></i>
                                        </span>
                                        <input type="text"
                                               list="maquiladores_catalog"
                                               :name="'maquiladores[' + mIdx + '][name]'"
                                               x-model="maq.name"
                                               required
                                               class="input-3d w-full pl-9 pr-4 py-2.5 text-sm font-bold text-slate-900 placeholder-slate-400 uppercase"
                                               placeholder="Escribe o selecciona el maquilador...">
                                    </div>
                                </div>

                                <!-- Tamaño de Lote Mínimo (Número + KG o L) -->
                                <div class="lg:col-span-3">
                                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1.5">
                                        Tamaño de Lote Mínimo
                                    </label>
                                    <div class="flex items-stretch gap-1.5">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               :name="'maquiladores[' + mIdx + '][min_batch]'"
                                               x-model="maq.min_batch"
                                               class="input-3d w-full px-3 py-2.5 text-sm font-black text-slate-900 placeholder-slate-400"
                                               placeholder="Mín. (Ej. 100)">
                                        <input type="hidden" :name="'maquiladores[' + mIdx + '][min_unit]'" :value="maq.min_unit">
                                        <div class="unit-pill-selector shrink-0 items-center">
                                            <button type="button"
                                                    @click="maq.min_unit = 'KG'"
                                                    :class="maq.min_unit === 'KG' ? 'active' : ''"
                                                    class="unit-pill-btn">KG</button>
                                            <button type="button"
                                                    @click="maq.min_unit = 'L'"
                                                    :class="maq.min_unit === 'L' ? 'active' : ''"
                                                    class="unit-pill-btn">L</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tamaño de Lote Máximo (Número + KG o L) -->
                                <div class="lg:col-span-4">
                                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1.5">
                                        Tamaño de Lote Máximo
                                    </label>
                                    <div class="flex items-stretch gap-1.5">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               :name="'maquiladores[' + mIdx + '][max_batch]'"
                                               x-model="maq.max_batch"
                                               class="input-3d w-full px-3 py-2.5 text-sm font-black text-slate-900 placeholder-slate-400"
                                               placeholder="Máx. (Ej. 2000)">
                                        <input type="hidden" :name="'maquiladores[' + mIdx + '][max_unit]'" :value="maq.max_unit">
                                        <div class="unit-pill-selector shrink-0 items-center">
                                            <button type="button"
                                                    @click="maq.max_unit = 'KG'"
                                                    :class="maq.max_unit === 'KG' ? 'active' : ''"
                                                    class="unit-pill-btn">KG</button>
                                            <button type="button"
                                                    @click="maq.max_unit = 'L'"
                                                    :class="maq.max_unit === 'L' ? 'active' : ''"
                                                    class="unit-pill-btn">L</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón Eliminar Maquilador -->
                                <div class="lg:col-span-1 flex justify-end lg:pt-5">
                                    <button type="button"
                                            @click="removeMaquilador(mIdx)"
                                            x-show="maquiladores.length > 1"
                                            class="w-10 h-10 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center transition-colors cursor-pointer"
                                            title="Eliminar maquilador">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- BLOQUE 5: IMAGEN DEL PRODUCTO (OPCIONAL) Y RESUMEN EN TIEMPO REAL   -->
            <!-- =================================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Subida de Imagen Opcional -->
                <div class="lg:col-span-6 glass-section rounded-3xl p-6 sm:p-7">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center">
                                <i class="fa-solid fa-image"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">5. Imagen del Producto</h3>
                                <p class="text-[11px] text-slate-500 font-medium">Fotografía o render 3D para el catálogo visual</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 border border-slate-200">
                            Campo Opcional
                        </span>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-5">
                        <!-- Preview Box -->
                        <div class="w-28 h-28 rounded-2xl bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden shrink-0 relative">
                            <template x-if="imagePreview">
                                <img :src="imagePreview" alt="Vista previa" class="w-full h-full object-contain p-2 bg-white">
                            </template>
                            <template x-if="!imagePreview">
                                <div class="text-center text-slate-400 p-2">
                                    <i class="fa-solid fa-camera-retro text-2xl mb-1 block"></i>
                                    <span class="text-[9px] font-bold uppercase tracking-wider block">Sin Imagen</span>
                                </div>
                            </template>
                        </div>

                        <div class="flex-1 w-full">
                            <label for="image" class="flex flex-col items-center justify-center w-full px-4 py-4 rounded-2xl border-2 border-dashed border-sky-200 bg-sky-50/40 hover:bg-sky-50 transition-colors cursor-pointer text-center">
                                <i class="fa-solid fa-cloud-arrow-up text-sky-600 text-lg mb-1"></i>
                                <span class="text-xs font-bold text-slate-700">Haz clic para seleccionar imagen (PNG, JPG, WEBP)</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Opcional · Puedes guardarlo sin imagen y subirla después</span>
                                <input type="file"
                                       name="image"
                                       id="image"
                                       accept=".png,.jpg,.jpeg,.webp"
                                       @change="handleImageUpload($event)"
                                       class="hidden">
                            </label>
                            <button type="button"
                                    x-show="imagePreview"
                                    @click="clearImage()"
                                    class="mt-2 text-[11px] font-bold text-rose-600 hover:text-rose-800 flex items-center gap-1">
                                <i class="fa-solid fa-xmark"></i> Quitar imagen seleccionada
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Nota de Flujo Paso 2 (Fórmula Maestra y BOM diferidos) -->
                <div class="lg:col-span-6 glass-section rounded-3xl p-6 sm:p-7 flex flex-col justify-between bg-gradient-to-br from-slate-900 via-slate-900 to-sky-950 text-white border-slate-800">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cyan-500/20 border border-cyan-400/30 text-cyan-300 text-[10px] font-black uppercase tracking-widest">
                                <i class="fa-solid fa-layer-group"></i> Flujo Modular EBR
                            </span>
                            <span class="text-xs font-bold text-slate-400" x-text="presentations.length + ' presentación(es) · ' + activeIngredients.length + ' activo(s)'"></span>
                        </div>
                        <h3 class="text-base font-black text-white font-outfit mb-1.5">
                            ¿Qué sucede después de guardar este producto?
                        </h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            El producto quedará creado de inmediato en el catálogo maestro con sus principios activos, presentaciones, envases, vida útil y maquiladores.
                            La <strong class="text-cyan-300">Sección 1 (Fórmula Maestra a Granel)</strong> y la <strong class="text-cyan-300">Sección 2 (Materiales de Empaque / BOM)</strong> quedan listas para configurarse en un paso posterior desde la ficha del producto.
                        </p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
                        <a href="{{ route('productos.index') }}"
                           class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 text-xs font-bold transition-colors">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="inline-flex items-center gap-2.5 px-7 py-3.5 rounded-2xl bg-gradient-to-r from-cyan-400 via-sky-500 to-blue-600 hover:from-cyan-300 hover:to-blue-500 text-slate-950 font-black text-sm uppercase tracking-wider shadow-xl shadow-cyan-500/25 transition-all hover:-translate-y-0.5 cursor-pointer">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar Producto
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function productCreatorWizard() {
        return {
            productName: @json(old('name', '')),
            icaLicense: @json(old('ica_license', '')),
            pharmaceuticalForm: @json(old('pharmaceutical_form', '')),
            shelfLifeInput: @json(old('vigencia_meses', 24)),
            shelfLifeUnit: @json(old('vida_util_unidad', 'MESES')),
            computedVigenciaMeses: 24,
            imagePreview: null,

            activeIngredients: [
                { uid: Date.now() + '_a1', name: '', concentration: '', unit: '%' }
            ],

            presentations: [
                { uid: Date.now() + '_p1', quantity: '', unit: 'ML', container_type: '' }
            ],

            maquiladores: [
                { uid: Date.now() + '_m1', name: '', min_batch: '', min_unit: 'KG', max_batch: '', max_unit: 'KG' }
            ],

            init() {
                this.syncShelfLife();
            },

            syncShelfLife() {
                const val = parseFloat(this.shelfLifeInput);
                if (isNaN(val) || val < 0) {
                    this.computedVigenciaMeses = '';
                    return;
                }
                if (this.shelfLifeUnit === 'AÑOS') {
                    this.computedVigenciaMeses = Math.round(val * 12);
                } else {
                    this.computedVigenciaMeses = Math.round(val);
                }
            },

            addActiveIngredient() {
                this.activeIngredients.push({
                    uid: Date.now() + '_a_' + Math.random().toString(36).slice(2, 7),
                    name: '',
                    concentration: '',
                    unit: '%'
                });
            },

            removeActiveIngredient(index) {
                if (this.activeIngredients.length > 1) {
                    this.activeIngredients.splice(index, 1);
                }
            },

            addPresentation() {
                const lastUnit = this.presentations.length > 0
                    ? this.presentations[this.presentations.length - 1].unit
                    : 'ML';
                this.presentations.push({
                    uid: Date.now() + '_p_' + Math.random().toString(36).slice(2, 7),
                    quantity: '',
                    unit: lastUnit,
                    container_type: ''
                });
            },

            removePresentation(index) {
                if (this.presentations.length > 1) {
                    this.presentations.splice(index, 1);
                }
            },

            addMaquilador() {
                this.maquiladores.push({
                    uid: Date.now() + '_m_' + Math.random().toString(36).slice(2, 7),
                    name: '',
                    min_batch: '',
                    min_unit: 'KG',
                    max_batch: '',
                    max_unit: 'KG'
                });
            },

            removeMaquilador(index) {
                if (this.maquiladores.length > 1) {
                    this.maquiladores.splice(index, 1);
                }
            },

            handleImageUpload(event) {
                const file = event.target.files[0];
                if (!file) {
                    this.imagePreview = null;
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            },

            clearImage() {
                this.imagePreview = null;
                const fileInput = document.getElementById('image');
                if (fileInput) fileInput.value = '';
            }
        };
    }
</script>
@endpush
