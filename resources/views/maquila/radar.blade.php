@extends('layouts.app')

@section('header_title', 'Radar 360° de Trazabilidad - ' . $order->op)

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="maquilaRadarApp()" x-init="initRadar()">
    
    <!-- Hero Header 3D -->
    <div class="card-3d p-6 bg-slate-900 text-white border border-slate-800 relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 bg-cyan-500 text-slate-950 font-mono text-xs font-black rounded-lg shadow-sm">
                        {{ $order->pre_orden ?? 'PL-XX-G' }}
                    </span>
                    <h1 class="font-display text-2xl lg:text-3xl font-black text-white tracking-tight">
                        OP: {{ $order->op }}
                    </h1>
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-cyan-900/60 text-cyan-300 border border-cyan-500/40 shadow-3d-badge">
                        LOTE: {{ $order->lote }}
                    </span>
                    <span class="px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider border {{ $order->estado_badge_class }}">
                        {{ $order->estado_label }}
                    </span>
                </div>

                <p class="text-sm font-bold text-slate-300">
                    {{ $order->producto_nombre }} 
                    <span class="text-xs text-slate-400">· Maquilador:</span> 
                    <span class="text-cyan-400 font-black">{{ $order->maquilador->nombre ?? 'N/A' }}</span>
                </p>
            </div>

            <!-- Acciones según estado actual -->
            <div class="flex flex-wrap items-center gap-2.5">
                @if(in_array($order->estado, ['OP EN PRODUCCION', 'enviada_a_maquila', 'en_proceso', 'entrega_parcial']))
                    <a href="{{ route('maquila.recepcion', $order->id) }}" 
                       class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-[#005889] to-[#06B6D4] shadow-3d-button hover:shadow-3d-cyan transition-all">
                        <i class="fas fa-truck-loading mr-1.5"></i> Ingresar Producto
                    </a>
                @elseif($order->estado === 'OP TERMINADA - BR PENDIENTE' || $order->estado === 'completada_pendiente_liquidacion')
                    <button @click="abrirLlegadaBr()" 
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-purple-600 hover:bg-purple-700 shadow-md transition-all">
                        <i class="fas fa-file-medical mr-1.5"></i> Registrar Llegada BR
                    </button>
                @elseif($order->estado === 'BR REVISION DT')
                    <button @click="modalRevisionDt = true" 
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-indigo-600 hover:bg-indigo-700 shadow-md transition-all">
                        <i class="fas fa-user-check mr-1.5"></i> Dictamen DT / Producción
                    </button>
                @elseif($order->estado === 'BR REVISION CALIDAD')
                    <button @click="modalRevisionQa = true" 
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-cyan-600 hover:bg-cyan-700 shadow-md transition-all">
                        <i class="fas fa-shield-alt mr-1.5"></i> Dictamen Calidad (QA)
                    </button>
                @endif

                @if(empty($order->fecha_llegada_br) && !in_array($order->estado, ['OP TERMINADA - BR PENDIENTE', 'completada_pendiente_liquidacion']))
                    <button @click="abrirLlegadaBr()" 
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-purple-200 hover:text-white bg-purple-900/60 hover:bg-purple-800 border border-purple-500/40 shadow-md transition-all">
                        <i class="fas fa-file-medical mr-1.5"></i> Registrar Llegada BR
                    </button>
                @endif

                <a href="{{ route('maquila.index') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-all">
                    &larr; Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Barra de Progreso y Rendimiento Global 360° -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white">
        <div class="flex justify-between items-center mb-2">
            <span class="text-xs font-black text-slate-500 uppercase tracking-wider">Avance Físico de Manufactura & Recepciones</span>
            <div class="flex items-center space-x-3">
                <span class="text-xs font-bold text-slate-400">Yield Real Calculado:</span>
                <span class="text-sm font-black text-cyan-800 font-mono">{{ $order->rendimiento_calculado }}%</span>
            </div>
        </div>

        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden border border-slate-200">
            <div class="h-3 rounded-full bg-gradient-to-r from-[#005889] via-cyan-500 to-emerald-500 transition-all duration-700"
                 style="width: {{ min(100, $order->porcentaje_avance_global) }}%"></div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 text-center text-xs">
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <span class="text-slate-400 font-bold block uppercase text-[10px]">Tamaño Base OP (Unidades)</span>
                <span class="font-black text-slate-900 text-sm font-mono">{{ number_format($order->total_programado > 0 ? $order->total_programado : $order->tamano_lote, 2) }}</span>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <span class="text-slate-400 font-bold block uppercase text-[10px]">Total Recibido (PT)</span>
                <span class="font-black text-cyan-700 text-sm font-mono">{{ number_format($order->total_recibido, 2) }}</span>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <span class="text-slate-400 font-bold block uppercase text-[10px]">Saldo por Recibir</span>
                <span class="font-black text-amber-600 text-sm font-mono">{{ number_format($order->saldo_total, 2) }}</span>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <span class="text-slate-400 font-bold block uppercase text-[10px]">Ubicación Físico BR</span>
                <span class="font-black text-purple-700 text-xs truncate block" title="{{ $order->posicion_archivo_fisico ?? 'Pendiente' }}">
                    {{ $order->posicion_archivo_fisico ?? 'Pendiente Archivo' }}
                </span>
            </div>
        </div>
    </div>

    <!-- LÍNEA DE TIEMPO FORENSE 360° (LAS 6 FASES DEL CICLO DE VIDA) -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white">
        <h3 class="font-display text-sm font-black uppercase tracking-wider text-slate-900 mb-6 flex items-center space-x-2">
            <i class="fas fa-stream text-cyan-600"></i>
            <span>Trazabilidad del Ciclo de Vida del Lote</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 relative">
            
            <!-- Fase 1: Creación -->
            <div class="p-4 rounded-2xl border {{ $order->fecha_creacion ? 'bg-cyan-50/50 border-cyan-300' : 'bg-slate-50 border-slate-200 opacity-60' }} relative">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="w-6 h-6 rounded-full {{ $order->fecha_creacion ? 'bg-cyan-600 text-white' : 'bg-slate-300' }} text-[11px] font-black flex items-center justify-center">1</span>
                    <span class="text-xs font-black text-slate-800">OP CREADA</span>
                </div>
                <div class="text-[11px] text-slate-600 space-y-0.5">
                    <div>Pre: <strong>{{ $order->pre_orden }}</strong></div>
                    <div>Fecha: {{ $order->fecha_creacion->format('Y-m-d') }}</div>
                    <div class="text-[10px] text-slate-400">Por: {{ $order->creator->name ?? 'Usuario' }}</div>
                </div>
            </div>

            <!-- Fase 2: Envío -->
            <div class="p-4 rounded-2xl border {{ $order->fecha_envio_maquila ? 'bg-amber-50/50 border-amber-300' : 'bg-slate-50 border-slate-200 opacity-60' }} relative">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="w-6 h-6 rounded-full {{ $order->fecha_envio_maquila ? 'bg-amber-600 text-white' : 'bg-slate-300' }} text-[11px] font-black flex items-center justify-center">2</span>
                    <span class="text-xs font-black text-slate-800">PRODUCCIÓN</span>
                </div>
                <div class="text-[11px] text-slate-600 space-y-0.5">
                    <div>Envío: <strong>{{ $order->fecha_envio_maquila ? $order->fecha_envio_maquila->format('Y-m-d') : 'Pendiente' }}</strong></div>
                    <div class="text-[10px] text-slate-400">{{ $order->maquilador->nombre }}</div>
                </div>
            </div>

            <!-- Fase 3: Recepción -->
            <div class="p-4 rounded-2xl border {{ $order->deliveries->count() > 0 ? 'bg-blue-50/50 border-blue-300' : 'bg-slate-50 border-slate-200 opacity-60' }} relative">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="w-6 h-6 rounded-full {{ $order->deliveries->count() > 0 ? 'bg-blue-600 text-white' : 'bg-slate-300' }} text-[11px] font-black flex items-center justify-center">3</span>
                    <span class="text-xs font-black text-slate-800">RECEPCIÓN</span>
                </div>
                <div class="text-[11px] text-slate-600 space-y-0.5">
                    <div>Entregas: <strong>{{ $order->deliveries->count() }} registradas</strong></div>
                    <div>Recibido: <strong>{{ number_format($order->total_recibido, 2) }}</strong></div>
                </div>
            </div>

            <!-- Fase 4: Llegada BR & Archivo -->
            <div class="p-4 rounded-2xl border {{ $order->fecha_llegada_br ? 'bg-purple-50/50 border-purple-300' : 'bg-slate-50 border-slate-200 opacity-60' }} relative">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="w-6 h-6 rounded-full {{ $order->fecha_llegada_br ? 'bg-purple-600 text-white' : 'bg-slate-300' }} text-[11px] font-black flex items-center justify-center">4</span>
                    <span class="text-xs font-black text-slate-800">LLEGADA BR</span>
                </div>
                <div class="text-[11px] text-slate-600 space-y-0.5">
                    <div>Fecha: <strong>{{ $order->fecha_llegada_br ? $order->fecha_llegada_br->format('Y-m-d') : 'Pendiente' }}</strong></div>
                    <div class="text-[10px] text-purple-700 font-bold truncate">{{ $order->posicion_archivo_fisico ?? 'Sin ubicar' }}</div>
                    @if($order->fecha_destruccion_br)
                        <div class="text-[10px] text-amber-700 font-bold pt-0.5">
                            <i class="fas fa-calendar-times mr-1 text-amber-600"></i> Destr. BR: <strong>{{ $order->fecha_destruccion_br }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Fase 5: Revisión DT -->
            <div class="p-4 rounded-2xl border {{ $order->fecha_revision_dt ? 'bg-indigo-50/50 border-indigo-300' : 'bg-slate-50 border-slate-200 opacity-60' }} relative">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="w-6 h-6 rounded-full {{ $order->fecha_revision_dt ? 'bg-indigo-600 text-white' : 'bg-slate-300' }} text-[11px] font-black flex items-center justify-center">5</span>
                    <span class="text-xs font-black text-slate-800">REVISIÓN DT</span>
                </div>
                <div class="text-[11px] text-slate-600 space-y-0.5">
                    <div>Dictamen: <strong class="{{ $order->estado_br_dt === 'CERRADO' ? 'text-emerald-700' : 'text-red-700' }}">{{ $order->estado_br_dt ?? 'Pendiente' }}</strong></div>
                    <div class="text-[10px] text-slate-400">DT: {{ $order->dtUser->name ?? '---' }}</div>
                </div>
            </div>

            <!-- Fase 6: Calidad & Liberación -->
            <div class="p-4 rounded-2xl border {{ $order->estado_br_calidad ? 'bg-emerald-50/50 border-emerald-300' : 'bg-slate-50 border-slate-200 opacity-60' }} relative">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="w-6 h-6 rounded-full {{ $order->estado_br_calidad ? 'bg-emerald-600 text-white' : 'bg-slate-300' }} text-[11px] font-black flex items-center justify-center">6</span>
                    <span class="text-xs font-black text-slate-800">CALIDAD (QA)</span>
                </div>
                <div class="text-[11px] text-slate-600 space-y-0.5">
                    <div>Dictamen: <strong class="{{ $order->estado_br_calidad === 'CERRADO' ? 'text-emerald-700' : 'text-red-700' }}">{{ $order->estado_br_calidad ?? 'Pendiente' }}</strong></div>
                    <div>Liberado: <strong>{{ $order->liberar_br ? 'SÍ (' . ($order->fecha_liberacion_br ? $order->fecha_liberacion_br->format('Y-m-d') : '') . ')' : 'NO' }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAQUETA 3D INTEGRADA DEL ARCHIVO FÍSICO PARA ESTE LOTE -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-cyan-100 text-cyan-600 flex items-center justify-center">
                    <i class="fas fa-cube text-sm"></i>
                </div>
                <div>
                    <h3 class="font-display text-sm font-black uppercase tracking-wider text-slate-900">Ubicación 3D del Expediente Físico</h3>
                    <p class="text-[11px] text-slate-500">Localización espacial asignada: <strong class="text-cyan-700">{{ $order->posicion_archivo_fisico ?? 'Pendiente de Asignación' }}</strong></p>
                </div>
            </div>
            <button @click="verArchivo3d = !verArchivo3d" class="text-xs font-bold text-cyan-600 hover:underline">
                <span x-text="verArchivo3d ? 'Ocultar Maqueta' : 'Mostrar Maqueta 3D'"></span>
            </button>
        </div>

        <div x-show="verArchivo3d" x-transition>
            @include('maquila.partials.archivo-3d', ['targetPosition' => $order->posicion_archivo_fisico, 'order' => $order])
        </div>
    </div>

    <!-- Tabla de Presentaciones Programadas y Entregas Registradas -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-6">
        <h3 class="font-display text-sm font-black uppercase tracking-wider text-slate-900 flex items-center space-x-2">
            <i class="fas fa-boxes text-cyan-600"></i>
            <span>Desglose de Presentaciones Programadas</span>
        </h3>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-900 text-white text-[10px] font-black uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-cyan-300"># Ítem / Presentación</th>
                        <th class="px-5 py-3.5">SDM</th>
                        <th class="px-5 py-3.5 text-right">Programado</th>
                        <th class="px-5 py-3.5 text-right">Recibido</th>
                        <th class="px-5 py-3.5 text-right">Saldo</th>
                        <th class="px-5 py-3.5 text-center">Avance %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($order->items as $item)
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-5 py-3.5">
                            <span class="font-mono font-black text-cyan-800">{{ $item->codigo_item }}</span>
                            <div class="font-bold text-slate-800">{{ $item->presentacion }}</div>
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-600 font-bold">{{ $item->sdm ?? '---' }}</td>
                        <td class="px-5 py-3.5 text-right font-mono font-bold">{{ number_format($item->cantidad_programada, 2) }} {{ $item->unidad_medida }}</td>
                        <td class="px-5 py-3.5 text-right font-mono font-black text-cyan-700 bg-cyan-50/40">{{ number_format($item->cantidad_recibida_total, 2) }} {{ $item->unidad_medida }}</td>
                        <td class="px-5 py-3.5 text-right font-mono font-bold text-amber-600">{{ number_format($item->saldo_pendiente, 2) }} {{ $item->unidad_medida }}</td>
                        <td class="px-5 py-3.5 text-center font-mono font-black text-slate-800">{{ $item->porcentaje_avance }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Historial de Entregas Parciales / Totales -->
        @if($order->deliveries->count() > 0)
        <div class="pt-4 border-t border-slate-100">
            <h4 class="font-display text-xs font-black uppercase tracking-wider text-slate-700 mb-3">Entregas de Producto Registradas</h4>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Factura / Remisión</th>
                            <th class="px-4 py-3">ESM</th>
                            <th class="px-4 py-3">Ítem / Presentación</th>
                            <th class="px-4 py-3 text-right">Cant. Recibida</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Registrado Por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($order->deliveries as $del)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 font-mono font-bold text-slate-700">{{ \Carbon\Carbon::parse($del->fecha_recepcion)->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 font-mono font-black text-cyan-800">{{ $del->numero_remision_factura }}</td>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $del->esm ?? '---' }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $del->item->presentacion ?? $del->item->codigo_item }}</td>
                            <td class="px-4 py-3 font-mono font-black text-right text-emerald-700">+{{ number_format($del->cantidad_recibida, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $del->tipo_entrega === 'TOTAL' ? 'bg-emerald-50 text-emerald-800' : 'bg-blue-50 text-blue-800' }}">
                                    {{ $del->tipo_entrega }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 font-medium">{{ $del->user->name ?? 'Sistema' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Modales para las fases si se invocan desde aquí -->
    
    <!-- MODAL LLEGADA BR & ARCHIVO FÍSICO INTERACTIVO -->
    <div x-show="modalLlegadaBr" x-cloak style="display: none;" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm p-3 sm:p-5 overflow-y-auto">
        <div @click.away="modalLlegadaBr = false" 
             class="w-full max-w-5xl bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden my-auto flex flex-col max-h-[92vh]">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-950 text-white flex items-center justify-between border-b border-slate-800 flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-500/20 text-purple-300 border border-purple-500/30 flex items-center justify-center font-black">
                        <i class="fas fa-file-medical text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-display text-base font-black text-white">Registrar Llegada del Batch Record & Asignación de Archivo Físico</h3>
                        <p class="text-xs text-slate-400">
                            OP: <strong class="text-cyan-300 font-mono">{{ $order->op }}</strong> · 
                            Lote: <strong class="text-purple-300 font-mono">{{ $order->lote }}</strong> · 
                            Producto: <span class="text-slate-200 font-bold">{{ $order->producto_nombre }}</span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="modalLlegadaBr = false" class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition-colors">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="{{ route('maquila.llegada_br', $order->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                
                <!-- Scrollable Body -->
                <div class="p-5 sm:p-6 overflow-y-auto space-y-5 flex-1 bg-slate-50">
                    
                    <!-- Row 1: Parámetros del BR (Fecha, PT Fabricado, Retención) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                        <div>
                            <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                                Fecha Llegada BR <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="fecha_llegada_br" required value="{{ date('Y-m-d') }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold text-slate-800">
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                                Total PT Fabricado (Unidades) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.001" min="0.001" name="total_producto_terminado_fabricado" 
                                   value="{{ $order->total_programado > 0 ? $order->total_programado : $order->tamano_lote }}" required placeholder="Ej: 500.00"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-mono font-black text-slate-900">
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                                Retención & Destrucción
                            </label>
                            <div class="px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-xs font-mono font-bold text-amber-900 flex items-center space-x-1.5 truncate">
                                <i class="fas fa-calendar-times text-amber-600"></i>
                                <span>{{ $order->fecha_destruccion_br ? 'Destrucción: ' . $order->fecha_destruccion_br : 'Calculada (+1 año post-venc)' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Selector Interactivo de Posición Física (RACK 1) -->
                    <div class="bg-slate-950 text-white rounded-3xl p-5 border border-slate-800 shadow-xl space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-7 h-7 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-mono font-bold text-xs border border-cyan-500/30">
                                    R1
                                </div>
                                <div>
                                    <span class="font-display font-black text-xs uppercase tracking-wider text-cyan-300 block">
                                        Seleccionar Casilla Física de Archivo (RACK 1 CENTRAL)
                                    </span>
                                    <span class="text-[10px] text-slate-400">
                                        5 Niveles · 210 Archivadores · 4 Slots por archivador (Capacidad: 840 BR)
                                    </span>
                                </div>
                            </div>

                            <button type="button" @click="sugerirPrimerSlotLibre()" 
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider text-slate-950 bg-gradient-to-r from-cyan-400 to-emerald-400 hover:from-cyan-300 hover:to-emerald-300 shadow-md transition-all flex items-center space-x-1.5 self-start sm:self-auto">
                                <i class="fas fa-bolt text-xs"></i>
                                <span>⚡ Sugerir Primer Slot Libre</span>
                            </button>
                        </div>

                        <!-- Controles de Nivel (1 al 5) y Cara (Frente / Atrás) -->
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <!-- Nivel Selector -->
                            <div class="flex items-center space-x-2">
                                <span class="text-[11px] font-black uppercase text-slate-400">Nivel:</span>
                                <div class="inline-flex p-1 bg-slate-900 rounded-xl border border-slate-800">
                                    <template x-for="n in [1, 2, 3, 4, 5]" :key="n">
                                        <button type="button" @click="llegadaBrNivel = n"
                                                :class="llegadaBrNivel === n ? 'bg-cyan-500 text-slate-950 font-black shadow-sm' : 'text-slate-400 hover:text-white font-bold'"
                                                class="px-3 py-1 rounded-lg text-xs font-mono transition-all">
                                            <span x-text="'Nivel 0' + n"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Cara Selector -->
                            <div class="flex items-center space-x-2">
                                <span class="text-[11px] font-black uppercase text-slate-400">Cara:</span>
                                <div class="inline-flex p-1 bg-slate-900 rounded-xl border border-slate-800">
                                    <button type="button" @click="llegadaBrCara = 'VISIBLE'"
                                            :class="llegadaBrCara === 'VISIBLE' ? 'bg-[#005889] text-white font-black shadow-sm' : 'text-slate-400 hover:text-white font-bold'"
                                            class="px-3 py-1 rounded-lg text-xs transition-all uppercase tracking-wider">
                                        Frente (Impares)
                                    </button>
                                    <button type="button" @click="llegadaBrCara = 'POSTERIOR'"
                                            :class="llegadaBrCara === 'POSTERIOR' ? 'bg-[#005889] text-white font-black shadow-sm' : 'text-slate-400 hover:text-white font-bold'"
                                            class="px-3 py-1 rounded-lg text-xs transition-all uppercase tracking-wider">
                                        Atrás (Pares)
                                    </button>
                                </div>
                            </div>

                            <!-- Leyenda -->
                            <div class="flex items-center space-x-3 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center"><span class="w-2.5 h-2.5 rounded bg-emerald-400 mr-1 shadow-[0_0_6px_#10B981]"></span> Seleccionado</span>
                                <span class="flex items-center"><span class="w-2.5 h-2.5 rounded bg-red-600/80 mr-1"></span> Ocupado</span>
                                <span class="flex items-center"><span class="w-2.5 h-2.5 rounded bg-slate-700 mr-1"></span> Libre</span>
                            </div>
                        </div>

                        <!-- Balda con 21 Archivadores -->
                        <div class="p-3 bg-slate-900/90 rounded-2xl border border-slate-800 overflow-x-auto">
                            <div class="grid grid-cols-7 sm:grid-cols-11 md:grid-cols-21 gap-1.5 min-w-[720px]">
                                <template x-for="archNum in getArchivadoresNivel()" :key="archNum">
                                    <div :class="llegadaBrArchivador === archNum ? 'ring-2 ring-cyan-400 bg-cyan-950/60 border-cyan-400' : 'bg-slate-900 border-slate-800 hover:border-slate-700'"
                                         class="rounded-xl p-1.5 flex flex-col justify-between border transition-all h-28">
                                        
                                        <!-- Número de Archivador -->
                                        <div class="text-center">
                                            <span class="text-[9px] font-mono font-black block"
                                                  :class="llegadaBrArchivador === archNum ? 'text-cyan-300' : 'text-slate-300'"
                                                  x-text="'#' + (archNum < 10 ? '0' + archNum : archNum)"></span>
                                        </div>

                                        <!-- Aro central -->
                                        <div class="w-2.5 h-2.5 rounded-full border border-slate-600 bg-slate-950 mx-auto"></div>

                                        <!-- 4 Slots (S1..S4) -->
                                        <div class="grid grid-cols-2 gap-1">
                                            <template x-for="s in [1, 2, 3, 4]" :key="s">
                                                <button type="button" 
                                                        @click="seleccionarSlot(archNum, s)"
                                                        :disabled="isSlotOccupied(archNum, s)"
                                                        :title="getSlotTooltip(archNum, s)"
                                                        :class="getSlotClass(archNum, s)"
                                                        class="h-4 rounded font-mono text-[8px] flex items-center justify-center transition-all">
                                                    <span x-text="'S' + s"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Banner de Confirmación de Posición -->
                        <div class="p-3.5 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                             :class="llegadaBrPosicion ? 'bg-emerald-950/50 border-emerald-500/50 text-emerald-300 shadow-[0_0_15px_rgba(16,185,129,0.15)]' : 'bg-amber-950/40 border-amber-600/50 text-amber-300'">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-sm"
                                     :class="llegadaBrPosicion ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-amber-500/20 text-amber-400 border border-amber-500/40'">
                                    <i class="fas" :class="llegadaBrPosicion ? 'fa-check-circle' : 'fa-hand-pointer animate-pulse'"></i>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-wider block"
                                          x-text="llegadaBrPosicion ? 'Ubicación Asignada en Archivo Físico:' : 'Instrucción:'"></span>
                                    <strong class="font-mono text-sm font-black text-white block" 
                                            x-text="llegadaBrPosicion || 'Haga clic en un slot libre (S1..S4) o presione Sugerir Primer Slot Libre'"></strong>
                                </div>
                            </div>

                            <input type="hidden" name="posicion_archivo_fisico" :value="llegadaBrPosicion" required>

                            <template x-if="llegadaBrPosicion">
                                <span class="px-3 py-1 rounded-xl bg-emerald-500/20 text-emerald-300 text-xs font-mono font-bold border border-emerald-500/30 self-start sm:self-auto">
                                    Slot Disponible Verificado
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="p-3 bg-purple-50 rounded-2xl border border-purple-200 text-xs text-purple-800">
                        <i class="fas fa-info-circle text-purple-600 mr-1.5"></i>
                        Al confirmar, se calculará el <strong>Rendimiento Operativo Real</strong>, la ubicación física quedará registrada en el sistema y el lote avanzará a <strong>BR REVISION DT</strong>.
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-white border-t border-slate-200 flex items-center justify-end space-x-3 flex-shrink-0">
                    <button type="button" @click="modalLlegadaBr = false" 
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" 
                            :disabled="!llegadaBrPosicion"
                            :class="!llegadaBrPosicion ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-md hover:shadow-lg'"
                            class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-white transition-all flex items-center space-x-2">
                        <i class="fas fa-save"></i>
                        <span>Confirmar Llegada & Archivar Batch Record</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL REVISION DT -->
    <div x-show="modalRevisionDt" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.away="modalRevisionDt = false" class="w-full max-w-lg card-3d p-6 bg-white border border-slate-200 rounded-3xl shadow-2xl space-y-4">
            <h3 class="font-display text-base font-black text-slate-900">Revisión DT & Producción (OP {{ $order->op }})</h3>
            <form action="{{ route('maquila.revision_dt', $order->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Decisión DT *</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="p-2.5 rounded-xl border border-slate-300 flex items-center space-x-2 text-xs font-bold"><input type="radio" name="estado_br_dt" value="CERRADO" checked> Cerrar BR</label>
                        <label class="p-2.5 rounded-xl border border-slate-300 flex items-center space-x-2 text-xs font-bold"><input type="radio" name="estado_br_dt" value="ABIERTO"> Dejar Abierto</label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Comentario DT *</label>
                    <textarea name="comentario_dt" rows="3" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs"></textarea>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="modalRevisionDt = false" class="px-4 py-2 text-xs font-bold text-slate-500">Cancelar</button>
                    <button type="submit" class="px-5 py-2 text-xs font-black uppercase text-white bg-indigo-600 rounded-xl">Guardar Dictamen DT</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL REVISION QA -->
    <div x-show="modalRevisionQa" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.away="modalRevisionQa = false" class="w-full max-w-lg card-3d p-6 bg-white border border-slate-200 rounded-3xl shadow-2xl space-y-4">
            <h3 class="font-display text-base font-black text-slate-900">Revisión Calidad (QA) & Liberación (OP {{ $order->op }})</h3>
            <form action="{{ route('maquila.revision_calidad', $order->id) }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-2 p-3 bg-slate-50 rounded-xl text-xs">
                    <div class="flex justify-between"><span>Cert. Físico-Químico:</span><div class="space-x-2"><label><input type="radio" name="certificado_fisicoquimico" value="SI" checked> Sí</label><label><input type="radio" name="certificado_fisicoquimico" value="NO"> No</label><label><input type="radio" name="certificado_fisicoquimico" value="NO_APLICA"> N/A</label></div></div>
                    <div class="flex justify-between"><span>Cert. Microbiológico:</span><div class="space-x-2"><label><input type="radio" name="certificado_microbiologico" value="SI" checked> Sí</label><label><input type="radio" name="certificado_microbiologico" value="NO"> No</label><label><input type="radio" name="certificado_microbiologico" value="NO_APLICA"> N/A</label></div></div>
                    <div class="flex justify-between"><span>Cert. Endotoxinas:</span><div class="space-x-2"><label><input type="radio" name="certificado_endotoxinas" value="SI"> Sí</label><label><input type="radio" name="certificado_endotoxinas" value="NO"> No</label><label><input type="radio" name="certificado_endotoxinas" value="NO_APLICA" checked> N/A</label></div></div>
                </div>
                <div class="flex items-center justify-between p-2.5 bg-emerald-50 rounded-xl text-xs font-bold">
                    <span>Liberar Batch Record</span>
                    <input type="checkbox" name="liberar_br" value="1" checked>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-2.5 rounded-xl border border-slate-300 flex items-center space-x-2 text-xs font-bold"><input type="radio" name="estado_br_calidad" value="CERRADO" checked> Cerrar BR</label>
                    <label class="p-2.5 rounded-xl border border-slate-300 flex items-center space-x-2 text-xs font-bold"><input type="radio" name="estado_br_calidad" value="ABIERTO"> Dejar Abierto</label>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Observaciones Calidad</label>
                    <textarea name="observaciones_calidad" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs"></textarea>
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="modalRevisionQa = false" class="px-4 py-2 text-xs font-bold text-slate-500">Cancelar</button>
                    <button type="submit" class="px-5 py-2 text-xs font-black uppercase text-white bg-cyan-600 rounded-xl">Dictaminar & Liberar</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function maquilaRadarApp() {
    return {
        modalLlegadaBr: false,
        modalRevisionDt: false,
        modalRevisionQa: false,
        verArchivo3d: true,

        orderId: {{ $order->id }},
        orderLote: '{{ $order->lote }}',
        llegadaBrNivel: 1,
        llegadaBrCara: 'VISIBLE',
        llegadaBrArchivador: null,
        llegadaBrSlot: 1,
        llegadaBrPosicion: '{{ $order->posicion_archivo_fisico ?? '' }}',
        archiveOccupiedMap: {},
        archiveLoading: false,

        initRadar() {
            if (this.llegadaBrPosicion) {
                const matchArch = this.llegadaBrPosicion.match(/ARCHIVADOR\s*#?\s*(\d+)/i);
                if (matchArch) {
                    const num = parseInt(matchArch[1]);
                    this.llegadaBrArchivador = num;
                    this.llegadaBrNivel = Math.ceil(num / 42);
                    this.llegadaBrCara = (num % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
                }
                const matchSlot = this.llegadaBrPosicion.match(/SLOT\s*([1-4])/i);
                if (matchSlot) {
                    this.llegadaBrSlot = parseInt(matchSlot[1]);
                }
            }
        },

        abrirLlegadaBr() {
            this.modalLlegadaBr = true;
            this.archiveLoading = true;
            fetch('/api/archive-locations/occupied', {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                this.archiveLoading = false;
                if (data && data.occupied) {
                    this.archiveOccupiedMap = data.occupied;
                }
                if (!this.llegadaBrPosicion) {
                    this.sugerirPrimerSlotLibre();
                }
            })
            .catch(err => {
                this.archiveLoading = false;
                console.error('Error cargando slots ocupados:', err);
                if (!this.llegadaBrPosicion) {
                    this.sugerirPrimerSlotLibre();
                }
            });
        },

        getArchivadoresNivel() {
            const list = [];
            const base = (this.llegadaBrNivel - 1) * 42;
            const isVisible = (this.llegadaBrCara === 'VISIBLE');
            for (let i = 0; i < 21; i++) {
                const num = base + (i * 2 + (isVisible ? 1 : 2));
                list.push(num);
            }
            return list;
        },

        isSlotOccupied(archNum, slot) {
            const key = `${archNum}_${slot}`;
            const occ = this.archiveOccupiedMap[key];
            if (!occ) return false;
            if (this.orderLote && occ.lote && occ.lote.toUpperCase().trim() === this.orderLote.toUpperCase().trim()) {
                return false;
            }
            if (this.orderId && occ.order_id == this.orderId) {
                return false;
            }
            return true;
        },

        getSlotTooltip(archNum, slot) {
            const key = `${archNum}_${slot}`;
            const occ = this.archiveOccupiedMap[key];
            if (this.isSlotOccupied(archNum, slot)) {
                return `OCUPADO: Lote ${occ.lote} (OP: ${occ.op || 'N/A'})`;
            }
            if (occ) {
                return `ASIGNADO A ESTA ORDEN: Slot ${slot}`;
            }
            return `DISPONIBLE: Archivador #${archNum} · Slot ${slot}`;
        },

        getSlotClass(archNum, slot) {
            const isSelected = (this.llegadaBrArchivador === archNum && this.llegadaBrSlot === slot);
            if (isSelected) {
                return 'bg-emerald-400 text-slate-950 font-black ring-2 ring-emerald-300 shadow-[0_0_12px_#10B981] scale-110 z-10';
            }
            if (this.isSlotOccupied(archNum, slot)) {
                return 'bg-red-950/80 text-red-400 border border-red-700/60 opacity-60 cursor-not-allowed';
            }
            return 'bg-slate-800 text-slate-300 hover:bg-cyan-600 hover:text-white font-bold cursor-pointer';
        },

        seleccionarSlot(archNum, slot) {
            if (this.isSlotOccupied(archNum, slot)) {
                const key = `${archNum}_${slot}`;
                const occ = this.archiveOccupiedMap[key];
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Slot Ocupado',
                        text: `El Slot ${slot} del Archivador #${archNum} ya está ocupado por el lote ${occ.lote}. Seleccione un slot disponible.`,
                        confirmButtonColor: '#005889'
                    });
                } else {
                    alert(`El Slot ${slot} del Archivador #${archNum} ya está ocupado por el lote ${occ.lote}.`);
                }
                return;
            }

            this.llegadaBrArchivador = archNum;
            this.llegadaBrSlot = slot;
            this.llegadaBrNivel = Math.ceil(archNum / 42);
            this.llegadaBrCara = (archNum % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
            this.llegadaBrPosicion = `RACK 1 · NIVEL 0${this.llegadaBrNivel} · ARCHIVADOR #${archNum} · SLOT ${slot}`;
        },

        sugerirPrimerSlotLibre() {
            for (let n = 1; n <= 5; n++) {
                const base = (n - 1) * 42;
                for (let i = 0; i < 21; i++) {
                    const arch = base + (i * 2 + 1);
                    for (let s = 1; s <= 4; s++) {
                        if (!this.isSlotOccupied(arch, s)) {
                            this.llegadaBrNivel = n;
                            this.llegadaBrCara = 'VISIBLE';
                            this.seleccionarSlot(arch, s);
                            return;
                        }
                    }
                }
                for (let i = 0; i < 21; i++) {
                    const arch = base + (i * 2 + 2);
                    for (let s = 1; s <= 4; s++) {
                        if (!this.isSlotOccupied(arch, s)) {
                            this.llegadaBrNivel = n;
                            this.llegadaBrCara = 'POSTERIOR';
                            this.seleccionarSlot(arch, s);
                            return;
                        }
                    }
                }
            }
        }
    };
}
</script>
@endsection
