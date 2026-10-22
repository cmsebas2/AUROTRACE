@extends('layouts.app')

@section('header_title', 'Catálogo de Fórmulas y Productos')

@section('content')
@php
    $formsList = collect($products)->pluck('pharmaceutical_form')->filter()->map(fn($f) => strtoupper(trim($f)))->unique()->values()->all();
    $maquiladoresFilterList = collect($products)->pluck('maquilador')->filter()->flatMap(fn($m) => array_map('trim', explode(',', $m)))->filter()->unique()->values()->all();
@endphp

<div class="space-y-5 max-w-7xl mx-auto"
     x-data="{
        search: '',
        selectedForm: 'ALL',
        selectedMaquilador: 'ALL',
        matchesFilter(prod) {
            const q = this.search.trim().toLowerCase();
            const matchSearch = q === '' ||
                (prod.name || '').toLowerCase().includes(q) ||
                (prod.ica || '').toLowerCase().includes(q) ||
                (prod.form || '').toLowerCase().includes(q) ||
                (prod.active || '').toLowerCase().includes(q) ||
                (prod.maquilador || '').toLowerCase().includes(q) ||
                (prod.presentation || '').toLowerCase().includes(q);

            const matchForm = this.selectedForm === 'ALL' ||
                (prod.form || '').toUpperCase() === this.selectedForm;

            const matchMaq = this.selectedMaquilador === 'ALL' ||
                (prod.maquilador || '').toUpperCase().includes(this.selectedMaquilador.toUpperCase());

            return matchSearch && matchForm && matchMaq;
        }
     }">

    <!-- BARRA SUPERIOR DE TÍTULO, BUSCADOR Y FILTROS -->
    <div class="card-3d p-5 border border-slate-200/80 bg-white space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-3 h-7 bg-gradient-to-b from-cyan-500 to-aurofarma rounded-full shadow-3d-cyan"></div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-display text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Catálogo de Productos y Fórmulas</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-900 text-cyan-300">
                            {{ count($products) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium">Especificaciones farmacéuticas, presentaciones, principios activos y registros ICA</p>
                </div>
            </div>

            <!-- Botón Crear Nuevo Producto -->
            <a href="{{ route('productos.create') }}"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-aurofarma text-white text-xs font-black uppercase tracking-wider shadow-3d-button hover:shadow-3d-cyan transform hover:-translate-y-0.5 transition-all shrink-0">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Producto
            </a>
        </div>

        <!-- Barra de Buscador Avanzado y Filtros (Reemplaza barra de especies) -->
        <div class="pt-3 border-t border-slate-100 grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <!-- Buscador general -->
            <div class="md:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text"
                       x-model="search"
                       autocomplete="off"
                       placeholder="Buscar por nombre, registro ICA, principio activo, presentación o maquilador..."
                       class="w-full pl-9 pr-8 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all">
                <button type="button"
                        x-show="search !== ''"
                        @click="search = ''"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Filtro por Forma Farmacéutica -->
            <div class="md:col-span-3">
                <select x-model="selectedForm"
                        class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="ALL">Todas las Formas Farmacéuticas</option>
                    @foreach($formsList as $fItem)
                        <option value="{{ $fItem }}">{{ $fItem }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro por Maquilador -->
            <div class="md:col-span-3 flex items-center gap-2">
                <select x-model="selectedMaquilador"
                        class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="ALL">Todos los Maquiladores</option>
                    @foreach($maquiladoresFilterList as $mItem)
                        <option value="{{ $mItem }}">{{ $mItem }}</option>
                    @endforeach
                </select>

                <button type="button"
                        x-show="search !== '' || selectedForm !== 'ALL' || selectedMaquilador !== 'ALL'"
                        @click="search = ''; selectedForm = 'ALL'; selectedMaquilador = 'ALL'"
                        class="px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-xs font-black shrink-0 transition-colors"
                        title="Limpiar filtros">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- GRILLA DE PRODUCTOS -->
    @if(count($products) === 0)
        <div class="card-3d p-12 text-center bg-white border border-slate-200/80">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-cyan-50 border border-cyan-200 flex items-center justify-center text-cyan-600">
                <i class="fa-solid fa-prescription-bottle-medical text-2xl"></i>
            </div>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-wide">Aún no hay productos registrados</h3>
            <p class="text-xs text-slate-500 mt-1 mb-5">Comienza registrando tu primer producto en el catálogo maestro.</p>
            <a href="{{ route('productos.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-sky-800 text-white text-xs font-black uppercase tracking-wider transition-colors">
                <i class="fa-solid fa-plus"></i> Crear Primer Producto
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
            @foreach($products as $product)
            @php
                $prodJson = [
                    'name'         => $product['name'] ?? '',
                    'ica'          => $product['ica_license'] ?? '',
                    'form'         => strtoupper(trim($product['pharmaceutical_form'] ?? '')),
                    'active'       => $product['active_ingredient'] ?? '',
                    'maquilador'   => $product['maquilador'] ?? '',
                    'presentation' => $product['presentation'] ?? '',
                ];
            @endphp

            <div x-show="matchesFilter(@js($prodJson))"
                 class="card-3d group relative flex flex-col overflow-hidden transition-all duration-300 hover:-translate-y-1.5 hover:shadow-3d-card-hover border border-slate-200/80 bg-white">

                <!-- Etiqueta Superior: Forma Farmacéutica -->
                @if(!empty($product['pharmaceutical_form']))
                <div class="absolute top-3 left-3 z-10 max-w-[75%]">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border shadow-sm bg-cyan-50 text-cyan-800 border-cyan-200 truncate">
                        {{ $product['pharmaceutical_form'] }}
                    </span>
                </div>
                @endif

                <!-- Botón Eliminar Flotante -->
                <form action="{{ route('productos.destroy', $product['id']) }}" method="POST"
                      class="absolute top-3 right-3 z-10 opacity-0 group-hover:opacity-100 transition-opacity"
                      onsubmit="return confirm('¿Confirma la eliminación de este producto?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="bg-white hover:bg-red-50 text-slate-400 hover:text-red-600 p-1.5 rounded-xl shadow-md border border-slate-200 transition-colors"
                            title="Eliminar producto">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </form>

                <a href="{{ route('productos.show', $product['id']) }}" class="flex-1 flex flex-col">
                    <!-- Zona de Imagen -->
                    @php
                        $cardImgSrc = !empty($product['image']) && str_starts_with($product['image'], 'data:')
                            ? $product['image']
                            : asset('img/productos/' . $product['image']);
                    @endphp
                    <div class="h-40 w-full bg-gradient-to-b from-slate-50 to-white flex items-center justify-center p-4 relative overflow-hidden border-b border-slate-100">
                        <img src="{{ $cardImgSrc }}"
                             alt="{{ $product['name'] }}"
                             class="max-h-32 max-w-full object-contain filter drop-shadow-[0_8px_16px_rgba(0,0,0,0.12)] transition-transform duration-500 group-hover:scale-110"
                             onerror="this.onerror=null; this.outerHTML='<div class=\'w-14 h-14 rounded-2xl bg-cyan-50 border border-cyan-200 flex items-center justify-center text-cyan-600\'><svg class=\'w-7 h-7\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z\'></path></svg></div>';">
                    </div>

                    <!-- Zona de Información y Metadata -->
                    <div class="p-4 flex-1 flex flex-col justify-between space-y-2.5">
                        <div>
                            <h3 class="font-display font-black text-slate-800 text-sm tracking-tight leading-snug group-hover:text-cyan-700 transition-colors line-clamp-2">
                                {{ $product['name'] }}
                            </h3>

                            @if(!empty($product['presentation']))
                                <p class="text-[11px] font-semibold text-slate-500 mt-1 line-clamp-1">
                                    {{ $product['presentation'] }}
                                </p>
                            @endif

                            <!-- Licencia ICA y Vida Útil -->
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[9px] font-bold rounded-md uppercase tracking-wider border border-slate-200">
                                    ICA: {{ $product['ica_license'] ?: 'N/A' }}
                                </span>
                                @if(!empty($product['vigencia_meses']))
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[9px] font-bold rounded-md uppercase tracking-wider border border-emerald-200">
                                        {{ $product['vigencia_meses'] }} Meses
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-black text-slate-600 group-hover:text-cyan-700 transition-colors">
                            <span>Ver Ficha Técnica</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
