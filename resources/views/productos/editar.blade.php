@extends('layouts.app')

@section('header_title', 'Editar Producto: ' . $producto->name)

@section('content')
@php
    $initialActives = $producto->active_ingredients_list;
    if (empty($initialActives)) {
        $initialActives = [[
            'uid'           => 'act_0',
            'name'          => $producto->active_ingredient ?: '',
            'concentration' => $producto->active_ingredient_concentration ?: '',
            'unit'          => '%',
        ]];
    } else {
        $initialActives = array_values(array_map(function ($a, $i) {
            return [
                'uid'           => 'act_' . $i . '_' . rand(100, 999),
                'name'          => $a['name'] ?? '',
                'concentration' => $a['concentration'] ?? '',
                'unit'          => '%',
            ];
        }, $initialActives, array_keys($initialActives)));
    }

    $unitNormalizer = ['G' => 'g', 'KG' => 'Kg', 'ML' => 'mL', 'L' => 'L'];
    $initialPresentations = [];
    if ($producto->presentations && $producto->presentations->count() > 0) {
        foreach ($producto->presentations as $idx => $pObj) {
            $rawU = strtoupper(trim($pObj->unit_measure ?? 'ML'));
            $normU = $unitNormalizer[$rawU] ?? 'mL';
            $qty = $pObj->quantity_value;
            $cType = $pObj->container_type ?? '';

            if ($qty === null && !empty($pObj->name)) {
                if (preg_match('/^([0-9.]+)\s*(g|kg|ml|l)\b/i', trim($pObj->name), $m)) {
                    $qty = (float) $m[1];
                    $normU = $unitNormalizer[strtoupper($m[2])] ?? 'mL';
                }
                if ($cType === '' && preg_match('/\((.+?)\)/', $pObj->name, $cm)) {
                    $cType = trim($cm[1]);
                } elseif ($cType === '') {
                    $cType = $pObj->name;
                }
            }

            $initialPresentations[] = [
                'uid'            => 'pres_' . $pObj->id,
                'id'             => $pObj->id,
                'quantity'       => $qty !== null ? rtrim(rtrim(number_format((float) $qty, 2, '.', ''), '0'), '.') : '',
                'unit'           => $normU,
                'container_type' => $cType,
            ];
        }
    }
    if (empty($initialPresentations)) {
        $initialPresentations = [[
            'uid'            => 'pres_new_0',
            'id'             => '',
            'quantity'       => '',
            'unit'           => 'mL',
            'container_type' => '',
        ]];
    }

    $initialMaquiladores = $producto->maquiladores_list;
    if (empty($initialMaquiladores)) {
        $initialMaquiladores = [[
            'uid'       => 'maq_0',
            'name'      => $producto->maquilador ?: '',
            'min_batch' => '',
            'min_unit'  => 'Kg',
            'max_batch' => $producto->base_batch_size ?: '',
            'max_unit'  => strtoupper(trim($producto->base_unit ?? 'KG')) === 'L' ? 'L' : 'Kg',
        ]];
    } else {
        $initialMaquiladores = array_values(array_map(function ($m, $i) {
            return [
                'uid'       => 'maq_' . $i . '_' . rand(100, 999),
                'name'      => $m['name'] ?? '',
                'min_batch' => $m['min_batch'] ?? '',
                'min_unit'  => strtoupper(trim($m['min_unit'] ?? 'Kg')) === 'L' ? 'L' : 'Kg',
                'max_batch' => $m['max_batch'] ?? '',
                'max_unit'  => strtoupper(trim($m['max_unit'] ?? 'Kg')) === 'L' ? 'L' : 'Kg',
            ];
        }, $initialMaquiladores, array_keys($initialMaquiladores)));
    }
@endphp

<div class="py-3">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">

        <!-- ENCABEZADO COMPACTO -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
            <div class="flex items-center gap-3">
                <a href="{{ route('productos.show', $producto->id) }}"
                   class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 hover:text-sky-600 hover:border-sky-300 flex items-center justify-center transition-colors"
                   title="Volver a la ficha del producto">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </a>
                <div>
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight font-outfit leading-none uppercase">
                        Editar Producto: {{ $producto->name }}
                    </h1>
                    <p class="text-[11px] font-semibold text-slate-500 mt-0.5">
                        Modifica la información general, principios activos, presentaciones, envases y maquiladores
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <form action="{{ route('productos.destroy', $producto->id) }}" method="POST"
                      onsubmit="return confirm('¿Estás seguro de eliminar este producto y toda su información? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-black uppercase tracking-wider border border-rose-200 transition-colors flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-trash-can"></i> Eliminar
                    </button>
                </form>
                <a href="{{ route('productos.show', $producto->id) }}"
                   class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold border border-slate-200 transition-colors">
                    Volver
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-rose-50 p-3.5 rounded-xl border border-rose-200 text-xs text-rose-800">
                <p class="font-black uppercase mb-1"><i class="fa-solid fa-circle-exclamation mr-1"></i> Verifica los siguientes campos:</p>
                <ul class="list-disc list-inside font-semibold space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('productos.update', $producto->id) }}" method="POST" id="product_form" enctype="multipart/form-data" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="space-y-4"
                 x-data="{
                    activeIngredients: @js($initialActives),
                    fichaPresentations: @js($initialPresentations),
                    maquiladores: @js($initialMaquiladores),
                    addActive() {
                        this.activeIngredients.push({ uid: 'a_' + Date.now() + Math.random(), name: '', concentration: '', unit: '%' });
                    },
                    removeActive(i) {
                        if (this.activeIngredients.length > 1) this.activeIngredients.splice(i, 1);
                    },
                    addPres() {
                        const lastU = this.fichaPresentations.length ? this.fichaPresentations[this.fichaPresentations.length - 1].unit : 'mL';
                        this.fichaPresentations.push({ uid: 'p_' + Date.now() + Math.random(), id: '', quantity: '', unit: lastU, container_type: '' });
                    },
                    removePres(i) {
                        if (this.fichaPresentations.length > 1) this.fichaPresentations.splice(i, 1);
                    },
                    addMaq() {
                        this.maquiladores.push({ uid: 'm_' + Date.now() + Math.random(), name: '', min_batch: '', min_unit: 'Kg', max_batch: '', max_unit: 'Kg' });
                    },
                    removeMaq(i) {
                        if (this.maquiladores.length > 1) this.maquiladores.splice(i, 1);
                    }
                 }">

                <!-- 1. DATOS GENERALES DEL PRODUCTO -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-sky-600 text-white text-xs font-black flex items-center justify-center">1</span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Datos Generales del Producto</h3>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
                        <div class="sm:col-span-3">
                            <label for="name" class="block text-[11px] font-black uppercase text-slate-700 mb-1">Nombre del Producto <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ $producto->name }}" required autocomplete="off" spellcheck="false"
                                   class="w-full px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 border border-slate-300 rounded-xl uppercase focus:bg-white focus:border-sky-600 focus:outline-none">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="ica_license" class="block text-[11px] font-black uppercase text-slate-700 mb-1">Registro ICA</label>
                            <input type="text" name="ica_license" id="ica_license" value="{{ $producto->ica_license }}" autocomplete="off" spellcheck="false"
                                   class="w-full px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 border border-slate-300 rounded-xl uppercase focus:bg-white focus:border-sky-600 focus:outline-none">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="formula_maestra" class="block text-[11px] font-black uppercase text-slate-700 mb-1">Fórmula Maestra</label>
                            <input type="text" name="formula_maestra" id="formula_maestra" value="{{ $producto->formula_maestra }}" autocomplete="off" spellcheck="false"
                                   class="w-full px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 border border-slate-300 rounded-xl uppercase focus:bg-white focus:border-sky-600 focus:outline-none"
                                   placeholder="Ej. FM-0001">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="pharmaceutical_form" class="block text-[11px] font-black uppercase text-slate-700 mb-1">Forma Farmacéutica <span class="text-rose-500">*</span></label>
                            <input type="text" name="pharmaceutical_form" id="pharmaceutical_form" value="{{ $producto->pharmaceutical_form }}" required autocomplete="off" spellcheck="false"
                                   class="w-full px-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:border-sky-600 focus:outline-none"
                                   placeholder="Escribe la forma farmacéutica...">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="vigencia_meses" class="block text-[11px] font-black uppercase text-slate-700 mb-1">Vida Útil</label>
                            <div class="flex flex-row items-stretch">
                                <input type="number" name="vigencia_meses" id="vigencia_meses" value="{{ $producto->vigencia_meses }}" min="0" step="1" autocomplete="off"
                                       class="w-full px-2.5 py-2 text-xs font-black text-slate-900 bg-slate-50 border border-r-0 border-slate-300 rounded-l-xl focus:bg-white focus:border-sky-600 focus:outline-none">
                                <input type="hidden" name="vida_util_unidad" value="MESES">
                                <span class="inline-flex items-center px-2.5 bg-slate-800 text-white text-[10px] font-black uppercase tracking-wider rounded-r-xl shrink-0">
                                    MESES
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. PRINCIPIOS ACTIVOS Y CONCENTRACIÓN (%) -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs font-black flex items-center justify-center">2</span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Principios Activos y Concentración (%)</h3>
                        </div>
                        <button type="button" @click="addActive()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-black text-[11px] uppercase tracking-wider cursor-pointer">
                            <i class="fa-solid fa-plus text-[10px]"></i> Agregar Activo
                        </button>
                    </div>

                    <div class="space-y-2.5">
                        <template x-for="(act, idx) in activeIngredients" :key="act.uid">
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                                <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-800 font-black text-[11px] flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                                <div class="flex-1">
                                    <input type="text" :name="'active_ingredients[' + idx + '][name]'" x-model="act.name" required autocomplete="off" spellcheck="false"
                                           class="w-full px-3 py-1.5 text-xs font-bold text-slate-900 bg-white border border-slate-300 rounded-lg"
                                           placeholder="Nombre del principio activo...">
                                </div>
                                <div class="w-full sm:w-48 flex flex-row items-stretch">
                                    <input type="number" step="0.01" min="0" :name="'active_ingredients[' + idx + '][concentration]'" x-model="act.concentration" autocomplete="off"
                                           class="w-full px-3 py-1.5 text-xs font-black text-emerald-900 bg-white border border-r-0 border-slate-300 rounded-l-lg text-right"
                                           placeholder="Concentración">
                                    <input type="hidden" :name="'active_ingredients[' + idx + '][unit]'" value="%">
                                    <span class="inline-flex items-center justify-center px-3 bg-emerald-700 text-white text-xs font-black rounded-r-lg shrink-0">%</span>
                                </div>
                                <button type="button" @click="removeActive(idx)" x-show="activeIngredients.length > 1"
                                        class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0 cursor-pointer">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                    <input type="hidden" name="active_ingredient_concentration" :value="activeIngredients[0]?.concentration || 0">
                </div>

                <!-- 3. PRESENTACIONES COMERCIALES (BOTONES INTERACTIVOS g, Kg, mL, L) Y TIPO DE ENVASE -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white text-xs font-black flex items-center justify-center">3</span>
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Presentaciones y Tipo de Envase</h3>
                                <p class="text-[10px] font-semibold text-slate-500">Selecciona con un clic <strong class="text-indigo-600">g, Kg, mL o L</strong> en cada presentación</p>
                            </div>
                        </div>
                        <button type="button" @click="addPres()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-black text-[11px] uppercase tracking-wider cursor-pointer">
                            <i class="fa-solid fa-plus text-[10px]"></i> Agregar Presentación
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(pres, pIdx) in fichaPresentations" :key="pres.uid">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center bg-slate-50 p-3 rounded-xl border border-slate-200">
                                <input type="hidden" :name="'ficha_presentations[' + pIdx + '][id]'" :value="pres.id">

                                <div class="md:col-span-3">
                                    <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">
                                        Presentación #<span x-text="pIdx + 1"></span> (Cantidad)
                                    </label>
                                    <input type="number" step="0.01" min="0" :name="'ficha_presentations[' + pIdx + '][quantity]'" x-model="pres.quantity" required autocomplete="off"
                                           class="w-full px-3 py-2 text-xs font-black text-indigo-950 bg-white border border-slate-300 rounded-lg"
                                           placeholder="Ej. 500">
                                </div>

                                <div class="md:col-span-4">
                                    <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">
                                        Unidad (<span class="text-indigo-600" x-text="'Seleccionado: ' + pres.unit"></span>)
                                    </label>
                                    <input type="hidden" :name="'ficha_presentations[' + pIdx + '][unit]'" :value="pres.unit">
                                    <div class="inline-flex flex-row items-center gap-1.5 bg-slate-200/80 p-1 rounded-xl border border-slate-300 w-full justify-between">
                                        <button type="button" @click="pres.unit = 'g'"
                                                :class="pres.unit === 'g' ? 'bg-indigo-600 text-white border-indigo-700 shadow-md ring-2 ring-indigo-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-indigo-50'"
                                                class="flex-1 py-1.5 px-2.5 rounded-lg text-xs font-black border transition-all cursor-pointer text-center select-none">g</button>
                                        <button type="button" @click="pres.unit = 'Kg'"
                                                :class="pres.unit === 'Kg' ? 'bg-indigo-600 text-white border-indigo-700 shadow-md ring-2 ring-indigo-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-indigo-50'"
                                                class="flex-1 py-1.5 px-2.5 rounded-lg text-xs font-black border transition-all cursor-pointer text-center select-none">Kg</button>
                                        <button type="button" @click="pres.unit = 'mL'"
                                                :class="pres.unit === 'mL' ? 'bg-indigo-600 text-white border-indigo-700 shadow-md ring-2 ring-indigo-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-indigo-50'"
                                                class="flex-1 py-1.5 px-2.5 rounded-lg text-xs font-black border transition-all cursor-pointer text-center select-none">mL</button>
                                        <button type="button" @click="pres.unit = 'L'"
                                                :class="pres.unit === 'L' ? 'bg-indigo-600 text-white border-indigo-700 shadow-md ring-2 ring-indigo-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-indigo-50'"
                                                class="flex-1 py-1.5 px-2.5 rounded-lg text-xs font-black border transition-all cursor-pointer text-center select-none">L</button>
                                    </div>
                                </div>

                                <div class="md:col-span-5 flex items-end gap-2">
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">Tipo de Envase</label>
                                        <input type="text" :name="'ficha_presentations[' + pIdx + '][container_type]'" x-model="pres.container_type" required autocomplete="off" spellcheck="false"
                                               class="w-full px-3 py-2 text-xs font-bold text-slate-900 bg-white border border-slate-300 rounded-lg"
                                               placeholder="Ej. Frasco PEAD, Garrafa, Bolsa...">
                                    </div>
                                    <button type="button" @click="removePres(pIdx)" x-show="fichaPresentations.length > 1"
                                            class="w-9 h-9 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0 cursor-pointer">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 4. MAQUILADOR(ES) Y TAMAÑO DE LOTE MÍNIMO Y MÁXIMO (BOTONES Kg / L) -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-600 text-white text-xs font-black flex items-center justify-center">4</span>
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Maquilador y Tamaño de Lote (Mínimo y Máximo)</h3>
                                <p class="text-[10px] font-semibold text-slate-500">Selecciona con los botones <strong class="text-amber-700">Kg</strong> o <strong class="text-amber-700">L</strong> la unidad del tamaño de lote mínimo y máximo</p>
                            </div>
                        </div>
                        <button type="button" @click="addMaq()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-[11px] uppercase tracking-wider cursor-pointer">
                            <i class="fa-solid fa-plus text-[10px]"></i> Agregar Maquilador
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(maq, mIdx) in maquiladores" :key="maq.uid">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end bg-slate-50 p-3 rounded-xl border border-slate-200">
                                <div class="lg:col-span-4">
                                    <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">Maquilador #<span x-text="mIdx + 1"></span></label>
                                    <input type="text" :name="'maquiladores[' + mIdx + '][name]'" x-model="maq.name" required autocomplete="off" spellcheck="false"
                                           class="w-full px-3 py-2 text-xs font-bold text-slate-900 bg-white border border-slate-300 rounded-lg uppercase"
                                           placeholder="Nombre del maquilador...">
                                </div>

                                <div class="lg:col-span-4">
                                    <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">
                                        Tamaño de Lote Mínimo (<span class="text-amber-700" x-text="maq.min_unit"></span>)
                                    </label>
                                    <div class="flex flex-row items-center gap-1.5">
                                        <input type="number" step="0.01" min="0" :name="'maquiladores[' + mIdx + '][min_batch]'" x-model="maq.min_batch" autocomplete="off"
                                               class="w-full px-3 py-2 text-xs font-black text-slate-900 bg-white border border-slate-300 rounded-lg"
                                               placeholder="Ej. 100">
                                        <input type="hidden" :name="'maquiladores[' + mIdx + '][min_unit]'" :value="maq.min_unit">
                                        <div class="inline-flex flex-row items-center gap-1 bg-slate-200/80 p-1 rounded-xl border border-slate-300 shrink-0">
                                            <button type="button" @click="maq.min_unit = 'Kg'"
                                                    :class="maq.min_unit === 'Kg' ? 'bg-amber-600 text-white border-amber-700 shadow-md ring-2 ring-amber-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50'"
                                                    class="py-1.5 px-3 rounded-lg text-xs font-black border transition-all cursor-pointer select-none">Kg</button>
                                            <button type="button" @click="maq.min_unit = 'L'"
                                                    :class="maq.min_unit === 'L' ? 'bg-amber-600 text-white border-amber-700 shadow-md ring-2 ring-amber-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50'"
                                                    class="py-1.5 px-3 rounded-lg text-xs font-black border transition-all cursor-pointer select-none">L</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="lg:col-span-4 flex items-end gap-2">
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">
                                            Tamaño de Lote Máximo (<span class="text-amber-700" x-text="maq.max_unit"></span>)
                                        </label>
                                        <div class="flex flex-row items-center gap-1.5">
                                            <input type="number" step="0.01" min="0" :name="'maquiladores[' + mIdx + '][max_batch]'" x-model="maq.max_batch" autocomplete="off"
                                                   class="w-full px-3 py-2 text-xs font-black text-slate-900 bg-white border border-slate-300 rounded-lg"
                                                   placeholder="Ej. 2000">
                                            <input type="hidden" :name="'maquiladores[' + mIdx + '][max_unit]'" :value="maq.max_unit">
                                            <div class="inline-flex flex-row items-center gap-1 bg-slate-200/80 p-1 rounded-xl border border-slate-300 shrink-0">
                                                <button type="button" @click="maq.max_unit = 'Kg'"
                                                        :class="maq.max_unit === 'Kg' ? 'bg-amber-600 text-white border-amber-700 shadow-md ring-2 ring-amber-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50'"
                                                        class="py-1.5 px-3 rounded-lg text-xs font-black border transition-all cursor-pointer select-none">Kg</button>
                                                <button type="button" @click="maq.max_unit = 'L'"
                                                        :class="maq.max_unit === 'L' ? 'bg-amber-600 text-white border-amber-700 shadow-md ring-2 ring-amber-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50'"
                                                        class="py-1.5 px-3 rounded-lg text-xs font-black border transition-all cursor-pointer select-none">L</button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" @click="removeMaq(mIdx)" x-show="maquiladores.length > 1"
                                            class="w-9 h-9 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0 cursor-pointer">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 5. IMAGEN DEL PRODUCTO Y BOTÓN GUARDAR -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="w-full sm:w-auto">
                        <label for="image" class="block text-xs font-black uppercase text-slate-800 mb-1">5. Actualizar Imagen del Producto (Opcional)</label>
                        <input type="file" name="image" id="image" accept=".png,.jpg,.jpeg,.webp"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 border border-slate-300 rounded-xl p-1">
                    </div>

                    <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                        <a href="{{ route('productos.show', $producto->id) }}"
                           class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-slate-900 via-sky-800 to-cyan-600 hover:opacity-95 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-sky-900/20 transition-all cursor-pointer">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
