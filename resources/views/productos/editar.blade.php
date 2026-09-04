@extends('layouts.app')

@section('header_title', 'Editar Producto: ' . $producto->name)

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Header Actions -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    Editar Producto: {{ $producto->name }}
                </h2>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este producto y TODA su fórmula? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 text-sm font-bold py-2 px-4 rounded-lg shadow-sm border border-red-300 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Eliminar Producto
                    </button>
                </form>
                <a href="{{ route('productos.show', $producto->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold py-2 px-4 rounded-lg shadow-sm border border-gray-300 transition-colors flex items-center">
                    Volver
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-6 rounded-xl border-red-200 shadow-sm p-4" role="alert" style="background-color: #fef2f2;">
                <div class="flex items-start">
                    <div class="flex-shrink-0 pt-0.5">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <strong class="text-sm font-bold text-red-800">Hay errores con tu solicitud:</strong>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="absolute top-4 right-4 text-red-400 hover:text-red-600 transition-colors" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true" class="text-xl font-bold">&times;</span>
                </button>
            </div>
        @endif

        <form action="{{ route('productos.update', $producto->id) }}" method="POST" id="product_form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- SECCIÓN A: DATOS GENERALES -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl mb-6 border border-gray-100">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-aurofarma-blue flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        DATOS GENERALES DEL PRODUCTO
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6">
                        
                        <div class="lg:col-span-3">
                            <label for="name" class="block text-sm font-bold text-gray-700 mb-1">Nombre Base del Producto <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ $producto->name }}" required class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm" placeholder="Ej. Q-MUTIN MAX">
                        </div>

                        <!-- Registro ICA, Fórmula Maestra y Vigencia -->
                        <div class="lg:col-span-2">
                            <label for="ica_license" class="block text-sm font-bold text-gray-700 mb-1">Registro ICA</label>
                            <input type="text" name="ica_license" id="ica_license" value="{{ $producto->ica_license }}" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm" placeholder="Ej. 12345-AL">
                        </div>

                        <div class="lg:col-span-2">
                            <label for="formula_maestra" class="block text-sm font-bold text-gray-700 mb-1">Fórmula Maestra</label>
                            <input type="text" name="formula_maestra" id="formula_maestra" value="{{ $producto->formula_maestra }}" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm" placeholder="Ej. FM-0000">
                        </div>

                        <div class="lg:col-span-1">
                            <label for="vigencia_meses" class="block text-sm font-bold text-gray-700 mb-1">Vigencia (Meses)</label>
                            <input type="number" name="vigencia_meses" id="vigencia_meses" value="{{ $producto->vigencia_meses }}" min="0" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm" placeholder="24">
                        </div>

                        <!-- Forma Farmacéutica e Imagen -->
                        <div class="lg:col-span-3">
                            <label for="pharmaceutical_form" class="block text-sm font-bold text-gray-700 mb-1">Forma Farmacéutica <span class="text-red-500">*</span></label>
                            <select name="pharmaceutical_form" id="pharmaceutical_form" required class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm">
                                <option value="">Seleccione una forma...</option>
                                @foreach(['Polvo Oral / Soluble', 'Solución Inyectable', 'Solución Oral', 'Premix', 'Ungüento', 'Líquido'] as $forma)
                                    <option value="{{ $forma }}" {{ $producto->pharmaceutical_form == $forma ? 'selected' : '' }}>{{ $forma }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lg:col-span-3">
                            <label for="active_ingredient_concentration" class="block text-sm font-bold text-gray-700 mb-1">Concentración del Principio Activo (%)</label>
                            <div class="relative rounded-lg shadow-sm">
                                <input type="number" step="0.01" name="active_ingredient_concentration" id="active_ingredient_concentration" value="{{ $producto->active_ingredient_concentration }}" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg" placeholder="Ej. 10.50">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-3">
                            <label for="image" class="block text-sm font-bold text-gray-700 mb-1">Actualizar Imagen (Opcional)</label>
                            <input type="file" name="image" id="image" accept=".png,.jpg,.jpeg" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-aurofarma-blue file:text-white hover:file:bg-blue-700 transition cursor-pointer border border-gray-300 rounded-lg shadow-sm">
                            @if($producto->image)
                                <p class="text-xs text-gray-500 mt-1">Imagen actual: {{ $producto->image }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div> <!-- Cierre Sección A -->

            <!-- SECCIÓN B: FÓRMULA Y PRESENTACIONES (COLAPSABLE) -->
            <div class="mb-6">
                <button type="button" onclick="toggleSection('formula-section')" class="w-full bg-white border border-gray-200 rounded-xl p-4 flex justify-between items-center shadow-sm hover:bg-gray-50 transition-all group">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        </div>
                        <div class="text-left">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Fórmula y Presentaciones Comerciales</h3>
                            <p class="text-xs text-slate-500 font-medium">Configure ingredientes primarios, excipientes y sus empaques.</p>
                        </div>
                    </div>
                    <svg id="formula-section-icon" class="w-6 h-6 text-slate-400 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <div id="formula-section" class="hidden mt-4 space-y-6">
                    <!-- SECCIÓN 1: FÓRMULA MAESTRA A GRANEL -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                        <div class="px-6 py-5 border-b border-gray-100 bg-emerald-50 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-emerald-800 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                SECCIÓN 1: FÓRMULA MAESTRA A GRANEL
                            </h3>
                            <span class="bg-emerald-200 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
                                Base de cálculo: 10,000 Unidades / KG
                            </span>
                        </div>
                        
                        <div class="p-6">
                            <div class="flex justify-between items-end mb-3">
                                <p class="text-sm text-gray-600">Materiales primarios y excipientes que conforman la mezcla universal.</p>
                                <button type="button" onclick="addRawMaterialRow()" class="text-sm bg-emerald-100 hover:bg-emerald-200 text-emerald-800 py-2 px-4 rounded-lg font-bold transition-colors flex items-center shadow-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Agregar Materia Prima
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                                <table class="min-w-full divide-y divide-gray-200" id="raw_materials_table">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">Código</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5">Nombre</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">Función</th>
                                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12">U.M</th>
                                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40 min-w-[160px]">%</th>
                                            <th scope="col" class="px-4 py-3 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="raw_materials_body">
                                        @foreach($producto->ingredients->where('material_type', 'MATERIA PRIMA') as $index => $rm)
                                            <tr class="hover:bg-emerald-50 transition-colors">
                                                <td class="px-4 py-2">
                                                    <input type="text" name="raw_materials[{{ $rm->id }}][code]" value="{{ $rm->material_code }}" required class="w-full py-1.5 px-2 text-sm border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500 font-mono" placeholder="Cód..." onblur="fetchItemName(this.value, this.closest('tr').querySelector('.rm-name'))">
                                                </td>
                                                <td class="px-4 py-2 relative">
                                                    <input type="text" name="raw_materials[{{ $rm->id }}][name]" value="{{ $rm->material_name }}" required readonly class="rm-name w-full py-1.5 px-2 text-sm border-gray-200 rounded bg-gray-100 text-gray-700 cursor-not-allowed" placeholder="Esperando código...">
                                                </td>
                                                <td class="px-4 py-2">
                                                    <input type="text" name="raw_materials[{{ $rm->id }}][function]" value="{{ $rm->function }}" class="w-full py-1.5 px-2 text-sm border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500" placeholder="Ej. API">
                                                </td>
                                                <td class="px-4 py-2">
                                                    <select name="raw_materials[{{ $rm->id }}][unit]" class="w-full py-1.5 px-2 text-sm border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500 font-medium">
                                                        <option value="KIL" {{ $rm->unit === 'KIL' ? 'selected' : '' }}>KIL</option>
                                                        <option value="LIT" {{ $rm->unit === 'LIT' ? 'selected' : '' }}>LIT</option>
                                                        <option value="UND" {{ $rm->unit === 'UND' ? 'selected' : '' }}>UND</option>
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number" step="0.01" name="raw_materials[{{ $rm->id }}][percentage]" 
                                                        value="{{ number_format($rm->percentage, 2, '.', '') }}" required 
                                                        class="w-full text-center text-2xl font-black text-blue-900 bg-blue-50 border-2 border-blue-400 rounded-lg p-3 shadow-inner focus:ring-4 focus:ring-blue-300 focus:border-blue-600 transition-all" 
                                                        style="min-width: 140px;" placeholder="0.00">
                                                </td>
                                                <td class="px-4 py-2 text-center">
                                                    <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50" title="Eliminar fila">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: PRESENTACIONES COMERCIALES -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                        <div class="px-6 py-5 border-b border-gray-100 bg-indigo-50 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-indigo-800 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                SECCIÓN 2: PRESENTACIONES Y EMPAQUE
                            </h3>
                            <button type="button" onclick="addPresentationBlock()" class="text-sm bg-indigo-100 hover:bg-indigo-200 text-indigo-800 py-2 px-4 rounded-lg font-bold transition-colors shadow-sm">
                                + Nueva Presentación (SKU)
                            </button>
                        </div>
                        <div class="p-6" id="presentations_container">
                            @if($producto->presentations->isEmpty())
                                <p id="empty_presentations_msg" class="text-center py-10 text-gray-400 italic">No hay presentaciones configuradas para este producto.</p>
                            @endif

                            @foreach($producto->presentations as $pIndex => $presentation)
                                <div class="bg-white border border-indigo-100 shadow-md rounded-xl p-6 mb-6 relative overflow-hidden">
                                    <div class="absolute top-0 left-0 w-2 h-full bg-indigo-500"></div>
                                    
                                    <div class="flex justify-between items-start mb-4 pl-4 grid grid-cols-12 gap-4">
                                        <div class="col-span-4">
                                            <label class="block text-sm font-bold text-indigo-900 mb-1">Código SKU (Ítem) <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <input type="text" name="presentations[{{ $presentation->id }}][presentation_code]" value="{{ $presentation->presentation_code }}" required 
                                                       class="pres-sku-input focus:ring-indigo-500 focus:border-indigo-500 block w-full py-2 px-3 text-sm font-mono font-black border-indigo-200 rounded-lg shadow-sm text-indigo-900 uppercase" 
                                                       placeholder="Ej. 10001"
                                                       oninput="autocompletarPresentacionDesdeSku(this)"
                                                       onblur="autocompletarPresentacionDesdeSku(this)"
                                                       onchange="autocompletarPresentacionDesdeSku(this)"
                                                       onpaste="setTimeout(() => autocompletarPresentacionDesdeSku(this), 50)">
                                                <span class="pres-spinner hidden absolute right-2.5 top-2.5 text-indigo-600">
                                                    <i class="fas fa-spinner fa-spin text-xs"></i>
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-gray-500 mt-1 block">
                                                <i class="fas fa-search text-[9px] text-indigo-500 mr-0.5"></i> Busca en <code>items</code> y autocompleta la presentación
                                            </span>
                                        </div>
                                        <div class="col-span-6">
                                            <label class="block text-sm font-bold text-indigo-900 mb-1">Nombre de la Presentación <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <input type="text" name="presentations[{{ $presentation->id }}][name]" value="{{ $presentation->name }}" required 
                                                       class="pres-name-input focus:ring-indigo-500 focus:border-indigo-500 block w-full py-2 px-3 sm:text-base font-bold border-indigo-200 rounded-lg shadow-sm text-indigo-900 placeholder-indigo-300 transition-all" 
                                                       placeholder="Ej. FRASCO X 1000 ML o SACO X 25 KG">
                                                <span class="pres-check {{ !empty($presentation->name) ? '' : 'hidden' }} absolute right-2.5 top-2.5 text-emerald-500 text-sm" title="Presentación confirmada">
                                                    <i class="fas fa-check-circle"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-span-2 flex justify-end items-end pb-1">
                                            <button type="button" onclick="this.closest('.bg-white').remove()" class="text-red-500 hover:text-red-700 text-sm font-medium flex items-center p-2 rounded hover:bg-red-50 transition-colors" title="Eliminar Presentación">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="pl-4 mt-6">
                                        <div class="flex justify-between items-end mb-3">
                                            <h5 class="font-bold text-gray-700 text-sm uppercase tracking-wide">Materiales de Envase y Empaque</h5>
                                            <button type="button" onclick="addPackagingRowWithCounter('{{ $presentation->id }}', this)" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 py-1.5 px-3 rounded font-bold transition-colors flex items-center border border-indigo-200">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Agregar Material
                                            </button>
                                        </div>
                                        
                                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Código</th>
                                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre del Material</th>
                                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Tipo Material</th>
                                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">MATERIAL</th>
                                                        <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 80px;">U.M</th>
                                                        <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-48">PORCENTAJE (%)</th>
                                                        <th scope="col" class="px-4 py-2 w-10"></th>
                                                    </tr>
                                                </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200 pkg-tbody">
                                                        {{-- Esto nos dirá qué tiene adentro la presentación para depurar --}}
                                                        @if(auth()->user()->id == 1) @endif

                                                        @foreach($presentation->packaging_materials as $index => $material)
                                                            <tr class="material-row hover:bg-indigo-50 transition-colors">
                                                                <td class="px-4 py-2">
                                                                    {{-- ID y Item ID para evitar pérdida de datos (SOLUCIÓN DEFINITIVA) --}}
                                                                    <input type="hidden" name="presentations[{{ $presentation->id }}][packaging][{{ $index }}][id]" value="{{ $material->id }}">
                                                                    <input type="hidden" name="presentations[{{ $presentation->id }}][packaging][{{ $index }}][item_id]" value="{{ $material->item->id ?? '' }}">
                                                                    
                                                                    <input type="text" class="form-control input-codigo-material w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 font-mono" 
                                                                        value="{{ $material->item->codigo ?? '' }}" placeholder="Código"
                                                                        oninput="buscarMaterialPorCodigo(this.value, this.closest('tr'))">
                                                                </td>
                                                                <td class="px-4 py-2">
                                                                    <input type="text" class="form-control pkg-name w-full py-1 px-2 text-xs border-gray-200 rounded bg-gray-100 text-gray-700 cursor-not-allowed" 
                                                                        value="{{ $material->item->nombre ?? 'Busque un código...' }}" readonly>
                                                                </td>
                                                                <td class="px-4 py-2">
                                                                    <input type="text" name="presentations[{{ $presentation->id }}][packaging][{{ $index }}][type]" 
                                                                        value="{{ $material->material_type ?? 'MATERIAL DE EMPAQUE' }}" required
                                                                        class="w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ej. Frasco">
                                                                </td>
                                                                <td class="px-4 py-2">
                                                                    <select name="presentations[{{ $presentation->id }}][packaging][{{ $index }}][material]" required class="w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 font-bold bg-amber-50">
                                                                        @foreach(['N.A.', 'ETIQUETA', 'PLEGADIZA', 'SOBRE', 'JERINGA', 'COLLARIN', 'GARRAFA'] as $opt)
                                                                            <option value="{{ $opt }}" {{ ($material->specific_material_type ?? 'N.A.') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="px-4 py-2 text-xs text-center font-bold text-gray-700" style="min-width: 80px;">{{ $material->item->unidad_medida ?? 'UND' }}</td>
                                                                <td class="px-4 py-3">
                                                                    <input type="number" step="0.01" 
                                                                        name="presentations[{{ $presentation->id }}][packaging][{{ $index }}][percentage]" 
                                                                        value="{{ $material->percentage ?? $material->cantidad ?? 0 }}"
                                                                        class="form-control w-full text-center text-xl font-bold text-blue-900 bg-blue-50 border-2 border-blue-400 rounded-lg p-2 shadow-inner focus:ring-4 focus:ring-blue-300 focus:border-blue-600 transition-all font-mono" 
                                                                        style="min-width: 140px;">
                                                                </td>
                                                                <td class="px-2 py-2 text-center">
                                                                    <button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-1 rounded-full hover:bg-red-50" title="Eliminar empaque">
                                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN C: CONFIGURACIÓN EBR -->
            <div class="bg-white overflow-hidden shadow-2xl sm:rounded-2xl border-2 border-slate-900 mb-8 p-12 text-center bg-gradient-to-br from-white to-slate-50">
                <div class="mx-auto w-24 h-24 bg-slate-900 rounded-2xl flex items-center justify-center mb-6 shadow-xl transform -rotate-3">
                    <svg class="w-12 h-12 text-aurofarma-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                
                <h2 class="text-3xl font-black text-slate-900 uppercase tracking-tighter mb-4">Configurador Master EBR (IF-304-1)</h2>
                <p class="text-slate-600 max-w-2xl mx-auto mb-10 text-lg font-medium italic">
                    Construya la hoja de ruta dinámica para la fabricación de este producto. Gestione pasos de carga, mezcla, tamizado y controles en proceso con plena trazabilidad farmacéutica.
                </p>
                
                <div class="flex justify-center gap-4">
                    <a href="{{ route('productos.instructivo.edit', $producto->id) }}" class="inline-flex items-center px-12 py-5 bg-slate-900 border-b-4 border-slate-700 rounded-xl font-black text-lg text-white uppercase tracking-widest hover:scale-105 active:translate-y-1 transition-all shadow-2xl group">
                        <svg class="w-6 h-6 mr-3 text-aurofarma-orange group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                        Abrir Master Builder EBR
                    </a>
                </div>
            </div>

            <!-- Botón Submit -->
            <div class="flex justify-end mb-10 pb-10 border-t border-gray-100 pt-8">
                <button type="submit" class="bg-aurofarma-blue py-4 px-10 border border-transparent rounded-xl shadow-lg text-lg font-bold text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-aurofarma-blue flex items-center transition-transform hover:-translate-y-1">
                    Guardar Cambios del Producto
                    <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </button>
            </div>
            
        </form> <!-- Cierre Formulario -->

    </div> <!-- Cierre container max-w-7xl -->
</div> <!-- Cierre container py-6 -->
@endsection

@push('scripts')
<script>
    const catalogoItems = @json($all_items);

    function buscarMaterialPorCodigo(codigo, container) {
        const itemNombreInput = container.querySelector('.pkg-name');
        const hiddenIdInput = container.querySelector('.hidden-item-id');
        
        let codigoDigitado = String(codigo || '').trim().toUpperCase();

        if (!codigoDigitado) {
            itemNombreInput.value = '';
            hiddenIdInput.value = '';
            itemNombreInput.classList.remove('bg-red-50', 'text-red-800');
            return;
        }

        const itemEncontrado = catalogoItems.find(i => i && String(i.codigo || '').trim().toUpperCase() === codigoDigitado);

        if (itemEncontrado) {
            itemNombreInput.value = itemEncontrado.nombre;
            hiddenIdInput.value = itemEncontrado.id;
            itemNombreInput.classList.remove('bg-red-50', 'text-red-800');
            itemNombreInput.classList.add('bg-green-50');
            setTimeout(() => itemNombreInput.classList.remove('bg-green-50'), 1000);
        } else {
            itemNombreInput.value = 'Material no encontrado';
            hiddenIdInput.value = '';
            itemNombreInput.classList.add('bg-red-50', 'text-red-800');
        }
    }

    // --- AUTOCOMPLETADO DE PRESENTACIÓN A PARTIR DEL CÓDIGO SKU (TABLA ITEMS) ---
    let presSkuTimers = {};
    function autocompletarPresentacionDesdeSku(inputEl) {
        if (!inputEl) return;
        const card = inputEl.closest('.bg-white') || inputEl.closest('.border-indigo-100');
        if (!card) return;
        const nameInput = card.querySelector('.pres-name-input') || card.querySelector('input[name*="[name]"]');
        const spinner = card.querySelector('.pres-spinner');
        const checkIcon = card.querySelector('.pres-check');
        const codigo = String(inputEl.value || '').trim().toUpperCase();

        if (!codigo) {
            if (checkIcon) checkIcon.classList.add('hidden');
            return;
        }

        const timerKey = inputEl.name || 'sku_default';
        clearTimeout(presSkuTimers[timerKey]);
        presSkuTimers[timerKey] = setTimeout(async () => {
            if (spinner) spinner.classList.remove('hidden');
            try {
                // 1. Buscar en catálogo en memoria (Respuesta instantánea 0ms)
                let itemLocal = null;
                if (typeof catalogoItems !== 'undefined' && Array.isArray(catalogoItems)) {
                    itemLocal = catalogoItems.find(i => {
                        if (!i) return false;
                        const c = String(i.codigo || '').trim().toUpperCase();
                        return c === codigo || (c && codigo && c.replace(/^0+/, '') === codigo.replace(/^0+/, ''));
                    });
                    if (!itemLocal && codigo.length >= 3) {
                        itemLocal = catalogoItems.find(i => {
                            if (!i) return false;
                            const c = String(i.codigo || '').trim().toUpperCase();
                            return c.includes(codigo) || codigo.includes(c);
                        });
                    }
                }

                if (itemLocal && itemLocal.nombre && nameInput) {
                    nameInput.value = itemLocal.nombre;
                    nameInput.classList.remove('bg-red-50');
                    nameInput.classList.add('bg-emerald-50');
                    setTimeout(() => nameInput.classList.remove('bg-emerald-50'), 1200);
                    if (checkIcon) checkIcon.classList.remove('hidden');
                    if (spinner) spinner.classList.add('hidden');
                    return;
                }

                // 2. Si no está en memoria, consultar la API en tiempo real
                const resp = await fetch(`/api/items/${encodeURIComponent(codigo)}`);
                if (resp.ok) {
                    const data = await resp.json();
                    if (data.success && nameInput) {
                        nameInput.value = data.description || data.name || data.ext_1_detail || '';
                        nameInput.classList.remove('bg-red-50');
                        nameInput.classList.add('bg-emerald-50');
                        setTimeout(() => nameInput.classList.remove('bg-emerald-50'), 1200);
                        if (checkIcon) checkIcon.classList.remove('hidden');
                    } else {
                        if (checkIcon) checkIcon.classList.add('hidden');
                    }
                } else {
                    if (checkIcon) checkIcon.classList.add('hidden');
                }
            } catch (err) {
                console.error('Error buscando ítem SKU:', err);
                if (checkIcon) checkIcon.classList.add('hidden');
            } finally {
                if (spinner) spinner.classList.add('hidden');
            }
        }, 150);
    }

    let rmIndex = Date.now();
    let presentationIndex = Date.now() + 1;
    let pkgCounters = {}; 

    function toggleSection(id) {
        const el = document.getElementById(id);
        const icon = document.getElementById(id + '-icon');
        if (el.classList.contains('hidden')) {
            el.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            el.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }

    // API Call
    async function fetchItemName(code, nameInputEl) {
        if (!code) {
            nameInputEl.value = '';
            return;
        }
        try {
            const response = await fetch(`/api/items/${code}`);
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    nameInputEl.value = data.name;
                    nameInputEl.classList.add('bg-green-50');
                    setTimeout(() => nameInputEl.classList.remove('bg-green-50'), 1000);
                }
            } else {
                nameInputEl.value = 'Item no encontrado.';
            }
        } catch (error) {
            console.error('API Error:', error);
        }
    }

    function addRawMaterialRow(data = null) {
        const tbody = document.getElementById('raw_materials_body');
        const tr = document.createElement('tr');
        tr.className = "hover:bg-emerald-50 transition-colors";
        
        const code = data ? data.material_code : '';
        const name = data ? data.material_name : '';
        const func = data && data.function ? data.function : '';
        const perc = data ? data.percentage : '';
        const unit = data ? data.unit : 'KIL';

        tr.innerHTML = `
            <td class="px-4 py-2">
                <input type="text" name="raw_materials[${rmIndex}][code]" value="${code}" required class="w-full py-1.5 px-2 text-sm border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500 font-mono" placeholder="Cód..." onblur="fetchItemName(this.value, this.closest('tr').querySelector('.rm-name'))">
            </td>
            <td class="px-4 py-2 relative">
                <input type="text" name="raw_materials[${rmIndex}][name]" value="${name}" required readonly class="rm-name w-full py-1.5 px-2 text-sm border-gray-200 rounded bg-gray-100 text-gray-700 cursor-not-allowed" placeholder="Esperando código...">
            </td>
            <td class="px-4 py-2">
                <input type="text" name="raw_materials[${rmIndex}][function]" value="${func}" class="w-full py-1.5 px-2 text-sm border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500" placeholder="Ej. API">
            </td>
            <td class="px-4 py-2">
                <select name="raw_materials[${rmIndex}][unit]" class="w-full py-1.5 px-2 text-sm border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500 font-medium">
                    <option value="KIL" ${unit === 'KIL' ? 'selected' : ''}>KIL</option>
                    <option value="LIT" ${unit === 'LIT' ? 'selected' : ''}>LIT</option>
                    <option value="UND" ${unit === 'UND' ? 'selected' : ''}>UND</option>
                </select>
            </td>
            <td class="px-4 py-3">
                <input type="number" step="0.01" name="raw_materials[${rmIndex}][percentage]" value="${perc}" required 
                    class="w-full text-center text-2xl font-black text-blue-900 bg-blue-50 border-2 border-blue-400 rounded-lg p-3 shadow-inner focus:ring-4 focus:ring-blue-300 focus:border-blue-600 transition-all" 
                    style="min-width: 140px;" placeholder="0.00">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50" title="Eliminar fila">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
        rmIndex++;
    }

    function addPresentationBlock(data = null) {
        const msg = document.getElementById('empty_presentations_msg');
        if(msg) msg.style.display = 'none';
        
        const container = document.getElementById('presentations_container');
        const blockDiv = document.createElement('div');
        
        const currentPresIndex = presentationIndex;
        pkgCounters[currentPresIndex] = 0; 
        
        const code = data ? data.presentation_code : '';
        const name = data ? data.name : '';

        blockDiv.className = "bg-white border border-indigo-100 shadow-md rounded-xl p-6 mb-6 relative overflow-hidden";
        blockDiv.innerHTML = `
            <div class="absolute top-0 left-0 w-2 h-full bg-indigo-500"></div>
            
            <div class="flex justify-between items-start mb-4 pl-4 grid grid-cols-12 gap-4">
                <div class="col-span-4">
                    <label class="block text-sm font-bold text-indigo-900 mb-1">Código SKU (Ítem) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" name="presentations[${currentPresIndex}][presentation_code]" value="${code}" required 
                               class="pres-sku-input focus:ring-indigo-500 focus:border-indigo-500 block w-full py-2 px-3 text-sm font-mono font-black border-indigo-200 rounded-lg shadow-sm text-indigo-900 uppercase" 
                               placeholder="Ej. 10001"
                               oninput="autocompletarPresentacionDesdeSku(this)"
                               onblur="autocompletarPresentacionDesdeSku(this)"
                               onchange="autocompletarPresentacionDesdeSku(this)"
                               onpaste="setTimeout(() => autocompletarPresentacionDesdeSku(this), 50)">
                        <span class="pres-spinner hidden absolute right-2.5 top-2.5 text-indigo-600">
                            <i class="fas fa-spinner fa-spin text-xs"></i>
                        </span>
                    </div>
                    <span class="text-[10px] text-gray-500 mt-1 block">
                        <i class="fas fa-search text-[9px] text-indigo-500 mr-0.5"></i> Busca en <code>items</code> y autocompleta la presentación
                    </span>
                </div>
                <div class="col-span-6">
                    <label class="block text-sm font-bold text-indigo-900 mb-1">Nombre de la Presentación <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" name="presentations[${currentPresIndex}][name]" value="${name}" required 
                               class="pres-name-input focus:ring-indigo-500 focus:border-indigo-500 block w-full py-2 px-3 sm:text-base font-bold border-indigo-200 rounded-lg shadow-sm text-indigo-900 placeholder-indigo-300 transition-all" 
                               placeholder="Ej. FRASCO X 1000 ML o SACO X 25 KG">
                        <span class="pres-check ${name ? '' : 'hidden'} absolute right-2.5 top-2.5 text-emerald-500 text-sm" title="Presentación confirmada">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    </div>
                </div>
                <div class="col-span-2 flex justify-end items-end pb-1">
                    <button type="button" onclick="this.closest('.bg-white').remove()" class="text-red-500 hover:text-red-700 text-sm font-medium flex items-center p-2 rounded hover:bg-red-50 transition-colors" title="Eliminar Presentación">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Eliminar
                    </button>
                </div>
            </div>

            <div class="pl-4 mt-6">
                <div class="flex justify-between items-end mb-3">
                    <h5 class="font-bold text-gray-700 text-sm uppercase tracking-wide">Materiales de Envase y Empaque</h5>
                    <button type="button" onclick="addPackagingRowWithCounter(${currentPresIndex}, this)" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 py-1.5 px-3 rounded font-bold transition-colors flex items-center border border-indigo-200">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Agregar Material
                    </button>
                </div>
                
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50 uppercase tracking-wider text-[10px] font-black">
                                                    <tr>
                                                        <th scope="col" class="px-4 py-2 text-left w-32 text-gray-500">Código</th>
                                                        <th scope="col" class="px-4 py-2 text-left text-gray-500">Nombre del Material</th>
                                                        <th scope="col" class="px-4 py-2 text-left w-1/6 text-gray-500">Tipo</th>
                                                        <th scope="col" class="px-4 py-2 text-left w-1/6 text-gray-500">MATERIAL</th>
                                                        <th scope="col" class="px-4 py-2 text-center text-gray-500" style="min-width: 80px;">U.M</th>
                                                        <th scope="col" class="px-4 py-2 text-center w-48 text-gray-500">PORCENTAJE (%)</th>
                                                        <th scope="col" class="px-2 py-2 w-10 text-gray-500"></th>
                                                    </tr>
                                                </thead>
                        <tbody class="bg-white divide-y divide-gray-200 pkg-tbody">
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        container.appendChild(blockDiv);
        
        if (data && data.materials && data.materials.length > 0) {
            const addBtn = blockDiv.querySelector('button[onclick^="addPackagingRow"]');
            data.materials.forEach(pkg => {
                addPackagingRowToPresentation(currentPresIndex, addBtn, pkg);
            });
        }

        presentationIndex++;
    }

    function addPackagingRowToPresentation(presId, buttonEl, data = null, overridePkgId = null) {
        const tbody = buttonEl.closest('.pl-4').querySelector('.pkg-tbody');
        const currentPkgId = overridePkgId !== null ? overridePkgId : pkgCounters[presId];
        
        const code = data ? data.material_code : '';
        const name = data ? data.material_name : '';
        const type = data ? data.material_type : 'MATERIAL DE EMPAQUE';
        const perc = data ? data.percentage : '';
        const unit = data ? data.unit : 'UND';
        const itemId = data ? (data.item ? data.item.id : '') : '';

        const tr = document.createElement('tr');
        tr.className = "hover:bg-indigo-50 transition-colors material-row";
        
        tr.innerHTML = `
            <td class="px-4 py-2">
                <input type="text" value="${code}" 
                    class="w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 font-mono uppercase input-codigo-material" 
                    placeholder="Código" 
                    oninput="buscarMaterialPorCodigo(this.value, this.closest('tr'))">
                <input type="hidden" name="presentations[${presId}][packaging][${currentPkgId}][item_id]" value="${itemId}" class="hidden-item-id">
            </td>
            <td class="px-4 py-2">
                <input type="text" value="${name}" readonly class="pkg-name w-full py-1 px-2 text-xs border-gray-200 rounded bg-gray-100 text-gray-700 cursor-not-allowed" placeholder="Busque un código...">
            </td>
            <td class="px-4 py-2">
                <input type="text" name="presentations[${presId}][packaging][${currentPkgId}][type]" value="${type}" required 
                    class="w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ej. Frasco">
            </td>
            <td class="px-4 py-2">
                <select name="presentations[${presId}][packaging][${currentPkgId}][material]" required class="w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 font-bold bg-amber-50">
                    <option value="N.A.">N.A.</option>
                    <option value="ETIQUETA">ETIQUETA</option>
                    <option value="PLEGADIZA">PLEGADIZA</option>
                    <option value="SOBRE">SOBRE</option>
                    <option value="JERINGA">JERINGA</option>
                    <option value="COLLARIN">COLLARIN</option>
                    <option value="GARRAFA">GARRAFA</option>
                </select>
            </td>
            <td class="px-4 py-2 text-xs text-center font-bold text-gray-700" style="min-width: 80px;">${unit}</td>
            <td class="px-4 py-3">
                <input type="number" step="0.01" name="presentations[${presId}][packaging][${currentPkgId}][percentage]" value="${perc}" required 
                    class="w-full text-center text-xl font-bold text-blue-900 bg-blue-50 border-2 border-blue-400 rounded-lg p-2 shadow-inner focus:ring-4 focus:ring-blue-300 focus:border-blue-600 transition-all font-mono" 
                    style="min-width: 140px;" placeholder="0.00">
            </td>
            <td class="px-2 py-2 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-1 rounded-full hover:bg-red-50" title="Eliminar empaque">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
        pkgCounters[presId]++;
    }

    // Inicializar contadores para empaque de presentaciones existentes
    document.addEventListener('DOMContentLoaded', () => {
        const pkgTbodies = document.querySelectorAll('.pkg-tbody');
        pkgTbodies.forEach(tbody => {
            // No hacemos nada especial, solo asegurar que pkgCounters se maneje bajo demanda
        });
    });

    // Helper para inicializar el contador de una presentación si no existe
    function getPkgCounter(presId) {
        if (!pkgCounters[presId]) {
            pkgCounters[presId] = Date.now();
        }
        return pkgCounters[presId]++;
    }

    // Modificar la llamada original
    function addPackagingRowWithCounter(presId, buttonEl) {
        const counter = getPkgCounter(presId);
        addPackagingRowToPresentation(presId, buttonEl, null, counter);
    }
</script>
@endpush
