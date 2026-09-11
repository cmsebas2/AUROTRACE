@extends('layouts.app')

@section('header_title', 'Aseguramiento de Calidad · Control & Dictamen de Lotes')

@section('content')
<div class="w-full space-y-8 max-w-7xl mx-auto pb-16" x-data="calidadPortalApp()">

    <!-- Hero Header -->
    <div class="relative overflow-hidden rounded-3xl bg-slate-900 text-white p-6 sm:p-10 shadow-2xl border border-slate-800">
        <!-- Glow accents -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-cyan-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-3">
                <div class="flex items-center space-x-3">
                    <span class="px-3.5 py-1 rounded-full bg-cyan-500/20 text-cyan-300 font-mono text-[11px] font-black uppercase tracking-widest border border-cyan-500/30">
                        Aseguramiento de Calidad
                    </span>
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-bold border border-emerald-500/30 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-pulse"></span>
                        Portal Activo
                    </span>
                </div>
                <h1 class="font-display text-2xl sm:text-4xl font-black text-white tracking-tight">
                    Dictamen & Custodia de Calidad
                </h1>
                <p class="text-sm text-slate-300 font-medium max-w-2xl leading-relaxed">
                    Módulo de revisión técnica y trazabilidad de lotes. Evalúe solicitudes pendientes, consulte la posición de expedientes en el Archivo Físico y emita dictámenes oficiales de liberación.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('consultas.br') }}" 
                   class="px-5 py-3 rounded-2xl text-xs font-black uppercase tracking-wider text-slate-900 bg-cyan-400 hover:bg-cyan-300 shadow-lg hover:shadow-cyan-400/25 transition-all flex items-center space-x-2">
                    <i class="fas fa-archive text-sm"></i>
                    <span>Sala de Archivo Físico</span>
                </a>
            </div>
        </div>
    </div>

    <!-- KPIs Metric Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1 -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pendientes Dictamen</span>
                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-black">
                    <i class="fas fa-clock text-base"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="font-display text-3xl font-black text-slate-900">{{ number_format($kpis['pendientes_calidad']) }}</span>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg">Por Evaluar</span>
            </div>
        </div>

        <!-- KPI 2 -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">En Revisión DT</span>
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black">
                    <i class="fas fa-user-check text-base"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="font-display text-3xl font-black text-slate-900">{{ number_format($kpis['en_revision_dt']) }}</span>
                <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">Paso Previo</span>
            </div>
        </div>

        <!-- KPI 3 -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Lotes Liberados</span>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black">
                    <i class="fas fa-check-double text-base"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="font-display text-3xl font-black text-slate-900">{{ number_format($kpis['liberados_total']) }}</span>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg">Conformes</span>
            </div>
        </div>

        <!-- KPI 4 -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Custodia en RACK 1</span>
                <div class="w-10 h-10 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center font-black">
                    <i class="fas fa-box text-base"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="font-display text-3xl font-black text-slate-900">{{ number_format($kpis['total_archivados_rack']) }}</span>
                <span class="text-xs font-semibold text-cyan-600 bg-cyan-50 px-2.5 py-1 rounded-lg">En Custodia</span>
            </div>
        </div>
    </div>

    <!-- Navegación por Pestañas Principales -->
    <div class="flex items-center justify-between border-b border-slate-200/80 pb-4">
        <div class="flex items-center space-x-2">
            <button type="button" 
                    @click="activeTab = 'pendientes'" 
                    :class="activeTab === 'pendientes' ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all flex items-center space-x-2">
                <i class="fas fa-clipboard-list text-xs"></i>
                <span>Solicitudes Pendientes</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-mono font-black"
                      :class="activeTab === 'pendientes' ? 'bg-cyan-400 text-slate-900' : 'bg-slate-100 text-slate-700'">
                    {{ $solicitudesPendientes->count() }}
                </span>
            </button>

            <button type="button" 
                    @click="activeTab = 'ubicador'" 
                    :class="activeTab === 'ubicador' ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all flex items-center space-x-2">
                <i class="fas fa-search-location text-xs"></i>
                <span>Ubicador & Expedientes</span>
            </button>
        </div>

        <div class="hidden sm:flex items-center space-x-2 text-xs text-slate-500 font-medium">
            <i class="fas fa-shield-alt text-emerald-500"></i>
            <span>Módulo de Control Técnico</span>
        </div>
    </div>

    <!-- PESTAÑA 1: SOLICITUDES PENDIENTES DE DICTAMEN -->
    <div x-show="activeTab === 'pendientes'" x-cloak class="space-y-6">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-display text-lg font-black text-slate-900">
                        Órdenes Esperando Dictamen Final (QA)
                    </h2>
                    <p class="text-xs text-slate-500 font-medium">
                        Lotes con revisión técnica previa. Seleccione "Emitir Dictamen" para aprobar o registrar observaciones.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/80 text-[11px] font-black uppercase tracking-wider text-slate-400">
                            <th class="py-4 px-6">Identificación OP</th>
                            <th class="py-4 px-6">Lote & Producto</th>
                            <th class="py-4 px-6">Maquilador / Origen</th>
                            <th class="py-4 px-6 text-center">Unidades & Rendimiento</th>
                            <th class="py-4 px-6">Revisión DT</th>
                            <th class="py-4 px-6">Ubicación Archivo Físico</th>
                            <th class="py-4 px-6 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                        @forelse($solicitudesPendientes as $sol)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- OP -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="font-mono font-black text-slate-900 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200 inline-block text-xs">
                                    OP: {{ $sol->op ?? 'N/A' }}
                                </span>
                                @if($sol->numero_odm)
                                    <span class="text-[10px] text-slate-400 font-mono block mt-1">ODM: {{ $sol->numero_odm }}</span>
                                @endif
                            </td>

                            <!-- Lote & Producto -->
                            <td class="py-4 px-6">
                                <div class="space-y-1">
                                    <span class="font-mono font-black text-cyan-900 bg-cyan-50 px-2.5 py-0.5 rounded-md border border-cyan-200 text-xs inline-block">
                                        {{ $sol->lote }}
                                    </span>
                                    <div class="font-bold text-slate-900 text-xs leading-snug">
                                        {{ $sol->producto_nombre }}
                                    </div>
                                </div>
                            </td>

                            <!-- Maquilador -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="font-semibold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px]">
                                    {{ $sol->maquilador->nombre ?? 'MAQUILA EXTERNA' }}
                                </span>
                            </td>

                            <!-- Unidades & Rendimiento -->
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-900 text-xs">
                                    {{ number_format($sol->total_producto_terminado_fabricado ?? 0) }} u
                                </div>
                                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-black {{ ($sol->rendimiento_real ?? 100) >= 95 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ number_format($sol->rendimiento_real ?? 100, 1) }}%
                                </span>
                            </td>

                            <!-- Revisión DT -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase inline-block {{ $sol->estado_br_dt === 'CERRADO' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    DT: {{ $sol->estado_br_dt ?? 'PENDIENTE' }}
                                </span>
                            </td>

                            <!-- Ubicación Archivo Físico -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                @if($sol->posicion_archivo_fisico)
                                    <a href="{{ route('consultas.br', ['buscar' => $sol->lote]) }}" 
                                       target="_blank"
                                       class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-xl bg-cyan-50 text-cyan-800 border border-cyan-200 font-mono text-[11px] font-bold hover:bg-cyan-100 transition-colors">
                                        <i class="fas fa-archive text-[10px] text-cyan-600"></i>
                                        <span>{{ $sol->posicion_archivo_fisico }}</span>
                                    </a>
                                @else
                                    <span class="text-[11px] text-slate-400 font-medium italic">Sin ubicar</span>
                                @endif
                            </td>

                            <!-- Acción -->
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    <button type="button" 
                                            @click="abrirModalDictamen({{ $sol->id }}, '{{ $sol->op }}', '{{ $sol->lote }}', '{{ addslashes($sol->producto_nombre) }}')"
                                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-slate-900 hover:bg-slate-800 shadow-md transition-all flex items-center space-x-1.5">
                                        <i class="fas fa-check-circle text-xs text-cyan-400"></i>
                                        <span>Emitir Dictamen</span>
                                    </button>
                                    <a href="{{ route('maquila.show', $sol->id) }}" 
                                       class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-all" 
                                       title="Ver Detalle">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 px-6 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <p class="font-bold text-slate-700 text-sm">No hay solicitudes pendientes</p>
                                    <p class="text-xs text-slate-400">Todas las órdenes han sido dictaminadas correctamente.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PESTAÑA 2: UBICADOR GENERAL & HISTÓRICO DE BATCH RECORDS -->
    <div x-show="activeTab === 'ubicador'" x-cloak class="space-y-6">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xl overflow-hidden p-6 space-y-6">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="font-display text-lg font-black text-slate-900">
                        Histórico & Localización de Expedientes
                    </h2>
                    <p class="text-xs text-slate-500 font-medium">
                        Búsqueda directa por lote o producto para consultar estado y posición en el archivo físico.
                    </p>
                </div>
            </div>

            <!-- Filtros de Búsqueda -->
            <form method="GET" action="{{ route('calidad.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="relative sm:col-span-6">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                    <input type="text" name="buscar" value="{{ $searchQuery }}" 
                           placeholder="Buscar lote, producto, OP..." 
                           class="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-slate-200 focus:border-slate-800 focus:ring-2 focus:ring-slate-800/10 text-xs font-bold text-slate-800">
                </div>

                <div class="sm:col-span-4">
                    <select name="filtro_estado" onchange="this.form.submit()" 
                            class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-700 bg-white">
                        <option value="">Todos los Estados</option>
                        <option value="CON_UBICACION" {{ $filtroEstado === 'CON_UBICACION' ? 'selected' : '' }}>● Con Ubicación Física</option>
                        <option value="SIN_UBICACION" {{ $filtroEstado === 'SIN_UBICACION' ? 'selected' : '' }}>○ Sin Ubicación Física</option>
                        <option value="BR REVISION CALIDAD" {{ $filtroEstado === 'BR REVISION CALIDAD' ? 'selected' : '' }}>Pendiente Dictamen</option>
                        <option value="BR CERRADO" {{ $filtroEstado === 'BR CERRADO' ? 'selected' : '' }}>Liberado (Cerrado)</option>
                        <option value="BR ABIERTO" {{ $filtroEstado === 'BR ABIERTO' ? 'selected' : '' }}>Observaciones (Abierto)</option>
                    </select>
                </div>

                <div class="sm:col-span-2 flex items-center space-x-2">
                    <button type="submit" 
                            class="w-full px-4 py-2.5 rounded-2xl text-xs font-black uppercase tracking-wider text-white bg-slate-900 hover:bg-slate-800 shadow-sm transition-all">
                        Filtrar
                    </button>
                    @if(!empty($searchQuery) || !empty($filtroEstado))
                        <a href="{{ route('calidad.index') }}" class="p-2.5 text-slate-400 hover:text-slate-700 text-xs" title="Limpiar">
                            <i class="fas fa-redo"></i>
                        </a>
                    @endif
                </div>
            </form>

            <!-- Tabla de Histórico -->
            <div class="overflow-x-auto border border-slate-200/80 rounded-2xl">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/80 text-[11px] font-black uppercase tracking-wider text-slate-400">
                            <th class="py-3.5 px-5">OP</th>
                            <th class="py-3.5 px-5">Lote</th>
                            <th class="py-3.5 px-5">Producto</th>
                            <th class="py-3.5 px-5">Origen</th>
                            <th class="py-3.5 px-5">Ubicación Archivo Físico</th>
                            <th class="py-3.5 px-5 text-center">Estado</th>
                            <th class="py-3.5 px-5 text-right">Trazabilidad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                        @forelse($todosBatchRecords as $br)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-5 font-mono font-bold text-slate-900 whitespace-nowrap">
                                {{ $br->op ?? 'N/A' }}
                            </td>

                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <span class="font-mono font-black text-cyan-900 bg-cyan-50 px-2.5 py-0.5 rounded border border-cyan-200 text-xs inline-block">
                                    {{ $br->lote }}
                                </span>
                            </td>

                            <td class="py-3.5 px-5">
                                <div class="font-bold text-slate-800 leading-snug">
                                    {{ $br->producto_nombre }}
                                </div>
                            </td>

                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <span class="text-slate-600 font-semibold">
                                    {{ $br->maquilador->nombre ?? 'AUROFARMA' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-5 whitespace-nowrap">
                                @if($br->posicion_archivo_fisico)
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 inline-block">
                                            {{ $br->posicion_archivo_fisico }}
                                        </span>
                                        <a href="{{ route('consultas.br', ['buscar' => $br->lote]) }}" 
                                           target="_blank"
                                           class="text-cyan-600 hover:text-cyan-800 text-xs" 
                                           title="Abrir en Archivo Físico">
                                            <i class="fas fa-archive"></i>
                                        </a>
                                    </div>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Pendiente</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                @if($br->estado === 'BR CERRADO')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Liberado
                                    </span>
                                @elseif($br->estado === 'BR REVISION CALIDAD')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 border border-amber-200">
                                        En Revision
                                    </span>
                                @elseif($br->estado === 'BR ABIERTO')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-50 text-red-700 border border-red-200">
                                        Con Hallazgos
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                        {{ $br->estado }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-1.5">
                                    <a href="{{ route('genealogia.show', $br->lote) }}" 
                                       class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-600 hover:text-slate-900 bg-slate-100 rounded-lg transition-all"
                                       title="Ver Genealogía">
                                        Genealogía
                                    </a>
                                    <a href="{{ route('batch-records.pdf', ['lote' => $br->lote]) }}" 
                                       target="_blank"
                                       class="p-1.5 text-slate-400 hover:text-red-600 transition-all"
                                       title="Descargar PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 px-6 text-center text-slate-400">
                                No se encontraron expedientes con los criterios seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($todosBatchRecords instanceof \Illuminate\Pagination\LengthAwarePaginator && $todosBatchRecords->hasPages())
                <div class="pt-2">
                    {{ $todosBatchRecords->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL DICTAMEN DE CALIDAD -->
    <div x-show="modalDictamen" x-cloak style="display: none;" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.away="modalDictamen = false" 
             class="w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-200 p-6 sm:p-8 space-y-5 animate-scale-up">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black">
                        <i class="fas fa-shield-alt text-base text-cyan-400"></i>
                    </div>
                    <div>
                        <h3 class="font-display text-base font-black text-slate-900">
                            Emitir Dictamen de Calidad
                        </h3>
                        <p class="text-xs text-slate-500 font-mono">
                            OP: <span x-text="dictamenData.op"></span> · LOTE: <strong class="text-slate-900" x-text="dictamenData.lote"></strong>
                        </p>
                    </div>
                </div>
                <button type="button" @click="modalDictamen = false" class="text-slate-400 hover:text-slate-600 p-2">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <form :action="'/maquilas/' + dictamenData.id + '/revision-calidad'" method="POST" class="space-y-4">
                @csrf

                <!-- Verificación de Certificados -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-700 block">
                        Certificados de Análisis (COAs)
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fisicoquímico</label>
                            <select name="certificado_fisicoquimico" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white">
                                <option value="SI">SÍ (Conforme)</option>
                                <option value="NO">NO</option>
                                <option value="NO_APLICA">NO APLICA</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Microbiológico</label>
                            <select name="certificado_microbiologico" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white">
                                <option value="SI">SÍ (Conforme)</option>
                                <option value="NO">NO</option>
                                <option value="NO_APLICA">NO APLICA</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Endotoxinas</label>
                            <select name="certificado_endotoxinas" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white">
                                <option value="NO_APLICA">NO APLICA</option>
                                <option value="SI">SÍ (Conforme)</option>
                                <option value="NO">NO</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Decisión de Dictamen -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Dictamen *
                        </label>
                        <select name="estado_br_calidad" x-model="dictamenData.estado" required 
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-black"
                                :class="dictamenData.estado === 'CERRADO' ? 'text-emerald-700 bg-emerald-50 border-emerald-300' : 'text-amber-700 bg-amber-50 border-amber-300'">
                            <option value="CERRADO">CERRADO (Aprobado y Conforme)</option>
                            <option value="ABIERTO">ABIERTO (Observaciones Pendientes)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Fecha Liberación
                        </label>
                        <input type="date" name="fecha_liberacion_br" value="{{ date('Y-m-d') }}" 
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white">
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start space-x-3">
                    <input type="checkbox" name="liberar_br" value="1" id="check_liberar" checked
                           class="w-4 h-4 rounded text-slate-900 border-slate-300 focus:ring-slate-800 mt-0.5">
                    <label for="check_liberar" class="text-xs text-slate-700 cursor-pointer">
                        <strong class="text-slate-900 block font-bold">Liberar Lote para Distribución Comercial</strong>
                        El expediente cumple los parámetros de calidad del producto.
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Observaciones / Justificación Técnica
                    </label>
                    <textarea name="observaciones_calidad" rows="3" required
                              placeholder="Indique los resultados de la revisión y justificación del dictamen..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-slate-800 text-xs text-slate-800"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalDictamen = false" class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 text-xs font-black uppercase tracking-wider text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-md flex items-center space-x-2">
                        <i class="fas fa-signature text-xs text-cyan-400"></i>
                        <span>Firmar y Emitir Dictamen</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function calidadPortalApp() {
    return {
        activeTab: '{{ !empty($searchQuery) || !empty($filtroEstado) ? "ubicador" : "pendientes" }}',
        modalDictamen: false,
        dictamenData: {
            id: null,
            op: '',
            lote: '',
            producto: '',
            estado: 'CERRADO'
        },

        abrirModalDictamen(id, op, lote, producto) {
            this.dictamenData = {
                id: id,
                op: op,
                lote: lote,
                producto: producto,
                estado: 'CERRADO'
            };
            this.modalDictamen = true;
        }
    };
}
</script>
@endsection
