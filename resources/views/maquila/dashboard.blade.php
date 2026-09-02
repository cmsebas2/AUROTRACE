@extends('layouts.app')

@section('header_title', 'Torre de Control - Producción en Maquilas Externas')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12 animate-fade-in" x-data="{ activeTab: '{{ $tipoFilter ?? 'todos' }}' }">
    
    <!-- Hero Header Component -->
    <div class="bg-[#0A2540] text-white p-6 rounded-2xl shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4 border border-slate-700/50">
        <div>
            <div class="flex items-center space-x-3">
                <div class="p-3 bg-[#04BFAD]/20 rounded-xl border border-[#04BFAD]/40">
                    <i class="fa-solid fa-industry text-[#04BFAD] text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-white uppercase">Control de Producción en Maquilas Externas</h1>
                    <p class="text-xs text-slate-300 font-medium mt-0.5">Trazabilidad 360°, Rendimientos (Yield %) y Cumplimiento 21 CFR Parte 11 / Res. ICA 062542</p>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('maquila.create') }}" class="bg-[#04BFAD] hover:bg-[#048ABF] text-slate-950 font-black px-5 py-3 rounded-xl shadow-lg transition flex items-center space-x-2 text-sm">
                <i class="fa-solid fa-plus text-base"></i>
                <span>Nueva Orden (ODM / SDM)</span>
            </a>
        </div>
    </div>

    <!-- Alertas Normativas BPM ICA & Vencimiento Productos -->
    @if($alertasBpmIca->count() > 0 || $alertasVencimientoProducto->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Alertas Certificado BPM ICA -->
        @if($alertasBpmIca->count() > 0)
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-xl shadow-sm">
            <div class="flex items-start space-x-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5"></i>
                <div>
                    <h4 class="text-xs font-black text-amber-900 uppercase tracking-wider">Alertas BPM ICA Maquiladores (Res. 062542)</h4>
                    <ul class="mt-2 space-y-1 text-xs text-amber-800 font-medium">
                        @foreach($alertasBpmIca as $m)
                        <li class="flex justify-between items-center bg-white/60 px-2.5 py-1 rounded border border-amber-200">
                            <span><strong>{{ $m->nombre }}</strong> (NIT: {{ $m->nit }})</span>
                            @if($m->estado_certificado_ica === 'vencido')
                            <span class="bg-red-500 text-white font-bold px-2 py-0.5 rounded text-[10px] uppercase">Vencido</span>
                            @else
                            <span class="bg-amber-500 text-white font-bold px-2 py-0.5 rounded text-[10px] uppercase">Vence en {{ $m->dias_vencimiento_ica }} días</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Alertas Vencimiento de Lotes -->
        @if($alertasVencimientoProducto->count() > 0)
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl shadow-sm">
            <div class="flex items-start space-x-3">
                <i class="fa-solid fa-clock text-red-600 text-lg mt-0.5"></i>
                <div>
                    <h4 class="text-xs font-black text-red-900 uppercase tracking-wider">Alertas de Vencimiento de Producto (&le; 90 días)</h4>
                    <ul class="mt-2 space-y-1 text-xs text-red-800 font-medium">
                        @foreach($alertasVencimientoProducto->take(4) as $it)
                        <li class="flex justify-between items-center bg-white/60 px-2.5 py-1 rounded border border-red-200">
                            <span>Lote <strong>{{ $it->lote_fisico }}</strong> - {{ $it->descripcion_producto }}</span>
                            <span class="font-bold text-red-700 text-[11px]">{{ $it->fecha_vencimiento->format('Y-m-d') }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Cards de KPIs 360° -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- OPs Activas -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <span class="text-xs font-black text-slate-400 uppercase tracking-wider">OPs Activas</span>
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-spinner fa-spin"></i></span>
            </div>
            <div class="text-3xl font-black text-[#0A2540] mt-3 tracking-tight">{{ $opsActivasCount }}</div>
            <p class="text-[11px] text-slate-500 mt-1 font-medium">En proceso / Entregas parciales</p>
        </div>

        <!-- Rendimiento Premezcla -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <span class="text-xs font-black text-slate-400 uppercase tracking-wider">Yield Premezcla</span>
                <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-sm"><i class="fa-solid fa-vial"></i></span>
            </div>
            <div class="text-3xl font-black text-purple-700 mt-3 tracking-tight">{{ $rendimientoPromedioPremezcla }}%</div>
            <p class="text-[11px] text-slate-500 mt-1 font-medium">Tolerancia estricta (±3%)</p>
        </div>

        <!-- Rendimiento Producto Terminado -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <span class="text-xs font-black text-slate-400 uppercase tracking-wider">Yield Terminado</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm"><i class="fa-solid fa-box-check"></i></span>
            </div>
            <div class="text-3xl font-black text-emerald-700 mt-3 tracking-tight">{{ $rendimientoPromedioTerminado }}%</div>
            <p class="text-[11px] text-slate-500 mt-1 font-medium">Tolerancia estándar (±5%)</p>
        </div>

        <!-- Lead Time Promedio -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:shadow-md transition">
            <div class="flex justify-between items-center">
                <span class="text-xs font-black text-slate-400 uppercase tracking-wider">Lead Time Promedio</span>
                <span class="p-2 bg-amber-50 text-amber-600 rounded-lg text-sm"><i class="fa-solid fa-stopwatch"></i></span>
            </div>
            <div class="text-3xl font-black text-slate-800 mt-3 tracking-tight">{{ $leadTimePromedio }} <span class="text-base font-bold text-slate-500">días</span></div>
            <p class="text-[11px] text-slate-500 mt-1 font-medium">Desde despacho hasta cierre</p>
        </div>
    </div>

    <!-- Barra de Filtros por Tipo de Producto -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex space-x-2 w-full md:w-auto">
            <a href="{{ route('maquila.index') }}" 
               class="px-4 py-2 rounded-xl text-xs font-black uppercase transition {{ empty($tipoFilter) ? 'bg-[#0A2540] text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
               Todas las Órdenes
            </a>
            <a href="{{ route('maquila.index', ['tipo_producto' => 'premezcla']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-black uppercase transition {{ $tipoFilter === 'premezcla' ? 'bg-purple-700 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
               Premezclas (Medica)
            </a>
            <a href="{{ route('maquila.index', ['tipo_producto' => 'producto_terminado']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-black uppercase transition {{ $tipoFilter === 'producto_terminado' ? 'bg-emerald-700 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
               Producto Terminado
            </a>
        </div>
    </div>

    <!-- Tabla Unificada de Órdenes de Maquila -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-black text-[#0A2540] uppercase tracking-wider">Órdenes de Maquila Registradas</h3>
            <span class="text-xs text-slate-400 font-bold">Total: {{ $orders->count() }} órdenes</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 font-black uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">ODM / SDM</th>
                        <th class="px-5 py-3.5">Tipo</th>
                        <th class="px-5 py-3.5">Maquilador Autorizado</th>
                        <th class="px-5 py-3.5">Avance Total</th>
                        <th class="px-5 py-3.5 text-right">Programado</th>
                        <th class="px-5 py-3.5 text-right">Recibido</th>
                        <th class="px-5 py-3.5 text-center">Estado</th>
                        <th class="px-5 py-3.5 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($orders as $op)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-5 py-4">
                            <span class="font-black text-[#0A2540] block">{{ $op->numero_odm }}</span>
                            <span class="text-[10px] text-slate-400 font-bold">{{ $op->numero_sdm ?? 'Sin SDM' }}</span>
                        </td>
                        <td class="px-5 py-4">
                            @if($op->tipo_producto === 'premezcla')
                            <span class="bg-purple-100 text-purple-800 text-[10px] font-black px-2.5 py-1 rounded-full uppercase">Premezcla</span>
                            @else
                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2.5 py-1 rounded-full uppercase">Terminado</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="font-bold text-slate-800 block">{{ $op->maquilador->nombre }}</span>
                            <span class="text-[10px] text-slate-400">NIT: {{ $op->maquilador->nit }}</span>
                        </td>
                        <td class="px-5 py-4 w-48">
                            <div class="flex items-center justify-between text-[11px] font-bold mb-1">
                                <span>{{ $op->porcentaje_avance_global }}%</span>
                                <span class="text-slate-400">Saldo: {{ number_format($op->saldo_total, 2) }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200">
                                <div class="h-2 rounded-full transition-all duration-500 {{ $op->porcentaje_avance_global >= 90 ? 'bg-emerald-500' : ($op->porcentaje_avance_global >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" 
                                     style="width: {{ min(100, $op->porcentaje_avance_global) }}%"></div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-slate-800">
                            {{ number_format($op->total_programado, 2) }}
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-emerald-600">
                            {{ number_format($op->total_recibido, 2) }}
                        </td>
                        <td class="px-5 py-4 text-center">
                            @php
                                $estadoColors = [
                                    'borrador' => 'bg-gray-100 text-gray-700',
                                    'enviada_a_maquila' => 'bg-blue-100 text-blue-800',
                                    'en_proceso' => 'bg-indigo-100 text-indigo-800',
                                    'entrega_parcial' => 'bg-amber-100 text-amber-800',
                                    'liquidada' => 'bg-emerald-100 text-emerald-800 font-black border border-emerald-300',
                                    'cerrada_tecnicamente' => 'bg-slate-800 text-white font-black',
                                    'anulada' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $estadoColors[$op->estado] ?? 'bg-gray-100' }}">
                                {{ str_replace('_', ' ', $op->estado) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <a href="{{ route('maquila.show', $op->id) }}" 
                               class="bg-slate-100 hover:bg-[#0A2540] hover:text-white text-slate-700 px-3 py-1.5 rounded-lg font-black transition inline-flex items-center space-x-1">
                                <i class="fa-solid fa-radar text-xs"></i>
                                <span>Radar 360°</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-slate-400">
                            <i class="fa-solid fa-inbox text-3xl mb-2 block text-slate-300"></i>
                            <p class="font-bold">No hay órdenes de maquila registradas</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
