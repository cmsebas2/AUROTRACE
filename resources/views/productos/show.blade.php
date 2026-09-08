@extends('layouts.app')

@section('header_title', 'Ficha Maestra de Producto')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    
    <!-- Breadcrumb Actions -->
    <div class="flex justify-between items-center">
        <a href="{{ route('productos.index') }}" class="text-slate-500 hover:text-cyan-600 font-bold text-xs flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Catálogo de Fórmulas
        </a>
    </div>

    <!-- ENCABEZADO SUPERIOR (Tarjeta 3D con Relieve e Iluminación) -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
            <!-- Lado Izquierdo (Imagen 3D & Título) -->
            <div class="flex items-center gap-6">
                <!-- Imagen en Contenedor 3D -->
                <div class="w-28 h-28 bg-gradient-to-b from-slate-50 to-white rounded-2xl flex-shrink-0 flex items-center justify-center border border-slate-200/80 overflow-hidden shadow-3d-card p-3 group">
                    <img src="{{ asset('img/productos/' . $product['image']) }}" 
                         alt="{{ $product['name'] }}"
                         class="max-h-24 max-w-full object-contain filter drop-shadow-[0_4px_10px_rgba(0,0,0,0.12)] group-hover:scale-105 transition-transform"
                         onerror="this.onerror=null; this.outerHTML='<div class=\'w-12 h-12 text-cyan-600 flex items-center justify-center\'><svg class=\'w-10 h-10\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z\'></path></svg></div>';">
                </div>
                
                <!-- Título y Badges 3D -->
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="font-display text-2xl lg:text-3xl font-black text-slate-900 tracking-tight uppercase">
                            {{ $product['name'] }}
                        </h1>
                        
                        @if($product['status'] === 'ACTIVO')
                            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 text-[10px] font-black rounded-full border border-emerald-200 shadow-3d-badge uppercase tracking-widest">
                                ● Activo
                            </span>
                        @else
                            <span class="px-2.5 py-1 bg-red-50 text-red-800 text-[10px] font-black rounded-full border border-red-200 shadow-3d-badge uppercase tracking-widest">
                                ● {{ $product['status'] }}
                            </span>
                        @endif

                        <span class="px-3 py-1 bg-amber-50 text-amber-900 text-[10px] font-black rounded-lg border border-amber-200 shadow-sm uppercase tracking-wider flex items-center">
                            <i class="fas fa-certificate mr-1.5 text-amber-500"></i> REGISTRO ICA: {{ $product['ica_license'] ?? 'N/A' }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-500 font-medium">
                        {{ collect($product['presentations']->pluck('name'))->implode(', ') }} • Cód: <strong class="text-slate-700">{{ $product['product_code'] }}</strong>
                    </p>

                    <!-- Pills de Especificaciones Oficiales -->
                    <div class="flex flex-wrap gap-2 pt-1">
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-cyan-50 text-cyan-800 border border-cyan-200 shadow-sm">
                            <i class="fas fa-capsules mr-1.5 text-cyan-600"></i> Forma: {{ $product['pharmaceutical_form'] }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200 shadow-sm">
                            <i class="fas fa-boxes mr-1.5 text-blue-600"></i> {{ $product['presentation_name'] }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-sm">
                            <i class="fas fa-percentage mr-1.5 text-emerald-600"></i> Concentración: {{ $productDb->active_ingredient_concentration ?? '0' }}%
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200 shadow-sm">
                            <i class="fas fa-flask mr-1.5 text-indigo-600"></i> FM: {{ $product['formula_maestra'] ?? 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Lado Derecho (KPIs y Botones de Acción) -->
            <div class="flex flex-row lg:flex-col items-center lg:items-end justify-between w-full lg:w-auto border-t lg:border-t-0 border-slate-100 pt-4 lg:pt-0 gap-4">
                <div class="text-left lg:text-right">
                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-wider">Lotes Fabricados (YTD)</p>
                    <p class="font-display text-3xl font-black text-cyan-700">{{ $product['manufactured_lots'] }} <span class="text-xs text-slate-400 font-bold">OPs</span></p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <a href="{{ route('productos.edit', $product['id']) }}" 
                       class="px-3.5 py-2 rounded-xl text-xs font-black bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300 transition-all flex items-center shadow-sm">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Editar
                    </a>
                    
                    <a href="{{ route('productos.imprimir', $product['id']) }}" target="_blank" 
                       class="px-4 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-[#005889] to-[#06B6D4] text-white shadow-3d-button hover:shadow-3d-cyan transition-all flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Imprimir Ficha
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- SISTEMA DE PESTAÑAS 3D (Tabs) -->
    <div class="card-3d border border-slate-200/80 overflow-hidden bg-white">
        <div class="border-b border-slate-200/80 bg-slate-50/50 px-6">
            <nav class="flex space-x-6" aria-label="Tabs" id="productTabs">
                <button onclick="switchTab('formula')" id="tab-formula" 
                        class="py-4 px-2 border-b-2 font-display text-xs font-black uppercase tracking-wider border-cyan-500 text-cyan-700 transition-all flex items-center space-x-2">
                    <i class="fas fa-flask"></i>
                    <span>Fórmula Maestra</span>
                </button>
                <button onclick="switchTab('instructivo')" id="tab-instructivo" 
                        class="py-4 px-2 border-b-2 font-display text-xs font-black uppercase tracking-wider border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center space-x-2">
                    <i class="fas fa-file-invoice"></i>
                    <span>Instructivos Maestros (EBR)</span>
                </button>
                <button onclick="switchTab('produccion')" id="tab-produccion" 
                        class="py-4 px-2 border-b-2 font-display text-xs font-black uppercase tracking-wider border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center space-x-2">
                    <i class="fas fa-industry"></i>
                    <span>Historial de Lotes</span>
                </button>
                <button onclick="switchTab('calidad')" id="tab-calidad" 
                        class="py-4 px-2 border-b-2 font-display text-xs font-black uppercase tracking-wider border-transparent text-slate-500 hover:text-slate-800 transition-all flex items-center space-x-2">
                    <i class="fas fa-shield-alt"></i>
                    <span>Control de Calidad (COAs)</span>
                </button>
            </nav>
        </div>

        <!-- Contenido de las Pestañas -->
        <div class="p-6">
            <!-- Pestaña: Fórmula -->
            <div id="content-formula" class="block space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h3 class="font-display text-lg font-black text-slate-800 tracking-tight">Receta Aprobada Vigente</h3>
                        <p class="text-xs text-slate-500">Componentes requeridos para el pesaje y dosificación en planta</p>
                    </div>
                    <div class="mt-2 sm:mt-0 px-3.5 py-1.5 rounded-full bg-slate-900 text-white text-xs font-black uppercase tracking-wider shadow-sm">
                        Lote Base: <span class="text-cyan-300 font-mono">{{ $product['base_batch_size'] }} {{ $product['base_unit'] }}</span>
                    </div>
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
                                    <td colspan="5" class="px-6 py-6 text-center text-xs text-slate-400 italic">No se encontraron materias primas en la receta maestra.</td>
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
                                    <td colspan="6" class="px-6 py-6 text-center text-xs text-slate-400 italic">No se encontraron materiales de empaque registrados.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pestaña: Instructivos Maestros (EBR) -->
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
                                            <button onclick="confirmDeletePlan({{ $product['active_plan']->id }})" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-colors border border-red-200 shadow-sm">
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

            <!-- Pestaña: Producción -->
            <div id="content-produccion" class="hidden text-center py-12 text-slate-400">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-2 text-sm font-black text-slate-800 uppercase">Historial de Lotes</h3>
                <p class="text-xs text-slate-500 mt-1">Consulte los registros históricos de órdenes cerradas para esta fórmula.</p>
            </div>

            <!-- Pestaña: Calidad -->
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
            el.classList.remove('border-cyan-500', 'text-cyan-700');
            el.classList.add('border-transparent', 'text-slate-500');
        });

        // Show selected content and activate tab
        document.getElementById('content-' + tabName).classList.remove('hidden');
        const activeEl = document.getElementById('tab-' + tabName);
        activeEl.classList.remove('border-transparent', 'text-slate-500');
        activeEl.classList.add('border-cyan-500', 'text-cyan-700');
    }

    function confirmDeletePlan(planId) {
        Swal.fire({
            title: '¿Confirmar eliminación?',
            text: "Esta acción eliminará el Instructivo Maestro y sus fases configuradas bajo auditoría CFR 21. No se puede revertir.",
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
