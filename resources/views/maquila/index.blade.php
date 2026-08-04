@extends('layouts.app')

@section('header_title', 'Producción de Maquilas')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ activeTab: '{{ request('type', 'TODOS') }}' }">
    
    <!-- 1. Cabecera y Botón de Acción Principal -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-[#0A2540] tracking-tighter flex items-center gap-3">
                <span class="w-3.5 h-8 bg-[#0A2540] rounded-full"></span>
                <i class="fa-solid fa-industry text-aurofarma-teal"></i>
                Control de Producción Maquilas
            </h2>
            <p class="text-sm text-gray-500 mt-1 font-medium">Gestione, verifique y supervise las órdenes de fabricación maquiladas externamente.</p>
        </div>
        <a href="{{ route('maquila.create') }}" 
           class="inline-flex items-center justify-center px-6 py-3.5 bg-[#0A2540] text-white font-bold rounded-2xl shadow-xl hover:bg-[#071b30] hover:-translate-y-0.5 transition-all text-sm tracking-wide gap-2.5 group border border-slate-700/30">
            <i class="fa-solid fa-plus text-[#04BFAD] text-base group-hover:rotate-90 transition-transform duration-300"></i>
            <span>Crear Orden de Maquila (ODM)</span>
        </a>
    </div>

    <!-- 2. Tarjetas de Métricas Globales -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Órdenes Totales -->
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-slate-100 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-boxes-stacked text-8xl text-[#0A2540]"></i>
            </div>
            <div class="p-4 rounded-2xl bg-[#0A2540] text-white shadow-lg flex items-center justify-center w-14 h-14">
                <i class="fa-solid fa-boxes-stacked text-2xl text-white"></i>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Órdenes Totales</p>
                <div class="text-3xl font-black text-[#0A2540] tracking-tight">{{ $plantaEnMarcha }}</div>
            </div>
        </div>

        <!-- Premezclas -->
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-slate-100 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-flask text-8xl text-emerald-600"></i>
            </div>
            <div class="p-4 rounded-2xl bg-emerald-600 text-white shadow-lg flex items-center justify-center w-14 h-14">
                <i class="fa-solid fa-flask text-2xl text-white"></i>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Premezclas</p>
                <div class="text-3xl font-black text-slate-800 tracking-tight">{{ $premezclaCount }}</div>
            </div>
        </div>

        <!-- Maquilas -->
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-slate-100 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-vial text-8xl text-indigo-600"></i>
            </div>
            <div class="p-4 rounded-2xl bg-indigo-600 text-white shadow-lg flex items-center justify-center w-14 h-14">
                <i class="fa-solid fa-vial text-2xl text-white"></i>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Maquilas</p>
                <div class="text-3xl font-black text-slate-800 tracking-tight">{{ $maquilaCount }}</div>
            </div>
        </div>
    </div>

    <!-- 3. Pestañas / Filtros Rápidos -->
    <div class="flex items-center gap-2 border-b border-gray-200 pb-1">
        <a href="{{ route('maquila.index') }}" 
           @click="activeTab = 'TODOS'"
           class="px-6 py-3 text-xs font-black border-b-2 transition-all leading-none uppercase tracking-wider flex items-center gap-2"
           :class="activeTab === 'TODOS' ? 'border-[#0A2540] text-[#0A2540] font-black' : 'border-transparent text-gray-400 hover:text-gray-600'">
            <i class="fa-solid fa-list-check"></i>
            <span>TODOS</span>
        </a>
        <a href="{{ route('maquila.index', ['type' => 'PREMEZCLA']) }}" 
           @click="activeTab = 'PREMEZCLA'"
           class="px-6 py-3 text-xs font-black border-b-2 transition-all leading-none uppercase tracking-wider flex items-center gap-2"
           :class="activeTab === 'PREMEZCLA' ? 'border-emerald-500 text-emerald-600 font-black' : 'border-transparent text-gray-400 hover:text-gray-600'">
            <i class="fa-solid fa-flask"></i>
            <span>PREMEZCLA</span>
        </a>
        <a href="{{ route('maquila.index', ['type' => 'MAQUILA']) }}" 
           @click="activeTab = 'MAQUILA'"
           class="px-6 py-3 text-xs font-black border-b-2 transition-all leading-none uppercase tracking-wider flex items-center gap-2"
           :class="activeTab === 'MAQUILA' ? 'border-indigo-500 text-indigo-600 font-black' : 'border-transparent text-gray-400 hover:text-gray-600'">
            <i class="fa-solid fa-vial"></i>
            <span>MAQUILA</span>
        </a>
    </div>

    <!-- 4. Tabla Unificada de Órdenes de Maquila -->
    <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#0A2540] text-white">
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">ODM / SDM</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">Maquilador Autorizado</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-center">Tipo</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">Fecha Creación</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">Registrado Por</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-right">Acciones</th>
                </tr>
            </thead>
            @forelse($orders as $order)
                <tbody x-data="{ open: false }" class="divide-y divide-slate-100 border-b border-slate-100">
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span class="text-base font-black text-[#0A2540] tracking-tight">{{ $order->odm }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">SDM: {{ $order->sdm ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="text-sm font-bold text-slate-700 leading-tight block">{{ $order->maquilador }}</span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            @if($order->tipo_producto == 'PREMEZCLA')
                                <span class="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-flask text-xs"></i>
                                    Premezcla
                                </span>
                            @else
                                <span class="px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-vial text-xs"></i>
                                    Maquila
                                </span>
                            @endif
                        </td>
                        <td class="px-8 py-6 text-sm text-slate-600 font-medium">
                            <i class="fa-regular fa-calendar text-slate-400 mr-1"></i>
                            {{ \Carbon\Carbon::parse($order->fecha_creacion)->format('d/m/Y') }}
                        </td>
                        <td class="px-8 py-6 text-sm text-slate-600 font-semibold">
                            <i class="fa-solid fa-user-shield text-slate-400 mr-1"></i>
                            {{ $order->creator->name ?? 'Sistema' }}
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="open = !open" 
                                        class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-[#0A2540] text-xs font-bold rounded-xl transition-all gap-2 border border-slate-200">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                    <span x-text="open ? 'Ocultar Detalle' : 'Ver Detalle'">Ver Detalle</span>
                                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                                <form action="{{ route('maquila.destroy', $order->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar la Orden de Maquila ODM: {{ $order->odm }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition-colors border border-transparent hover:border-red-200" title="Eliminar Orden">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Detalle Desplegable de la Orden -->
                    <tr x-show="open" x-transition class="bg-slate-50/50 border-t border-b border-slate-100">
                        <td colspan="6" class="px-8 py-6">
                            <div class="bg-white rounded-2xl shadow-inner border border-slate-200/70 p-6 space-y-4">
                                <h4 class="text-xs font-black text-[#0A2540] uppercase tracking-widest flex items-center gap-2">
                                    <i class="fa-solid fa-list text-aurofarma-teal"></i>
                                    Detalle de Productos y Trazabilidad ({{ $order->items->count() }} ítems)
                                </h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="border-b border-slate-200 bg-slate-50">
                                                <th class="py-3 px-4 text-[9px] font-black uppercase tracking-wider text-slate-500">Referencia</th>
                                                <th class="py-3 px-4 text-[9px] font-black uppercase tracking-wider text-slate-500">Producto (Descripción)</th>
                                                <th class="py-3 px-4 text-[9px] font-black uppercase tracking-wider text-slate-500">Lote Físico</th>
                                                <th class="py-3 px-4 text-[9px] font-black uppercase tracking-wider text-slate-500 text-center">Cant. Programada</th>
                                                <th class="py-3 px-4 text-[9px] font-black uppercase tracking-wider text-slate-500">Fab.</th>
                                                <th class="py-3 px-4 text-[9px] font-black uppercase tracking-wider text-slate-500">Venc.</th>
                                                <th class="py-3 px-4 text-[9px] font-black uppercase tracking-wider text-slate-500 text-right">Alerta</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 text-xs">
                                            @foreach($order->items as $item)
                                                @php
                                                    $nearExpiry = $item->isNearExpiry();
                                                @endphp
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="py-3 px-4 font-black text-[#0A2540]">{{ $item->referencia }}</td>
                                                    <td class="py-3 px-4 text-slate-700 font-semibold">
                                                        {{ $item->getDescription() }}
                                                    </td>
                                                    <td class="py-3 px-4 font-bold text-slate-800 uppercase">{{ $item->lote_fisico }}</td>
                                                    <td class="py-3 px-4 font-black text-slate-800 text-center">
                                                        {{ number_format($item->cantidad_programada, 2) }} <span class="text-[9px] text-gray-400 font-normal uppercase">{{ $item->unidad_medida }}</span>
                                                    </td>
                                                    <td class="py-3 px-4 text-slate-500">{{ $item->fecha_fabricacion ? $item->fecha_fabricacion->format('d/m/Y') : 'N/A' }}</td>
                                                    <td class="py-3 px-4 {{ $nearExpiry ? 'text-red-600 font-black' : 'text-slate-500 font-semibold' }}">
                                                        {{ $item->fecha_vencimiento ? $item->fecha_vencimiento->format('d/m/Y') : 'N/A' }}
                                                    </td>
                                                    <td class="py-3 px-4 text-right">
                                                        @if($nearExpiry)
                                                            <span class="inline-flex items-center gap-1.5 text-[9px] font-black text-red-600 uppercase tracking-wider animate-pulse bg-red-50 border border-red-200 px-3 py-1 rounded-full">
                                                                <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                                                                Vencimiento Próximo (&lt; 3 meses)
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1.5 text-[9px] font-black text-emerald-700 uppercase tracking-wider bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
                                                                <i class="fa-solid fa-circle-check text-xs"></i>
                                                                Conforme
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            @empty
                <tbody>
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center opacity-40">
                                <i class="fa-solid fa-boxes-stacked text-6xl text-slate-300 mb-4"></i>
                                <p class="text-sm font-black uppercase tracking-widest text-slate-500">No hay órdenes de maquila registradas</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            @endforelse
        </table>
    </div>
    
    <!-- Paginación -->
    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>

<style>
    @keyframes fade-in { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in { animation: fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
@endsection
