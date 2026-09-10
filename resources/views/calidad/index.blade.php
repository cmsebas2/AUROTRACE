@extends('layouts.app')

@section('header_title', 'Aseguramiento de Calidad · Dictamen y Custodia de Batch Records')

@section('content')
<div class="w-full space-y-8 max-w-7xl mx-auto pb-12" x-data="calidadPortalApp()">

    <!-- Header Principal del Portal de Calidad -->
    <div class="card-3d p-6 sm:p-8 border border-slate-200/80 bg-white relative overflow-hidden rounded-3xl shadow-xl">
        <div class="absolute -top-16 -right-16 w-72 h-72 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3.5 py-1 rounded-full bg-slate-900 text-cyan-300 font-mono text-[10px] font-black uppercase tracking-widest shadow-sm">
                        Aseguramiento de Calidad
                    </span>
                    <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-black border border-emerald-200/80 flex items-center shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                        Control & Dictamen Oficial
                    </span>
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Portal de Calidad & Liberación de Lotes
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 font-medium max-w-2xl leading-relaxed">
                    Espacio de control y auditoría exclusivo para el área de Calidad. Revise solicitudes pendientes de aprobación, consulte expedientes técnicos en el Archivo 3D y formalice dictámenes de liberación final.
                </p>
            </div>

            <!-- KPIs de Aseguramiento de Calidad -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 text-center w-full lg:w-auto">
                <div class="bg-gradient-to-br from-cyan-50/80 to-cyan-100/30 p-4 rounded-2xl border border-cyan-200/80 min-w-[120px] shadow-sm flex flex-col justify-between">
                    <span class="text-[9px] font-black uppercase tracking-wider text-cyan-700 block">Pendientes QA</span>
                    <span class="font-display text-2xl font-black text-cyan-900 my-1">{{ number_format($kpis['pendientes_calidad']) }}</span>
                    <span class="text-[9px] text-cyan-600 font-bold block">Por Dictaminar</span>
                </div>
                <div class="bg-gradient-to-br from-indigo-50/80 to-indigo-100/30 p-4 rounded-2xl border border-indigo-200/80 min-w-[120px] shadow-sm flex flex-col justify-between">
                    <span class="text-[9px] font-black uppercase tracking-wider text-indigo-700 block">En Revisión DT</span>
                    <span class="font-display text-2xl font-black text-indigo-900 my-1">{{ number_format($kpis['en_revision_dt']) }}</span>
                    <span class="text-[9px] text-indigo-600 font-bold block">Paso Previo</span>
                </div>
                <div class="bg-gradient-to-br from-emerald-50/80 to-emerald-100/30 p-4 rounded-2xl border border-emerald-200/80 min-w-[120px] shadow-sm flex flex-col justify-between">
                    <span class="text-[9px] font-black uppercase tracking-wider text-emerald-700 block">Lotes Liberados</span>
                    <span class="font-display text-2xl font-black text-emerald-900 my-1">{{ number_format($kpis['liberados_total']) }}</span>
                    <span class="text-[9px] text-emerald-600 font-bold block">Expedientes Cerrados</span>
                </div>
                <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 p-4 rounded-2xl border border-slate-200 min-w-[120px] shadow-sm flex flex-col justify-between">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500 block">Archivo RACK 1</span>
                    <span class="font-display text-2xl font-black text-slate-800 my-1">{{ number_format($kpis['total_archivados_rack']) }}</span>
                    <span class="text-[9px] text-slate-400 font-bold block">Con Ubicación 3D</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas Activas de Aprobación Pendiente -->
    @if($solicitudesPendientes->count() > 0)
    <div class="card-3d p-5 bg-gradient-to-r from-amber-500/10 via-cyan-500/10 to-transparent border-2 border-cyan-400/80 rounded-2xl shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-in">
        <div class="flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-2xl bg-cyan-600 text-white flex items-center justify-center font-black shadow-md flex-shrink-0">
                <i class="fas fa-clipboard-check text-xl"></i>
            </div>
            <div>
                <h3 class="font-display text-sm font-black text-slate-900">
                    ¡Atención! Tiene {{ $solicitudesPendientes->count() }} {{ $solicitudesPendientes->count() === 1 ? 'solicitud pendiente' : 'solicitudes pendientes' }} de Aprobación y Dictamen por Calidad
                </h3>
                <p class="text-xs text-slate-600 mt-0.5">
                    Estas órdenes cuentan con revisión previa y expediente en custodia. Requieren dictamen formal para su liberación final.
                </p>
            </div>
        </div>
        <a href="#seccion-pendientes" class="px-5 py-2.5 bg-slate-900 text-cyan-300 font-black text-xs uppercase tracking-wider rounded-xl hover:bg-slate-800 shadow-md text-center flex items-center justify-center space-x-2 flex-shrink-0 transition-all">
            <span>Revisar Solicitudes</span>
            <i class="fas fa-arrow-down text-xs"></i>
        </a>
    </div>
    @endif

    <!-- ========================================================================= -->
    <!-- SECCIÓN 1: SOLICITUDES PENDIENTES DE APROBACIÓN POR CALIDAD (QA) -->
    <!-- ========================================================================= -->
    <div id="seccion-pendientes" class="card-3d border border-slate-200/80 bg-white overflow-hidden shadow-xl rounded-3xl">
        <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/70">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-2xl bg-cyan-600 text-white flex items-center justify-center font-black shadow-sm">
                    <i class="fas fa-shield-alt text-base"></i>
                </div>
                <div>
                    <h2 class="font-display text-base font-black text-slate-900 tracking-tight">
                        Solicitudes Pendientes por Dictamen Calidad
                    </h2>
                    <span class="text-xs text-slate-500 font-medium">
                        Lotes con estado <strong class="text-cyan-800 font-mono">BR REVISION CALIDAD</strong> esperando decisión final.
                    </span>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <span class="px-3.5 py-1.5 rounded-xl text-xs font-mono font-black {{ $solicitudesPendientes->count() > 0 ? 'bg-cyan-100 text-cyan-900 border border-cyan-300' : 'bg-slate-100 text-slate-600' }}">
                    {{ $solicitudesPendientes->count() }} Pendientes
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/60 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">OP / Identificación</th>
                        <th class="px-6 py-4">Lote & Producto</th>
                        <th class="px-6 py-4">Maquilador / Origen</th>
                        <th class="px-6 py-4 text-center">Fabricado / Rendimiento</th>
                        <th class="px-6 py-4">Revisión DT</th>
                        <th class="px-6 py-4">Ubicación Archivo 3D</th>
                        <th class="px-6 py-4 text-right">Acción Calidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($solicitudesPendientes as $sol)
                    <tr class="hover:bg-cyan-50/30 transition-colors">
                        <!-- OP / Identificación -->
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <span class="font-mono text-xs font-black text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200 inline-block shadow-2xs">
                                    OP: {{ $sol->op ?? 'N/A' }}
                                </span>
                                @if($sol->numero_odm)
                                    <span class="text-[10px] text-slate-500 block font-mono">ODM: {{ $sol->numero_odm }}</span>
                                @endif
                            </div>
                        </td>

                        <!-- Lote & Producto -->
                        <td class="px-6 py-4">
                            <div class="space-y-1 max-w-xs">
                                <span class="font-mono text-xs font-black text-cyan-900 bg-cyan-50 px-2.5 py-0.5 rounded-lg border border-cyan-200 inline-block">
                                    LOTE: {{ $sol->lote }}
                                </span>
                                <div class="font-black text-slate-900 text-xs break-words" title="{{ $sol->producto_nombre }}">
                                    {{ $sol->producto_nombre }}
                                </div>
                                @if($sol->items && $sol->items->count() > 0)
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($sol->items as $item)
                                            <span class="text-[9px] font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                                {{ $item->presentacion ?? $item->codigo_item }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- Maquilador -->
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200 inline-block text-[11px]">
                                {{ $sol->maquilador->nombre ?? 'MAQUILA EXTERNA' }}
                            </span>
                        </td>

                        <!-- Total Fabricado / Yield -->
                        <td class="px-6 py-4 text-center">
                            <div class="space-y-1">
                                <span class="font-mono font-black text-slate-900 text-xs block">
                                    {{ number_format($sol->total_producto_terminado_fabricado ?? 0) }} u
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-black inline-block {{ ($sol->rendimiento_real ?? 100) >= 95 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    Rend: {{ number_format($sol->rendimiento_real ?? 100, 1) }}%
                                </span>
                            </div>
                        </td>

                        <!-- Revisión DT -->
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase inline-block {{ $sol->estado_br_dt === 'CERRADO' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    DT: {{ $sol->estado_br_dt ?? 'PENDIENTE' }}
                                </span>
                                @if($sol->comentario_dt)
                                    <p class="text-[10px] text-slate-500 italic truncate max-w-[180px]" title="{{ $sol->comentario_dt }}">
                                        "{{ $sol->comentario_dt }}"
                                    </p>
                                @endif
                            </div>
                        </td>

                        <!-- Ubicación Archivo 3D -->
                        <td class="px-6 py-4">
                            @if($sol->posicion_archivo_fisico)
                                <div class="space-y-1">
                                    <span class="font-mono text-[10px] font-black text-cyan-900 bg-cyan-50 px-2.5 py-1 rounded-lg border border-cyan-200 block truncate max-w-[180px]" title="{{ $sol->posicion_archivo_fisico }}">
                                        {{ $sol->posicion_archivo_fisico }}
                                    </span>
                                    <a href="{{ route('consultas.br', ['buscar' => $sol->lote]) }}" 
                                       target="_blank"
                                       class="text-[10px] font-bold text-cyan-600 hover:text-cyan-800 flex items-center space-x-1">
                                        <i class="fas fa-cube text-[9px]"></i>
                                        <span>Ubicar en 3D &rarr;</span>
                                    </a>
                                </div>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-400">
                                    Sin asignar aún
                                </span>
                            @endif
                        </td>

                        <!-- Acción Calidad (Emitir Dictamen) -->
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <button type="button" 
                                        @click="abrirModalDictamen({{ $sol->id }}, '{{ $sol->op }}', '{{ $sol->lote }}', '{{ addslashes($sol->producto_nombre) }}')"
                                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-cyan-600 to-teal-600 shadow-md hover:from-cyan-700 hover:to-teal-700 transition-all flex items-center space-x-2">
                                    <i class="fas fa-check-circle text-xs"></i>
                                    <span>Emitir Dictamen</span>
                                </button>
                                <a href="{{ route('maquila.show', $sol->id) }}" 
                                   class="p-2 text-slate-500 hover:text-cyan-700 hover:bg-cyan-50 rounded-xl transition-all border border-slate-200" 
                                   title="Ver Detalle Completo">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h4 class="font-display font-bold text-slate-800 text-sm">¡Al día! No hay solicitudes pendientes de aprobación por Calidad</h4>
                                <p class="text-xs text-slate-400">Todas las órdenes con revisión técnica han sido evaluadas y dictaminadas.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECCIÓN 2: UBICADOR CENTRAL DE BATCH RECORDS EN ARCHIVO FÍSICO (RACK 1) -->
    <!-- ========================================================================= -->
    <div class="card-3d border border-slate-200/80 bg-white overflow-hidden shadow-xl rounded-3xl p-6 space-y-6">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-0.5 rounded-full bg-slate-900 text-cyan-300 font-mono text-[10px] font-black uppercase tracking-wider">
                        Custodia & Trazabilidad
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        Modo Solo Lectura
                    </span>
                </div>
                <h2 class="font-display text-xl font-black text-slate-900 tracking-tight">
                    Ubicador General de Batch Records & Expedientes Físicos
                </h2>
                <p class="text-xs text-slate-500 font-medium">
                    Consulte cualquier lote o expediente técnico, verifique su posición exacta en la estantería RACK 1 (5 niveles y doble fondo) y acceda a la vista 3D.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <a href="{{ route('consultas.br') }}" 
                   class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-slate-900 hover:bg-slate-800 shadow-md transition-all flex items-center space-x-2">
                    <i class="fas fa-cubes text-cyan-300"></i>
                    <span>Abrir Sala de Archivo 3D</span>
                </a>
            </div>
        </div>

        <!-- Barra de Búsqueda y Filtros de Estado -->
        <form method="GET" action="{{ route('calidad.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="relative sm:col-span-6 w-full">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fas fa-search"></i>
                </div>
                <input type="text" name="buscar" value="{{ $searchQuery }}" 
                       placeholder="Buscar por Lote (ej: 301AN01), OP, Producto, Maquilador..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800 uppercase tracking-wide">
            </div>

            <div class="sm:col-span-4 w-full">
                <select name="filtro_estado" onchange="this.form.submit()" 
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white">
                    <option value="">Todos los Estados</option>
                    <option value="CON_UBICACION" {{ $filtroEstado === 'CON_UBICACION' ? 'selected' : '' }}>● Con Ubicación en RACK 1</option>
                    <option value="SIN_UBICACION" {{ $filtroEstado === 'SIN_UBICACION' ? 'selected' : '' }}>○ Sin Ubicación Física</option>
                    <option value="BR REVISION CALIDAD" {{ $filtroEstado === 'BR REVISION CALIDAD' ? 'selected' : '' }}>Pendiente Dictamen Calidad</option>
                    <option value="BR CERRADO" {{ $filtroEstado === 'BR CERRADO' ? 'selected' : '' }}>Lotes Liberados (BR Cerrado)</option>
                    <option value="BR ABIERTO" {{ $filtroEstado === 'BR ABIERTO' ? 'selected' : '' }}>Lotes Observados (BR Abierto)</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex items-center space-x-2">
                <button type="submit" 
                        class="w-full px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-cyan-600 hover:bg-cyan-700 shadow-md transition-all">
                    Filtrar
                </button>
                @if(!empty($searchQuery) || !empty($filtroEstado))
                    <a href="{{ route('calidad.index') }}" class="p-2.5 text-slate-400 hover:text-slate-700 text-xs font-bold" title="Limpiar Filtros">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>

        <!-- Tabla de Batch Records (Solo Lectura con Localizador) -->
        <div class="overflow-x-auto border border-slate-200 rounded-2xl">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">OP / ODM</th>
                        <th class="px-5 py-3">Lote</th>
                        <th class="px-5 py-3">Producto / Presentación</th>
                        <th class="px-5 py-3">Maquilador</th>
                        <th class="px-5 py-3">Posición Archivo Físico (RACK 1)</th>
                        <th class="px-5 py-3 text-center">Estado BR</th>
                        <th class="px-5 py-3 text-right">Consultas & Trazabilidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($todosBatchRecords as $br)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <!-- OP / ODM -->
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs font-bold text-slate-900">
                                {{ $br->op ?? 'N/A' }}
                            </span>
                            @if($br->numero_odm)
                                <span class="text-[10px] text-slate-400 block font-mono">{{ $br->numero_odm }}</span>
                            @endif
                        </td>

                        <!-- Lote -->
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs font-black text-slate-900 bg-cyan-50 px-2.5 py-0.5 rounded border border-cyan-200 inline-block">
                                {{ $br->lote }}
                            </span>
                        </td>

                        <!-- Producto -->
                        <td class="px-5 py-3">
                            <div class="font-black text-slate-800 break-words max-w-xs" title="{{ $br->producto_nombre }}">
                                {{ $br->producto_nombre }}
                            </div>
                            @if($br->items && $br->items->first())
                                <span class="text-[10px] text-slate-400 block">
                                    {{ $br->items->first()->presentacion ?? '' }}
                                </span>
                            @endif
                        </td>

                        <!-- Maquilador -->
                        <td class="px-5 py-3">
                            <span class="text-slate-700 font-bold">
                                {{ $br->maquilador->nombre ?? 'AUROFARMA' }}
                            </span>
                        </td>

                        <!-- Ubicación Física RACK 1 -->
                        <td class="px-5 py-3">
                            @if($br->posicion_archivo_fisico)
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono text-[10px] font-black text-emerald-900 bg-emerald-50 px-2 py-1 rounded-lg border border-emerald-200 flex items-center truncate max-w-[220px]" title="{{ $br->posicion_archivo_fisico }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 flex-shrink-0"></span>
                                        {{ $br->posicion_archivo_fisico }}
                                    </span>
                                    <a href="{{ route('consultas.br', ['buscar' => $br->lote]) }}" 
                                       target="_blank"
                                       class="p-1.5 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 rounded-lg flex-shrink-0" 
                                       title="Ver en Sala 3D">
                                        <i class="fas fa-crosshairs text-xs"></i>
                                    </a>
                                </div>
                            @else
                                <span class="text-[10px] text-slate-400 italic">
                                    Pendiente de llegada a bodega
                                </span>
                            @endif
                        </td>

                        <!-- Estado BR -->
                        <td class="px-5 py-3 text-center">
                            @if($br->estado === 'BR CERRADO')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Liberado (Cerrado)
                                </span>
                            @elseif($br->estado === 'BR REVISION CALIDAD')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-cyan-50 text-cyan-700 border border-cyan-200 animate-pulse">
                                    Revisión QA
                                </span>
                            @elseif($br->estado === 'BR ABIERTO')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 border border-amber-200">
                                    Con Hallazgos
                                </span>
                            @elseif($br->estado === 'BR REVISION DT')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    Revisión DT
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                    {{ $br->estado }}
                                </span>
                            @endif
                        </td>

                        <!-- Consultas & Trazabilidad (Solo lectura) -->
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                <a href="{{ route('genealogia.show', $br->lote) }}" 
                                   class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-600 hover:text-cyan-700 bg-white hover:bg-cyan-50 rounded-lg border border-slate-200 transition-all"
                                   title="Ver Genealogía del Lote">
                                    Genealogía
                                </a>
                                <a href="{{ route('batch-records.pdf', ['lote' => $br->lote]) }}" 
                                   target="_blank"
                                   class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-all"
                                   title="Descargar Expediente PDF">
                                    <i class="fas fa-file-pdf text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                            No se encontraron registros con el criterio de búsqueda aplicado.
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

    <!-- ========================================================================= -->
    <!-- MODAL INTEGRADO: DICTAMEN DE CALIDAD / LIBERACIÓN DE LOTE -->
    <!-- ========================================================================= -->
    <div x-show="modalDictamen" x-cloak style="display: none;" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm p-4">
        <div @click.away="modalDictamen = false" 
             class="w-full max-w-xl card-3d p-6 sm:p-8 bg-white border border-slate-200 rounded-3xl shadow-2xl space-y-5 animate-scale-up">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 rounded-2xl bg-cyan-600 text-white flex items-center justify-center font-black shadow-md">
                        <i class="fas fa-shield-alt text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-display text-base font-black text-slate-900">
                            Dictamen Aseguramiento de Calidad
                        </h3>
                        <p class="text-xs text-slate-500 font-mono">
                            OP: <span x-text="dictamenData.op"></span> · LOTE: <strong class="text-cyan-900" x-text="dictamenData.lote"></strong>
                        </p>
                    </div>
                </div>
                <button type="button" @click="modalDictamen = false" class="text-slate-400 hover:text-slate-600 p-2">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <form :action="'/maquilas/' + dictamenData.id + '/revision-calidad'" method="POST" class="space-y-4">
                @csrf

                <!-- Verificación de Certificados de Análisis (COAs) -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-700 block">
                        1. Verificación de Certificados de Calidad (COAs)
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase mb-1">Fisicoquímico</label>
                            <select name="certificado_fisicoquimico" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white">
                                <option value="SI">SÍ (Conforme)</option>
                                <option value="NO">NO</option>
                                <option value="NO_APLICA">NO APLICA</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase mb-1">Microbiológico</label>
                            <select name="certificado_microbiologico" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white">
                                <option value="SI">SÍ (Conforme)</option>
                                <option value="NO">NO</option>
                                <option value="NO_APLICA">NO APLICA</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase mb-1">Endotoxinas</label>
                            <select name="certificado_endotoxinas" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white">
                                <option value="NO_APLICA">NO APLICA</option>
                                <option value="SI">SÍ (Conforme)</option>
                                <option value="NO">NO</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Decisión de Dictamen y Cierre -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                            Dictamen de Calidad *
                        </label>
                        <select name="estado_br_calidad" x-model="dictamenData.estado" required 
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs font-black"
                                :class="dictamenData.estado === 'CERRADO' ? 'text-emerald-700 bg-emerald-50 border-emerald-300' : 'text-amber-700 bg-amber-50 border-amber-300'">
                            <option value="CERRADO">CERRADO (Aprobado y Conforme)</option>
                            <option value="ABIERTO">ABIERTO (Observaciones Pendientes)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                            Fecha de Liberación
                        </label>
                        <input type="date" name="fecha_liberacion_br" value="{{ date('Y-m-d') }}" 
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white">
                    </div>
                </div>

                <!-- Check Liberación Formal de Lote -->
                <div class="p-3.5 rounded-xl bg-cyan-50 border border-cyan-200 flex items-start space-x-3">
                    <input type="checkbox" name="liberar_br" value="1" id="check_liberar" checked
                           class="w-4 h-4 rounded text-cyan-600 border-slate-300 focus:ring-cyan-500 mt-0.5">
                    <label for="check_liberar" class="text-xs text-slate-700 cursor-pointer">
                        <strong class="text-cyan-950 block">Liberar Lote para Distribución Comercial</strong>
                        Confirmo que el expediente cumple los parámetros de calidad del producto.
                    </label>
                </div>

                <!-- Observaciones de Calidad -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                        Observaciones / Justificación de Aseguramiento de Calidad
                    </label>
                    <textarea name="observaciones_calidad" rows="3" required
                              placeholder="Indique los resultados de la revisión, trazabilidad de análisis y autorización final..."
                              class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs text-slate-800"></textarea>
                </div>

                <!-- Footer & Confirmación -->
                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalDictamen = false" class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 rounded-xl shadow-md flex items-center space-x-2">
                        <i class="fas fa-signature text-xs"></i>
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
