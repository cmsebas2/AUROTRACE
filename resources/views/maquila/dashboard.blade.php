@extends('layouts.app')

@section('header_title', 'Torre de Control - Maquilas Externas & Batch Records')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="maquilaDashboardApp()">

    <!-- Header y Acciones de Cabecera -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-3 h-8 bg-gradient-to-b from-cyan-500 to-aurofarma rounded-full shadow-3d-cyan"></div>
            <div>
                <h1 class="font-display text-2xl font-black text-slate-800 tracking-tight">Control de Maquilas Externas & Batch Records</h1>
                <p class="text-xs text-slate-500 font-medium">Trazabilidad forense de lotes externos, rendimientos y custodia física de expedientes</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Botón Maqueta 3D -->
            <button @click="abrirMaqueta3D(null)" 
                    class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-cyan-800 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 shadow-sm transition-all transform hover:-translate-y-0.5">
                <i class="fas fa-cubes mr-2 text-cyan-600"></i>
                Maqueta 3D Archivo
            </button>

            <!-- Botón Nueva OP -->
            <a href="{{ route('maquila.create') }}" 
               class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-cyan-500 via-[#005889] to-[#003B5C] shadow-3d-button hover:shadow-3d-cyan transition-all transform hover:-translate-y-0.5">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Nueva OP Maquila
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm flex items-center space-x-3 animate-fade-in">
            <div class="p-1 rounded-lg bg-emerald-100 text-emerald-600 flex-shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
            </div>
            <div class="text-xs font-bold">{{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-sm flex items-center space-x-3">
            <div class="text-xs font-bold">{{ session('error') }}</div>
        </div>
    @endif

    <!-- 4 Tarjetas de Métricas 3D (KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- KPI 1: En Producción -->
        <div class="card-3d p-5 border border-slate-200/80 bg-white relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-600">Piso de Planta</span>
                    <h3 class="font-display text-2xl font-black text-slate-900 mt-0.5">{{ $opsEnProduccion }}</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-1">OPs en fabricación externa</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shadow-sm group-hover:scale-110 transition-transform">
                    <i class="fas fa-industry text-lg"></i>
                </div>
            </div>
            <div class="mt-3 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500 rounded-full" style="width: {{ $totalOps > 0 ? ($opsEnProduccion / $totalOps) * 100 : 0 }}%"></div>
            </div>
        </div>

        <!-- KPI 2: Pendiente BR -->
        <div class="card-3d p-5 border border-slate-200/80 bg-white relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-purple-600">Expedientes</span>
                    <h3 class="font-display text-2xl font-black text-slate-900 mt-0.5">{{ $opsBrPendiente }}</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-1">Llegada de BR pendiente</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 flex items-center justify-center text-purple-600 shadow-sm group-hover:scale-110 transition-transform">
                    <i class="fas fa-clock text-lg"></i>
                </div>
            </div>
            <div class="mt-3 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-purple-500 rounded-full" style="width: {{ $totalOps > 0 ? ($opsBrPendiente / $totalOps) * 100 : 0 }}%"></div>
            </div>
        </div>

        <!-- KPI 3: En Revisión DT / QA -->
        <div class="card-3d p-5 border border-slate-200/80 bg-white relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-cyan-600">Revisión Técnica</span>
                    <h3 class="font-display text-2xl font-black text-slate-900 mt-0.5">{{ $opsEnRevision }}</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-1">En dictamen DT o Calidad</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-cyan-50 border border-cyan-200 flex items-center justify-center text-cyan-600 shadow-sm group-hover:scale-110 transition-transform">
                    <i class="fas fa-microscope text-lg"></i>
                </div>
            </div>
            <div class="mt-3 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-cyan-500 rounded-full" style="width: {{ $totalOps > 0 ? ($opsEnRevision / $totalOps) * 100 : 0 }}%"></div>
            </div>
        </div>

        <!-- KPI 4: BR Cerrado / Custodia -->
        <div class="card-3d p-5 border border-slate-200/80 bg-white relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600">Custodia Física</span>
                    <h3 class="font-display text-2xl font-black text-slate-900 mt-0.5">{{ $opsBrCerrado }}</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-1">Batch Records cerrados</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shadow-sm group-hover:scale-110 transition-transform">
                    <i class="fas fa-check-double text-lg"></i>
                </div>
            </div>
            <div class="mt-3 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $totalOps > 0 ? ($opsBrCerrado / $totalOps) * 100 : 0 }}%"></div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="card-3d p-4 border border-slate-200/80 bg-white flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Pestañas Rápidas por Estado del Ciclo -->
        <div class="flex flex-wrap items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
            @php
                $estadosFiltro = [
                    'todos' => 'Todas (' . $orders->count() . ')',
                    'creada' => 'OP Creada',
                    'produccion' => 'En Producción',
                    'br_pendiente' => 'Pendiente BR',
                    'revision' => 'Revisión DT/QA',
                    'cerrado' => 'BR Cerrado',
                    'abierto' => 'BR Abierto',
                ];
            @endphp

            @foreach($estadosFiltro as $key => $label)
                <a href="{{ route('maquila.index', array_merge(request()->query(), ['estado' => $key])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ ($statusFilter == $key || (!$statusFilter && $key == 'todos')) ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- Buscador Inteligente -->
        <form method="GET" action="{{ route('maquila.index') }}" class="flex items-center space-x-2">
            @if(request('estado'))
                <input type="hidden" name="estado" value="{{ request('estado') }}">
            @endif
            <div class="relative w-full sm:w-64">
                <input type="text" name="buscar" value="{{ $search }}" placeholder="Buscar OP, Lote, ODM, Ítem..."
                       class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 placeholder-slate-400 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
            </div>
            <button type="submit" class="px-3 py-1.5 bg-cyan-600 text-white rounded-xl text-xs font-black uppercase tracking-wider hover:bg-cyan-700">
                Filtrar
            </button>
            @if($search)
                <a href="{{ route('maquila.index', ['estado' => $statusFilter]) }}" class="text-xs text-slate-400 hover:text-red-600">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </form>
    </div>

    <!-- Tabla Maestra de Órdenes de Maquila -->
    <div class="card-3d overflow-hidden border border-slate-200/80 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left">
                <thead>
                    <tr class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white text-[10px] font-black uppercase tracking-wider">
                        <th class="px-5 py-4 text-cyan-300"># Pre-Orden / OP</th>
                        <th class="px-5 py-4">Producto & Lote</th>
                        <th class="px-5 py-4">Maquilador</th>
                        <th class="px-5 py-4">Plan / Avance</th>
                        <th class="px-5 py-4">Estado Ciclo</th>
                        <th class="px-5 py-4 text-right">Acción Requerida</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-xs">
                    @forelse($orders as $op)
                    <tr class="hover:bg-cyan-50/30 transition-colors group">
                        
                        <!-- 1. Pre-Orden & OP & ODM -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="font-mono text-xs font-black text-slate-900">{{ $op->pre_orden ?? 'PL-XX-G' }}</span>
                                <span class="font-display text-sm font-black text-cyan-800 mt-0.5">OP: {{ $op->op }}</span>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $op->numero_odm }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- 2. Producto & Lote Placa 3D -->
                        <td class="px-5 py-4">
                            <div class="font-bold text-slate-900 leading-snug">{{ $op->producto_nombre }}</div>
                            <div class="text-[10px] text-slate-500 font-medium mt-0.5">
                                Forma: <strong class="text-slate-700">{{ $op->forma_farmaceutica ?? 'Polvo Oral' }}</strong>
                            </div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-cyan-50 text-cyan-800 border border-cyan-300 shadow-3d-badge">
                                    LOTE: {{ $op->lote }}
                                </span>
                                @if($op->fecha_destruccion_br)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-300" title="Retención de Batch Record: 1 año post-vencimiento según ICA (Destrucción: {{ $op->fecha_destruccion_br }})">
                                        <i class="fas fa-calendar-times text-[9px] mr-1 text-amber-600"></i> Destr. BR: {{ $op->fecha_destruccion_br }}
                                    </span>
                                @endif
                                @if($op->posicion_archivo_fisico)
                                    <button @click="abrirMaqueta3D('{{ $op->posicion_archivo_fisico }}')" 
                                            class="text-[9px] font-bold text-slate-500 hover:text-cyan-700 bg-slate-100 hover:bg-cyan-50 px-2 py-0.5 rounded border border-slate-200 transition-colors flex items-center space-x-1"
                                            title="Ver ubicación en el archivo 3D">
                                        <i class="fas fa-cube text-[9px] text-cyan-600"></i>
                                        <span>{{ $op->posicion_archivo_fisico }}</span>
                                    </button>
                                @endif
                            </div>
                        </td>

                        <!-- 3. Maquilador -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="font-bold text-slate-800">{{ $op->maquilador->nombre ?? 'Sin Maquilador' }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5 font-medium">
                                Certificado BPM: <span class="font-bold {{ ($op->maquilador?->estado_certificado_ica ?? '') === 'vigente' ? 'text-emerald-600' : 'text-amber-600' }}">{{ strtoupper($op->maquilador?->estado_certificado_ica ?? 'N/A') }}</span>
                            </div>
                        </td>

                        <!-- 4. Plan de Producción y Avance Cilíndrico 3D -->
                        <td class="px-5 py-4">
                            <div class="space-y-1">
                                <div class="flex justify-between text-[11px] font-bold">
                                    <span class="text-slate-500">Progreso:</span>
                                    <span class="text-cyan-700 font-mono">{{ $op->porcentaje_avance_global }}%</span>
                                </div>
                                <div class="w-36 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                    <div class="h-full bg-gradient-to-r from-[#005889] to-[#06B6D4] rounded-full transition-all" 
                                         style="width: {{ min(100, $op->porcentaje_avance_global) }}%"></div>
                                </div>
                                <div class="text-[10px] text-slate-400 font-medium pt-0.5">
                                    {{ number_format($op->total_recibido, 2) }} / {{ number_format($op->tamano_lote > 0 ? $op->tamano_lote : $op->total_programado, 2) }}
                                </div>
                            </div>
                        </td>

                        <!-- 5. Estado del Ciclo de Vida -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border {{ $op->estado_badge_class }} inline-flex items-center">
                                @if($op->estado_label === 'OP CREADA')
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                                @elseif($op->estado_label === 'OP EN PRODUCCION' || $op->estado_label === 'RECEPCIÓN PARCIAL')
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping mr-1.5"></span>
                                @elseif($op->estado_label === 'BR CERRADO')
                                    <i class="fas fa-check-circle text-emerald-600 mr-1.5"></i>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse mr-1.5"></span>
                                @endif
                                {{ $op->estado_label }}
                            </span>

                            @if($op->rendimiento_real)
                                <div class="text-[10px] font-black text-cyan-800 mt-1">
                                    Yield Real: {{ $op->rendimiento_real }}%
                                </div>
                            @endif
                        </td>

                        <!-- 6. Acciones Dinámicas Según Estado Actual -->
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-2">
                                
                                <!-- Caso 1: OP CREADA -> Enviar a Maquilador -->
                                @if($op->estado === 'OP CREADA' || $op->estado === 'borrador')
                                    <button @click="abrirModalEnviar({{ $op->id }}, '{{ $op->op }}')" 
                                            class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-amber-500 to-orange-600 shadow-3d-button hover:shadow-[0_4px_12px_rgba(242,142,19,0.4)] transition-all">
                                        Enviar a Maquilador
                                    </button>

                                <!-- Caso 2: OP EN PRODUCCIÓN o RECEPCIÓN PARCIAL -> Ingresar Producto -->
                                @elseif(in_array($op->estado, ['OP EN PRODUCCION', 'enviada_a_maquila', 'en_proceso', 'entrega_parcial']))
                                    <a href="{{ route('maquila.recepcion', $op->id) }}" 
                                       class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-[#005889] to-[#06B6D4] shadow-3d-button hover:shadow-3d-cyan transition-all flex items-center space-x-1">
                                        <i class="fas fa-truck-loading text-xs"></i>
                                        <span>Ingresar Producto</span>
                                    </a>

                                <!-- Caso 3: OP TERMINADA - BR PENDIENTE -> Registrar Llegada BR -->
                                @elseif($op->estado === 'OP TERMINADA - BR PENDIENTE' || $op->estado === 'completada_pendiente_liquidacion')
                                    <button @click="abrirModalLlegadaBr({{ $op->id }}, '{{ $op->op }}', {{ $op->tamano_lote > 0 ? $op->tamano_lote : $op->total_programado }}, '{{ $op->fecha_destruccion_br }}')" 
                                            class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-purple-600 to-indigo-600 shadow-3d-button hover:shadow-purple-500/40 transition-all flex items-center space-x-1">
                                        <i class="fas fa-file-medical text-xs"></i>
                                        <span>Llegada de BR</span>
                                    </button>

                                <!-- Caso 4: BR REVISION DT -> Revisión DT y Producción -->
                                @elseif($op->estado === 'BR REVISION DT')
                                    <button @click="abrirModalRevisionDt({{ $op->id }}, '{{ $op->op }}')" 
                                            class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-indigo-600 to-blue-700 shadow-3d-button hover:shadow-indigo-500/40 transition-all flex items-center space-x-1">
                                        <i class="fas fa-user-check text-xs"></i>
                                        <span>Revisión DT</span>
                                    </button>

                                <!-- Caso 5: BR REVISION CALIDAD -> Revisión Calidad (QA) -->
                                @elseif($op->estado === 'BR REVISION CALIDAD')
                                    <button @click="abrirModalRevisionQa({{ $op->id }}, '{{ $op->op }}')" 
                                            class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-cyan-600 to-teal-600 shadow-3d-button hover:shadow-cyan-500/40 transition-all flex items-center space-x-1">
                                        <i class="fas fa-shield-alt text-xs"></i>
                                        <span>Revisión QA</span>
                                    </button>

                                <!-- Caso 6: BR CERRADO o BR ABIERTO -> Ver Radar 360 -->
                                @else
                                    <a href="{{ route('maquila.show', $op->id) }}" 
                                       class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-all">
                                        Trazabilidad 360°
                                    </a>
                                @endif

                                <!-- Botón Detalle / Radar -->
                                <a href="{{ route('maquila.show', $op->id) }}" 
                                   class="p-2 text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-xl transition-colors border border-transparent hover:border-cyan-200" 
                                   title="Ver Radar Completo del Lote">
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-300 mb-3">
                                    <i class="fas fa-folder-open text-2xl"></i>
                                </div>
                                <h4 class="font-display font-bold text-slate-700 text-sm">No se encontraron órdenes de maquila</h4>
                                <p class="text-xs text-slate-400 mt-1">Cree una nueva orden de producción para iniciar el ciclo.</p>
                                <a href="{{ route('maquila.create') }}" class="mt-4 text-xs font-black text-cyan-600 hover:underline uppercase tracking-wider">
                                    + Crear primera OP Maquila
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: Enviar OP a Maquilador (Paso 2) -->
    <div x-show="modalEnviar" x-cloak style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.away="modalEnviar = false" 
             class="w-full max-w-md card-3d p-6 bg-white border border-slate-200 rounded-3xl shadow-2xl space-y-4">
            
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                    <i class="fas fa-paper-plane text-base"></i>
                </div>
                <div>
                    <h3 class="font-display text-base font-black text-slate-900">Enviar OP a Maquilador</h3>
                    <p class="text-xs text-slate-500">OP: <strong x-text="activeOpNumber"></strong></p>
                </div>
            </div>

            <form :action="'/maquilas/' + activeOpId + '/enviar-maquilador'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha de Envío a Maquila <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha_envio_maquila" required value="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold text-slate-800">
                </div>

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800">
                    Al confirmar, el estado cambiará a <strong>OP EN PRODUCCION</strong>.
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="modalEnviar = false" 
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-amber-600 hover:bg-amber-700 shadow-md">
                        Confirmar Envío
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Llegada de Batch Record & Archivo Físico (Paso 4) -->
    <div x-show="modalLlegadaBr" x-cloak style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.away="modalLlegadaBr = false" 
             class="w-full max-w-lg card-3d p-6 bg-white border border-slate-200 rounded-3xl shadow-2xl space-y-4">
            
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                    <i class="fas fa-file-medical text-base"></i>
                </div>
                <div>
                    <h3 class="font-display text-base font-black text-slate-900">Registrar Llegada del Batch Record</h3>
                    <p class="text-xs text-slate-500">OP: <strong x-text="activeOpNumber"></strong></p>
                </div>
            </div>

            <form :action="'/maquilas/' + activeOpId + '/llegada-br'" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                            Fecha Llegada BR <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="fecha_llegada_br" required value="{{ date('Y-m-d') }}"
                               class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                            Total PT Fabricado <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.001" min="0.001" name="total_producto_terminado_fabricado" 
                               :value="activeTamanoLote" required placeholder="Ej: 500.00"
                               class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-mono font-black text-slate-900">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Posición en Archivo Físico <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="posicion_archivo_fisico" id="posicion_archivo_fisico" required 
                           placeholder="Ej: ESTANTE A · NIVEL 03 · CAJA 05"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold uppercase text-cyan-900">
                    <span class="text-[10px] text-slate-400 mt-1 block">Ubicación física donde se archivará la carpeta física del Batch Record.</span>
                </div>

                <div class="p-3 bg-amber-50/90 rounded-xl border border-amber-200 text-xs text-amber-900 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-calendar-times text-amber-600"></i>
                        <span>Retención ICA (Batch Record):</span>
                    </div>
                    <span class="font-mono font-black text-amber-950 bg-amber-200/70 px-2.5 py-0.5 rounded text-[11px]">
                        Destrucción: <span x-text="activeFechaDestruccion || 'Calculada (+1 año post-vencimiento)'"></span>
                    </span>
                </div>

                <div class="p-3 bg-purple-50 rounded-xl border border-purple-200 text-xs text-purple-800">
                    Se calculará automáticamente el <strong>Rendimiento Operativo Real</strong> y el estado avanzará a <strong>BR REVISION DT</strong>.
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="modalLlegadaBr = false" 
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-purple-600 hover:bg-purple-700 shadow-md">
                        Guardar Entrada de BR
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Revisión DT & Producción (Paso 5) -->
    <div x-show="modalRevisionDt" x-cloak style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.away="modalRevisionDt = false" 
             class="w-full max-w-lg card-3d p-6 bg-white border border-slate-200 rounded-3xl shadow-2xl space-y-4">
            
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <i class="fas fa-user-check text-base"></i>
                </div>
                <div>
                    <h3 class="font-display text-base font-black text-slate-900">Revisión Dirección Técnica & Producción</h3>
                    <p class="text-xs text-slate-500">OP: <strong x-text="activeOpNumber"></strong></p>
                </div>
            </div>

            <form :action="'/maquilas/' + activeOpId + '/revision-dt'" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Decisión del Director Técnico <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="p-3 rounded-xl border border-slate-300 flex items-center space-x-2 cursor-pointer hover:border-emerald-500">
                            <input type="radio" name="estado_br_dt" value="CERRADO" checked class="text-emerald-600">
                            <span class="text-xs font-black text-emerald-800">CERRAR BATCH RECORD</span>
                        </label>
                        <label class="p-3 rounded-xl border border-slate-300 flex items-center space-x-2 cursor-pointer hover:border-red-500">
                            <input type="radio" name="estado_br_dt" value="ABIERTO" class="text-red-600">
                            <span class="text-xs font-black text-red-800">DEJAR ABIERTO (OBS.)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Comentarios u Observaciones Técnicas <span class="text-red-500">*</span>
                    </label>
                    <textarea name="comentario_dt" rows="3" required placeholder="Dictamen técnico sobre rendimiento, balance de materia, controles en proceso..."
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-medium text-slate-800"></textarea>
                </div>

                <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-200 text-xs text-indigo-800">
                    Al confirmar, el expediente avanzará a <strong>BR REVISION CALIDAD</strong> para la verificación final.
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="modalRevisionDt = false" 
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-indigo-600 hover:bg-indigo-700 shadow-md">
                        Guardar Dictamen DT
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Revisión Aseguramiento de Calidad (QA) & Liberación (Paso 6) -->
    <div x-show="modalRevisionQa" x-cloak style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.away="modalRevisionQa = false" 
             class="w-full max-w-lg card-3d p-6 bg-white border border-slate-200 rounded-3xl shadow-2xl space-y-4">
            
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-600 flex items-center justify-center">
                    <i class="fas fa-shield-alt text-base"></i>
                </div>
                <div>
                    <h3 class="font-display text-base font-black text-slate-900">Revisión Aseguramiento de Calidad (QA)</h3>
                    <p class="text-xs text-slate-500">OP: <strong x-text="activeOpNumber"></strong></p>
                </div>
            </div>

            <form :action="'/maquilas/' + activeOpId + '/revision-calidad'" method="POST" class="space-y-4">
                @csrf
                
                <!-- Verificación de Certificados de Análisis -->
                <div class="space-y-2.5 p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-700 block">Certificados y Pruebas Analíticas</span>
                    
                    <!-- Físico-Químico -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700">Certificado Físico-Químico:</span>
                        <div class="flex items-center space-x-3 text-xs">
                            <label><input type="radio" name="certificado_fisicoquimico" value="SI" checked> Sí</label>
                            <label><input type="radio" name="certificado_fisicoquimico" value="NO"> No</label>
                            <label><input type="radio" name="certificado_fisicoquimico" value="NO_APLICA"> N/A</label>
                        </div>
                    </div>

                    <!-- Microbiológico -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700">Certificado Microbiológico:</span>
                        <div class="flex items-center space-x-3 text-xs">
                            <label><input type="radio" name="certificado_microbiologico" value="SI" checked> Sí</label>
                            <label><input type="radio" name="certificado_microbiologico" value="NO"> No</label>
                            <label><input type="radio" name="certificado_microbiologico" value="NO_APLICA"> N/A</label>
                        </div>
                    </div>

                    <!-- Endotoxinas -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700">Certificado de Endotoxinas:</span>
                        <div class="flex items-center space-x-3 text-xs">
                            <label><input type="radio" name="certificado_endotoxinas" value="SI"> Sí</label>
                            <label><input type="radio" name="certificado_endotoxinas" value="NO"> No</label>
                            <label><input type="radio" name="certificado_endotoxinas" value="NO_APLICA" checked> N/A</label>
                        </div>
                    </div>
                </div>

                <!-- Liberar BR -->
                <div class="p-3 rounded-xl border border-slate-200 bg-emerald-50/40 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black text-slate-800 block">Liberar Batch Record / Lote</span>
                        <span class="text-[10px] text-slate-500">Habilita la disposición comercial del lote</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="liberar_br" value="1" checked class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <!-- Decisión Calidad -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Decisión de Aseguramiento de Calidad <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="p-3 rounded-xl border border-slate-300 flex items-center space-x-2 cursor-pointer hover:border-emerald-500">
                            <input type="radio" name="estado_br_calidad" value="CERRADO" checked class="text-emerald-600">
                            <span class="text-xs font-black text-emerald-800">CERRAR BATCH RECORD</span>
                        </label>
                        <label class="p-3 rounded-xl border border-slate-300 flex items-center space-x-2 cursor-pointer hover:border-red-500">
                            <input type="radio" name="estado_br_calidad" value="ABIERTO" class="text-red-600">
                            <span class="text-xs font-black text-red-800">DEJAR ABIERTO (OBS.)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Observaciones de Calidad
                    </label>
                    <textarea name="observaciones_calidad" rows="2" placeholder="Dictamen analítico de liberación, números de COAs..."
                              class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-medium text-slate-800"></textarea>
                </div>

                <div class="p-3 bg-slate-100 rounded-xl text-[11px] text-slate-600 font-medium">
                    <strong>Regla de Resolución:</strong> Si Dirección Técnica y Calidad marcan <em>CERRADO</em>, el BR se declara <strong>BR CERRADO</strong>. Si cualquiera marca <em>ABIERTO</em>, el estado final será <strong>BR ABIERTO</strong>.
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="modalRevisionQa = false" 
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-cyan-600 hover:bg-cyan-700 shadow-md">
                        Dictaminar Calidad & Liberar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: Maqueta 3D del Archivo Físico -->
    <div x-show="modalMaqueta3D" x-cloak style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4">
        <div @click.away="modalMaqueta3D = false" 
             class="w-full max-w-5xl bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl p-6 relative max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-800">
                <div class="flex items-center space-x-2 text-white">
                    <i class="fas fa-cubes text-cyan-400 text-xl"></i>
                    <h2 class="font-display text-lg font-black tracking-tight text-slate-100">Explorador Espacial 3D · Archivo Físico Central</h2>
                </div>
                <button @click="modalMaqueta3D = false" class="text-slate-400 hover:text-white p-2">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            @include('maquila.partials.archivo-3d', ['targetPosition' => ''])
        </div>
    </div>

</div>

<script>
function maquilaDashboardApp() {
    return {
        modalEnviar: false,
        modalLlegadaBr: false,
        modalRevisionDt: false,
        modalRevisionQa: false,
        modalMaqueta3D: false,

        activeOpId: null,
        activeOpNumber: '',
        activeTamanoLote: 0,
        activeFechaDestruccion: '',

        abrirModalEnviar(id, opNumber) {
            this.activeOpId = id;
            this.activeOpNumber = opNumber;
            this.modalEnviar = true;
        },

        abrirModalLlegadaBr(id, opNumber, tamanoLote, fechaDestruccion) {
            this.activeOpId = id;
            this.activeOpNumber = opNumber;
            this.activeTamanoLote = tamanoLote;
            this.activeFechaDestruccion = fechaDestruccion || '';
            this.modalLlegadaBr = true;
        },

        abrirModalRevisionDt(id, opNumber) {
            this.activeOpId = id;
            this.activeOpNumber = opNumber;
            this.modalRevisionDt = true;
        },

        abrirModalRevisionQa(id, opNumber) {
            this.activeOpId = id;
            this.activeOpNumber = opNumber;
            this.modalRevisionQa = true;
        },

        abrirMaqueta3D(posicion) {
            this.modalMaqueta3D = true;
        }
    };
}
</script>
@endsection
