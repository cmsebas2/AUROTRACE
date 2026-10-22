@extends('layouts.app')

@section('header_title', 'Crear Nuevo Producto')

@section('styles')
<style>
    .compact-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 6px 18px -6px rgba(15, 23, 42, 0.06);
    }
    .input-compact {
        background: #f8fafc;
        border: 1.5px solid #cbd5e1;
        border-radius: 0.65rem;
        transition: all 0.15s ease;
    }
    .input-compact:focus {
        background: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        outline: none;
    }
    .unit-btn-group {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #f1f5f9;
        padding: 0.25rem;
        border-radius: 0.65rem;
        border: 1px solid #cbd5e1;
    }
    .unit-btn {
        min-width: 2.35rem;
        padding: 0.35rem 0.6rem;
        border-radius: 0.45rem;
        font-size: 0.75rem;
        font-weight: 800;
        text-align: center;
        transition: all 0.15s ease;
        color: #475569;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        cursor: pointer;
    }
    .unit-btn:hover {
        border-color: #38bdf8;
        color: #0284c7;
    }
    .unit-btn.active {
        background: linear-gradient(135deg, #0f172a 0%, #0284c7 100%);
        color: #ffffff;
        border-color: transparent;
        box-shadow: 0 2px 6px rgba(2, 132, 199, 0.35);
    }
</style>
@endsection

@section('content')
<div class="py-3" x-data="productCreatorWizard()">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">

        <!-- ENCABEZADO COMPACTO -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('productos.index') }}"
                   class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-sky-600 hover:border-sky-300 flex items-center justify-center shadow-sm transition-colors"
                   title="Volver al catálogo">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </a>
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight font-outfit leading-none">
                        Crear Nuevo Producto
                    </h1>
                    <p class="text-[11px] font-semibold text-slate-500 mt-0.5">
                        Paso 1: Registro inicial en base de datos · Fórmula Maestra y Empaque se configuran después
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-[11px] font-black uppercase tracking-wider bg-slate-900 text-white px-3.5 py-1.5 rounded-xl">
                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                <span>Paso 1 de 2 · Ficha Inicial</span>
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

        <form action="{{ route('productos.store') }}" method="POST" id="product_form" enctype="multipart/form-data" autocomplete="off" class="space-y-4">
            @csrf

            <!-- =================================================================== -->
            <!-- 1. DATOS PRINCIPALES: NOMBRE, ICA, FORMA FARMACÉUTICA Y VIDA ÚTIL   -->
            <!-- =================================================================== -->
            <div class="compact-card p-4 sm:p-5">
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-lg bg-sky-600 text-white text-xs font-black flex items-center justify-center">1</span>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-800">Datos Generales del Producto</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
                    <!-- Nombre del Producto -->
                    <div class="sm:col-span-4">
                        <label for="name" class="block text-[11px] font-black uppercase text-slate-700 mb-1">
                            Nombre del Producto <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               id="name"
                               x-model="productName"
                               value="{{ old('name') }}"
                               required
                               autocomplete="off"
                               autocorrect="off"
                               autocapitalize="characters"
                               spellcheck="false"
                               class="input-compact w-full px-3 py-2 text-xs font-bold text-slate-900 uppercase"
                               placeholder="Ej. Q-MUTIN MAX">
                    </div>

                    <!-- Registro ICA -->
                    <div class="sm:col-span-3">
                        <label for="ica_license" class="block text-[11px] font-black uppercase text-slate-700 mb-1">
                            Registro ICA
                        </label>
                        <input type="text"
                               name="ica_license"
                               id="ica_license"
                               x-model="icaLicense"
                               value="{{ old('ica_license') }}"
                               autocomplete="off"
                               spellcheck="false"
                               class="input-compact w-full px-3 py-2 text-xs font-bold text-slate-900 uppercase"
                               placeholder="Ej. 10452-MV">
                    </div>

                    <!-- Forma Farmacéutica (Texto Libre sin desplegable) -->
                    <div class="sm:col-span-3">
                        <label for="pharmaceutical_form" class="block text-[11px] font-black uppercase text-slate-700 mb-1">
                            Forma Farmacéutica <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="pharmaceutical_form"
                               id="pharmaceutical_form"
                               x-model="pharmaceuticalForm"
                               value="{{ old('pharmaceutical_form') }}"
                               required
                               autocomplete="off"
                               spellcheck="false"
                               class="input-compact w-full px-3 py-2 text-xs font-bold text-slate-900"
                               placeholder="Escribe la forma farmacéutica...">
                    </div>

                    <!-- Tiempo de Vida Útil (Fijo en MESES) -->
                    <div class="sm:col-span-2">
                        <label for="vigencia_meses" class="block text-[11px] font-black uppercase text-slate-700 mb-1">
                            Vida Útil
                        </label>
                        <div class="flex items-stretch">
                            <input type="number"
                                   name="vigencia_meses"
                                   id="vigencia_meses"
                                   min="0"
                                   step="1"
                                   x-model="shelfLifeInput"
                                   autocomplete="off"
                                   class="input-compact w-full px-2.5 py-2 text-xs font-black text-slate-900 rounded-r-none border-r-0"
                                   placeholder="24">
                            <input type="hidden" name="vida_util_unidad" value="MESES">
                            <span class="inline-flex items-center px-2.5 bg-slate-800 text-white text-[10px] font-black uppercase tracking-wider rounded-r-lg">
                                MESES
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- 2. PRINCIPIOS ACTIVOS Y CONCENTRACIÓN (%)                           -->
            <!-- =================================================================== -->
            <div class="compact-card p-4 sm:p-5">
                <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs font-black flex items-center justify-center">2</span>
                        <h2 class="text-xs font-black uppercase tracking-wider text-slate-800">Principios Activos y Concentración (%)</h2>
                    </div>
                    <button type="button"
                            @click="addActiveIngredient()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-black text-[11px] uppercase tracking-wider transition-colors cursor-pointer">
                        <i class="fa-solid fa-plus text-[10px]"></i> Agregar Activo
                    </button>
                </div>

                <div class="space-y-2.5">
                    <template x-for="(act, idx) in activeIngredients" :key="act.uid">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/80">
                            <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-800 font-black text-[11px] flex items-center justify-center shrink-0"
                                  x-text="idx + 1"></span>

                            <!-- Nombre del Activo -->
                            <div class="flex-1">
                                <input type="text"
                                       :name="'active_ingredients[' + idx + '][name]'"
                                       x-model="act.name"
                                       required
                                       autocomplete="off"
                                       spellcheck="false"
                                       class="input-compact w-full px-3 py-1.5 text-xs font-bold text-slate-900 bg-white"
                                       placeholder="Nombre del principio activo...">
                            </div>

                            <!-- Concentración fija en % -->
                            <div class="w-full sm:w-48 flex items-stretch">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="'active_ingredients[' + idx + '][concentration]'"
                                       x-model="act.concentration"
                                       autocomplete="off"
                                       class="input-compact w-full px-3 py-1.5 text-xs font-black text-emerald-900 bg-white text-right rounded-r-none border-r-0"
                                       placeholder="Concentración">
                                <input type="hidden" :name="'active_ingredients[' + idx + '][unit]'" value="%">
                                <span class="inline-flex items-center justify-center px-3 bg-emerald-700 text-white text-xs font-black rounded-r-lg">
                                    %
                                </span>
                            </div>

                            <!-- Eliminar fila -->
                            <button type="button"
                                    @click="removeActiveIngredient(idx)"
                                    x-show="activeIngredients.length > 1"
                                    class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0 cursor-pointer"
                                    title="Quitar activo">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <input type="hidden" name="active_ingredient_concentration" :value="activeIngredients[0]?.concentration || 0">
            </div>

            <!-- =================================================================== -->
            <!-- 3. PRESENTACIONES (g, Kg, mL, L) Y TIPO DE ENVASE                   -->
            <!-- =================================================================== -->
            <div class="compact-card p-4 sm:p-5">
                <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white text-xs font-black flex items-center justify-center">3</span>
                        <h2 class="text-xs font-black uppercase tracking-wider text-slate-800">Presentaciones y Tipo de Envase</h2>
                    </div>
                    <button type="button"
                            @click="addPresentation()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-black text-[11px] uppercase tracking-wider transition-colors cursor-pointer">
                        <i class="fa-solid fa-plus text-[10px]"></i> Agregar Presentación
                    </button>
                </div>

                <div class="space-y-2.5">
                    <template x-for="(pres, pIdx) in presentations" :key="pres.uid">
                        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2.5 bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/80">
                            <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-800 font-black text-[11px] flex items-center justify-center shrink-0"
                                  x-text="pIdx + 1"></span>

                            <!-- Número / Cantidad -->
                            <div class="w-full md:w-36">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="'presentations[' + pIdx + '][quantity]'"
                                       x-model="pres.quantity"
                                       required
                                       autocomplete="off"
                                       class="input-compact w-full px-3 py-1.5 text-xs font-black text-indigo-950 bg-white"
                                       placeholder="Cantidad (Ej. 500)">
                            </div>

                            <!-- Botones para elegir g, Kg, mL, L -->
                            <div class="shrink-0">
                                <input type="hidden" :name="'presentations[' + pIdx + '][unit]'" :value="pres.unit">
                                <div class="unit-btn-group">
                                    <button type="button"
                                            @click="pres.unit = 'g'"
                                            :class="pres.unit === 'g' ? 'active' : ''"
                                            class="unit-btn">g</button>
                                    <button type="button"
                                            @click="pres.unit = 'Kg'"
                                            :class="pres.unit === 'Kg' ? 'active' : ''"
                                            class="unit-btn">Kg</button>
                                    <button type="button"
                                            @click="pres.unit = 'mL'"
                                            :class="pres.unit === 'mL' ? 'active' : ''"
                                            class="unit-btn">mL</button>
                                    <button type="button"
                                            @click="pres.unit = 'L'"
                                            :class="pres.unit === 'L' ? 'active' : ''"
                                            class="unit-btn">L</button>
                                </div>
                            </div>

                            <!-- Tipo de Envase (Sin desplegable de sugerencias) -->
                            <div class="flex-1">
                                <input type="text"
                                       :name="'presentations[' + pIdx + '][container_type]'"
                                       x-model="pres.container_type"
                                       required
                                       autocomplete="off"
                                       spellcheck="false"
                                       class="input-compact w-full px-3 py-1.5 text-xs font-bold text-slate-900 bg-white"
                                       placeholder="Tipo de envase (Ej. Frasco PEAD, Garrafa, Bolsa...)">
                            </div>

                            <!-- Eliminar presentación -->
                            <button type="button"
                                    @click="removePresentation(pIdx)"
                                    x-show="presentations.length > 1"
                                    class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0 cursor-pointer"
                                    title="Quitar presentación">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- 4. MAQUILADOR(ES) Y TAMAÑO DE LOTE MÍNIMO Y MÁXIMO (Kg / L)         -->
            <!-- =================================================================== -->
            <div class="compact-card p-4 sm:p-5">
                <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-amber-600 text-white text-xs font-black flex items-center justify-center">4</span>
                        <h2 class="text-xs font-black uppercase tracking-wider text-slate-800">Maquilador y Tamaño de Lote (Mínimo y Máximo)</h2>
                    </div>
                    <button type="button"
                            @click="addMaquilador()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-[11px] uppercase tracking-wider transition-colors cursor-pointer">
                        <i class="fa-solid fa-plus text-[10px]"></i> Agregar Maquilador
                    </button>
                </div>

                <div class="space-y-2.5">
                    <template x-for="(maq, mIdx) in maquiladores" :key="maq.uid">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 items-center bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/80">
                            <!-- Nombre del Maquilador (Texto libre sin lista desplegable) -->
                            <div class="lg:col-span-4 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-900 font-black text-[11px] flex items-center justify-center shrink-0"
                                      x-text="mIdx + 1"></span>
                                <input type="text"
                                       :name="'maquiladores[' + mIdx + '][name]'"
                                       x-model="maq.name"
                                       required
                                       autocomplete="off"
                                       spellcheck="false"
                                       class="input-compact w-full px-3 py-1.5 text-xs font-bold text-slate-900 bg-white uppercase"
                                       placeholder="Nombre del maquilador...">
                            </div>

                            <!-- Lote Mínimo (Número + Botones Kg / L) -->
                            <div class="lg:col-span-4 flex items-center gap-1.5">
                                <span class="text-[10px] font-black uppercase text-slate-500 shrink-0">Lote Mín:</span>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="'maquiladores[' + mIdx + '][min_batch]'"
                                       x-model="maq.min_batch"
                                       autocomplete="off"
                                       class="input-compact w-full px-2.5 py-1.5 text-xs font-black text-slate-900 bg-white"
                                       placeholder="Mínimo">
                                <input type="hidden" :name="'maquiladores[' + mIdx + '][min_unit]'" :value="maq.min_unit">
                                <div class="unit-btn-group shrink-0">
                                    <button type="button"
                                            @click="maq.min_unit = 'Kg'"
                                            :class="maq.min_unit === 'Kg' ? 'active' : ''"
                                            class="unit-btn">Kg</button>
                                    <button type="button"
                                            @click="maq.min_unit = 'L'"
                                            :class="maq.min_unit === 'L' ? 'active' : ''"
                                            class="unit-btn">L</button>
                                </div>
                            </div>

                            <!-- Lote Máximo (Número + Botones Kg / L) + Eliminar -->
                            <div class="lg:col-span-4 flex items-center gap-1.5">
                                <span class="text-[10px] font-black uppercase text-slate-500 shrink-0">Lote Máx:</span>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="'maquiladores[' + mIdx + '][max_batch]'"
                                       x-model="maq.max_batch"
                                       autocomplete="off"
                                       class="input-compact w-full px-2.5 py-1.5 text-xs font-black text-slate-900 bg-white"
                                       placeholder="Máximo">
                                <input type="hidden" :name="'maquiladores[' + mIdx + '][max_unit]'" :value="maq.max_unit">
                                <div class="unit-btn-group shrink-0">
                                    <button type="button"
                                            @click="maq.max_unit = 'Kg'"
                                            :class="maq.max_unit === 'Kg' ? 'active' : ''"
                                            class="unit-btn">Kg</button>
                                    <button type="button"
                                            @click="maq.max_unit = 'L'"
                                            :class="maq.max_unit === 'L' ? 'active' : ''"
                                            class="unit-btn">L</button>
                                </div>

                                <button type="button"
                                        @click="removeMaquilador(mIdx)"
                                        x-show="maquiladores.length > 1"
                                        class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0 cursor-pointer"
                                        title="Quitar maquilador">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- =================================================================== -->
            <!-- 5. IMAGEN OPCIONAL Y BOTÓN GUARDAR PRODUCTO                         -->
            <!-- =================================================================== -->
            <div class="compact-card p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <!-- Imagen Opcional Compacta -->
                <div class="flex items-center gap-3.5 w-full sm:w-auto">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 border border-dashed border-slate-300 flex items-center justify-center overflow-hidden shrink-0">
                        <template x-if="imagePreview">
                            <img :src="imagePreview" alt="Preview" class="w-full h-full object-contain p-1 bg-white">
                        </template>
                        <template x-if="!imagePreview">
                            <i class="fa-solid fa-image text-slate-400 text-lg"></i>
                        </template>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black uppercase text-slate-800">5. Imagen del Producto</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-500">Opcional</span>
                        </div>
                        <div class="mt-1 flex items-center gap-2">
                            <label for="image" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 text-xs font-bold cursor-pointer transition-colors">
                                <i class="fa-solid fa-upload text-[10px]"></i> Seleccionar imagen
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
                                    class="text-xs font-bold text-rose-600 hover:underline">
                                Quitar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                    <a href="{{ route('productos.index') }}"
                       class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-slate-900 via-sky-800 to-cyan-600 hover:opacity-95 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-sky-900/20 transition-all cursor-pointer">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar Producto
                    </button>
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
            imagePreview: null,

            activeIngredients: [
                { uid: Date.now() + '_a1', name: '', concentration: '', unit: '%' }
            ],

            presentations: [
                { uid: Date.now() + '_p1', quantity: '', unit: 'mL', container_type: '' }
            ],

            maquiladores: [
                { uid: Date.now() + '_m1', name: '', min_batch: '', min_unit: 'Kg', max_batch: '', max_unit: 'Kg' }
            ],

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
                    : 'mL';
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
                    min_unit: 'Kg',
                    max_batch: '',
                    max_unit: 'Kg'
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
