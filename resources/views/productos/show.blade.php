@extends('layouts.app')

@section('header_title', 'Detalle de Producto')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <div>
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                {{ $product['name'] }}
                @if($product['status'] === 'ACTIVO')
                    <span class="ml-3 px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full uppercase tracking-wider">Activo</span>
                @else
                    <span class="ml-3 px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full uppercase tracking-wider">{{ $product['status'] }}</span>
                @endif
                <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-600 text-[10px] font-bold rounded border border-gray-200 uppercase tracking-tighter">
                    <i class="fas fa-certificate mr-1"></i> Registro ICA: {{ $product['ica_license'] ?? 'N/A' }}
                </span>
            </h2>
            <p class="text-sm text-gray-500 mt-1">{{ collect($product['presentations']->pluck('name'))->implode(', ') }} • Cód: {{ $product['product_code'] }}</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <a href="{{ route('productos.edit', $product['id']) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold py-2 px-4 rounded-lg shadow-sm border border-gray-300 transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Editar Producto
            </a>
            <a href="{{ route('productos.imprimir', $product['id']) }}" target="_blank" class="bg-aurofarma-blue hover:bg-blue-800 text-white text-sm font-bold py-2 px-4 rounded-lg shadow transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir Ficha
            </a>
        </div>
    </div>
    <!-- Breadcrumb Actions -->
    <div class="flex justify-between items-center">
        <a href="{{ route('productos.index') }}" class="text-gray-500 hover:text-aurofarma-blue font-medium text-sm flex items-center transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Catálogo
        </a>
    </div>

    <!-- ENCABEZADO SUPERIOR -->
    <div class="bg-white shadow-sm rounded-xl p-6 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 border border-gray-200">
        <!-- Lado Izquierdo (Info principal) -->
        <div class="flex items-center gap-6">
            <!-- Imagen / Icono -->
            <div class="w-24 h-24 bg-gray-50 rounded-lg flex-shrink-0 flex items-center justify-center border border-gray-100 overflow-hidden shadow-inner">
                <img src="{{ asset('img/productos/' . $product['image']) }}" 
                     alt="{{ $product['name'] }}"
                     class="w-full h-full object-contain p-2"
                     onerror="this.onerror=null; this.outerHTML='<svg class=\'w-12 h-12 text-gray-300\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z\'></path></svg>';">
            </div>
            
            <!-- Título y Badges -->
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-3 uppercase flex items-center">
                    {{ $product['name'] }}
                    <span class="ml-4 px-3 py-1 bg-amber-50 text-amber-900 text-[10px] font-black rounded-lg border-2 border-amber-200 shadow-sm uppercase tracking-widest flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        REGISTRO ICA: {{ $product['ica_license'] ?? 'N/A' }}
                    </span>
                </h1>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                        {{ $product['pharmaceutical_form'] }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                        {{ $product['presentation_name'] }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm uppercase tracking-tight">
                        <i class="fas fa-percentage mr-1.5 text-emerald-400"></i> Concentración: {{ $productDb->active_ingredient_concentration ?? '0' }}%
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                        {{ $product['status'] }}
                    </span>
                    <!-- FM Badge Relocated -->
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-sm uppercase tracking-tight">
                        <i class="fas fa-flask mr-1.5 text-indigo-400"></i> {{ $product['formula_maestra'] ?? 'N/A' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Lado Derecho (KPIs y Botón) -->
        <div class="flex flex-col items-start md:items-end gap-3 w-full md:w-auto border-t md:border-t-0 border-gray-100 pt-4 md:pt-0">
            <div class="text-left md:text-right">
                <p class="text-sm text-gray-500 font-medium">Lotes Fabricados (YTD)</p>
                <p class="text-3xl font-black text-aurofarma-blue">{{ $product['manufactured_lots'] }}</p>
            </div>
            <button class="w-full md:w-auto inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-aurofarma-blue hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-aurofarma-blue transition-colors">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Nueva Orden de Producción
            </button>
        </div>
    </div>

    <!-- SISTEMA DE PESTAÑAS (Tabs) -->
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px px-6" aria-label="Tabs" id="productTabs">
                <button onclick="switchTab('formula')" id="tab-formula" class="w-1/4 md:w-auto py-4 px-1 text-center border-b-2 font-medium text-sm border-aurofarma-blue text-aurofarma-blue">
                    Fórmula Maestra
                </button>
                <button onclick="switchTab('instructivo')" id="tab-instructivo" class="w-1/4 md:w-auto py-4 px-1 ml-8 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Instructivos Maestros (EBR)
                </button>
                <button onclick="switchTab('produccion')" id="tab-produccion" class="w-1/4 md:w-auto py-4 px-1 ml-8 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Producción
                </button>
                <button onclick="switchTab('calidad')" id="tab-calidad" class="w-1/3 md:w-auto py-4 px-1 ml-8 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Control de Calidad
                </button>
            </nav>
        </div>

        <!-- Contenido de las Pestañas -->
        <div class="p-6">
            <!-- Pestaña: Fórmula -->
            <div id="content-formula" class="block">
                <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <h3 class="text-lg font-bold text-gray-900 border-l-4 border-aurofarma-teal pl-2">Receta Aprobada Vigente</h3>
                    <p class="text-sm text-gray-500 mt-2 sm:mt-0">Lote Base de Cálculo: <span class="font-bold text-gray-700">{{ $product['base_batch_size'] }} {{ $product['base_unit'] }}</span></p>
                </div>
                
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Código</th>
                <!-- Materias Primas -->
                <h4 class="text-md font-semibold text-gray-800 mb-2 mt-6">Materias Primas / Granel</h4>
                <div class="overflow-x-auto rounded-lg border border-gray-200 mb-6 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Código Material</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nombre Material</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Función</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">%</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">UND</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($product['raw_materials'] as $ing)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">{{ $ing['code'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $ing['description'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if(str_contains(strtoupper($ing['function']), 'API') || str_contains(strtoupper($ing['function']), 'PRINCIPIO'))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">{{ $ing['function'] }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">{{ $ing['function'] }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono font-bold text-gray-700 bg-gray-50/50">{{ $ing['quantity'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">{{ $ing['unit'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 italic">No se encontraron materias primas en la receta.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Materiales de Empaque -->
                <h4 class="text-md font-semibold text-gray-800 mb-2 mt-6">Material de Empaque y Envase</h4>
                <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Código Material</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nombre Material</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo Material</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">MATERIAL</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">U.M.</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">PORCENTAJE (%)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($product['packaging'] as $ing)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">{{ $ing['code'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $ing['description'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">{{ $ing['tipo_material'] }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-black bg-orange-50 text-orange-700 border border-orange-200 shadow-sm uppercase tracking-tighter">{{ $ing['material_clasificacion'] }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-gray-900">{{ $ing['unit'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono font-bold text-gray-700 bg-gray-50/50">{{ number_format($ing['quantity'], 2, '.', '') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 italic">No se encontraron materiales de empaque en la receta.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pestaña: Instructivos Maestros (EBR) -->
            <div id="content-instructivo" class="hidden">
                <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 border-l-4 border-aurofarma-orange pl-2">Gestión de Instructivos Maestros</h3>
                        <p class="text-sm text-gray-500 ml-3">Documentos base para la generación de Órdenes de Producción.</p>
                    </div>
                    @if($product['active_plan'])
                        {{-- Ya tiene un plan activo, se gestiona en la lista --}}
                    @else
                        <a href="{{ route('productos.instructivo.edit', $product['id']) }}" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-bold rounded-md text-white bg-aurofarma-blue hover:bg-blue-800 transition-all">
                            <i class="fas fa-plus mr-2"></i> Crear Instructivo Maestro
                        </a>
                    @endif
                </div>

                @if($product['active_plan'])
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Documento</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Cód. Maestro</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Cód. Interno</th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Versión</th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">F. Emisión</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600">
                                                    <i class="fas fa-file-invoice text-xl"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-black text-gray-900">INSTRUCTIVO MAESTRO</div>
                                                    <div class="text-[10px] text-gray-500 uppercase">Batch Base: {{ number_format($product['active_plan']->master_batch_size, 2, '.', '') }} KG</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">{{ $product['active_plan']->master_code_header ?? '---' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">{{ $product['active_plan']->internal_code ?? '---' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 py-1 text-[10px] font-black bg-blue-50 text-blue-700 rounded-md border border-blue-100">V{{ $product['active_plan']->version }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($product['active_plan']->issue_date)->format('Y-m-d') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <a href="{{ route('productos.instructivo.edit', $product['id']) }}" class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors shadow-sm border border-gray-300">
                                                <i class="fas fa-print mr-1.5 text-blue-600"></i> Ver / Imprimir
                                            </a>
                                            <a href="{{ route('productos.instructivo.edit', $product['id']) }}" class="inline-flex items-center px-3 py-1 bg-white text-aurofarma-blue rounded hover:bg-blue-50 transition-colors shadow-sm border border-blue-200">
                                                <i class="fas fa-edit mr-1.5"></i> Editar
                                            </a>
                                            <button onclick="confirmDeletePlan({{ $product['active_plan']->id }})" class="inline-flex items-center px-3 py-1 bg-white text-red-600 rounded hover:bg-red-50 transition-colors shadow-sm border border-red-200">
                                                <i class="fas fa-trash-alt mr-1.5"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12 bg-gray-50 rounded-xl border-2 border-gray-200 border-dashed">
                        <div class="w-16 h-16 bg-white rounded-full shadow-sm flex items-center justify-center mx-auto mb-4 border border-gray-100">
                            <i class="fas fa-file-medical text-3xl text-gray-300"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">No hay Instructivo Maestro configurado</h3>
                        <p class="mt-1 text-sm text-gray-500 max-w-sm mx-auto">Para iniciar la producción de este producto, primero debe definir los procesos, tiempos y materiales en un instructivo maestro.</p>
                        <a href="{{ route('productos.instructivo.edit', $product['id']) }}" class="mt-6 inline-flex items-center px-6 py-3 border border-transparent shadow-md text-base font-bold rounded-lg text-white bg-aurofarma-blue hover:bg-blue-800 transition-all">
                            <i class="fas fa-plus-circle mr-2"></i> Comenzar Configuración
                        </a>
                    </div>
                @endif
            </div>

            <!-- Pestaña: Producción (Vacía por ahora) -->
            <div id="content-produccion" class="hidden text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Sin Órdenes Recientes</h3>
                <p class="mt-1 text-sm text-gray-500">Aún no hay lotes en proceso para este producto.</p>
            </div>

            <!-- Pestaña: Calidad (Vacía por ahora) -->
            <div id="content-calidad" class="hidden text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Historial Analítico</h3>
                <p class="mt-1 text-sm text-gray-500">Módulo en construcción.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function switchTab(tabName) {
        // Hide all contents
        document.getElementById('content-formula').classList.add('hidden');
        document.getElementById('content-instructivo').classList.add('hidden');
        document.getElementById('content-produccion').classList.add('hidden');
        document.getElementById('content-calidad').classList.add('hidden');
        
        // Reset all tabs styles
        const tabs = ['formula', 'instructivo', 'produccion', 'calidad'];
        tabs.forEach(t => {
            const el = document.getElementById('tab-' + t);
            el.classList.remove('border-aurofarma-blue', 'text-aurofarma-blue');
            el.classList.add('border-transparent', 'text-gray-500');
        });

        // Show selected content and activate tab
        document.getElementById('content-' + tabName).classList.remove('hidden');
        const activeEl = document.getElementById('tab-' + tabName);
        activeEl.classList.remove('border-transparent', 'text-gray-500');
        activeEl.classList.add('border-aurofarma-blue', 'text-aurofarma-blue');
    }

    function confirmDeletePlan(planId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción eliminará permanentemente el Instructivo Maestro y todos sus pasos configurados. No se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar permanentemente',
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
                        Swal.fire(
                            '¡Eliminado!',
                            data.message,
                            'success'
                        ).then(() => {
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
