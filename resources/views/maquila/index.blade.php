@extends('layouts.app')

@section('header_title', 'Producción de Maquilas')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ activeTab: '{{ request('type', 'TODOS') }}' }">
    
    <!-- 1. Cabecera y Botón de Acción -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter flex items-center gap-3">
                <span class="w-4 h-8 bg-slate-900 rounded-full"></span>
                Control de Producción Maquilas
            </h2>
            <p class="text-sm text-gray-500 mt-1">Geste, verifique y supervise las órdenes de fabricación maquiladas externamente.</p>
        </div>
        <a href="{{ route('maquila.create') }}" 
           class="inline-flex items-center justify-center px-6 py-3.5 bg-slate-900 text-white font-bold rounded-2xl shadow-xl hover:bg-slate-800 hover:-translate-y-0.5 transition-all text-sm tracking-wide gap-2 group">
            <svg class="w-5 h-5 text-emerald-400 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Crear Orden de Maquila (ODM)
        </a>
    </div>

    <!-- 2. Tarjetas de Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Órdenes Totales -->
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-slate-100 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform">
                <svg class="w-24 h-24 text-slate-950" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path></svg>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900 text-white shadow-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Órdenes Totales</p>
                <div class="text-3xl font-black text-slate-800 tracking-tight">{{ $plantaEnMarcha }}</div>
            </div>
        </div>

        <!-- Premezclas -->
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-slate-100 flex items-center gap-5 relative overflow-hidden group">
            <div class="p-4 rounded-2xl bg-emerald-500 text-white shadow-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 9.172V5L8 4z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Premezclas</p>
                <div class="text-3xl font-black text-slate-800 tracking-tight">{{ $premezclaCount }}</div>
            </div>
        </div>

        <!-- Productos Terminados -->
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-slate-100 flex items-center gap-5 relative overflow-hidden group">
            <div class="p-4 rounded-2xl bg-blue-500 text-white shadow-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Producto Terminado</p>
                <div class="text-3xl font-black text-slate-800 tracking-tight">{{ $productoTerminadoCount }}</div>
            </div>
        </div>
    </div>

    <!-- 3. Pestañas de Filtrado -->
    <div class="flex items-center gap-2 border-b border-gray-200 pb-1">
        <a href="{{ route('maquila.index') }}" 
           @click="activeTab = 'TODOS'"
           class="px-5 py-3 text-sm font-bold border-b-2 transition-all leading-none"
           :class="activeTab === 'TODOS' ? 'border-slate-900 text-slate-900 font-extrabold' : 'border-transparent text-gray-400 hover:text-gray-600'">
            TODOS
        </a>
        <a href="{{ route('maquila.index', ['type' => 'PREMEZCLA']) }}" 
           @click="activeTab = 'PREMEZCLA'"
           class="px-5 py-3 text-sm font-bold border-b-2 transition-all leading-none"
           :class="activeTab === 'PREMEZCLA' ? 'border-emerald-500 text-emerald-600 font-extrabold' : 'border-transparent text-gray-400 hover:text-gray-600'">
            PREMEZCLA
        </a>
        <a href="{{ route('maquila.index', ['type' => 'PRODUCTO_TERMINADO']) }}" 
           @click="activeTab = 'PRODUCTO_TERMINADO'"
           class="px-5 py-3 text-sm font-bold border-b-2 transition-all leading-none"
           :class="activeTab === 'PRODUCTO_TERMINADO' ? 'border-blue-500 text-blue-600 font-extrabold' : 'border-transparent text-gray-400 hover:text-gray-600'">
            PRODUCTO TERMINADO
        </a>
    </div>

    <!-- 4. Tabla de Órdenes de Maquila -->
    <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-900 text-white">
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">ODM / SDM</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">Maquilador</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-center">Tipo</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">Fecha Creación</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">Registrado Por</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                    <tr x-data="{ open: false }" class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span class="text-base font-black text-slate-800 tracking-tight">{{ $order->odm }}</span>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">SDM: {{ $order->sdm ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="text-sm font-bold text-slate-600 leading-tight block">{{ $order->maquilador }}</span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            @if($order->tipo_producto == 'PREMEZCLA')
                                <span class="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 text-[9px] font-black uppercase tracking-wider">
                                    Premezcla
                                </span>
                            @else
                                <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200 text-[9px] font-black uppercase tracking-wider">
                                    Prod. Terminado
                                </span>
                            @endif
                        </td>
                        <td class="px-8 py-6 text-sm text-slate-500 font-medium">
                            {{ \Carbon\Carbon::parse($order->fecha_creacion)->format('d/m/Y') }}
                        </td>
                        <td class="px-8 py-6 text-sm text-slate-500 font-semibold">
                            {{ $order->creator->name ?? 'Sistema' }}
                        </td>
                        <td class="px-8 py-6 text-right">
                            <button @click="open = !open" 
                                    class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all gap-1.5 border border-slate-200/50">
                                <span x-text="open ? 'Ocultar Detalle' : 'Ver Detalle'">Ver Detalle</span>
                                <svg class="w-4 h-4 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </td>
                        
                        <!-- Detalle Desplegable de la Orden -->
                        <template x-if="open">
                            <tr class="bg-slate-50/50 border-t border-b border-slate-100">
                                <td colspan="6" class="px-8 py-6">
                                    <div class="bg-white rounded-2xl shadow-inner border border-slate-200/50 p-6 space-y-4">
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Detalle de Productos y Trazabilidad</h4>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left">
                                                <thead>
                                                    <tr class="border-b border-slate-200">
                                                        <th class="py-2.5 text-[9px] font-black uppercase tracking-wider text-slate-500">Producto</th>
                                                        <th class="py-2.5 text-[9px] font-black uppercase tracking-wider text-slate-500">Lote</th>
                                                        <th class="py-2.5 text-[9px] font-black uppercase tracking-wider text-slate-500 text-center">Cantidad Recibida</th>
                                                        <th class="py-2.5 text-[9px] font-black uppercase tracking-wider text-slate-500 text-center">Cantidad Programada</th>
                                                        <th class="py-2.5 text-[9px] font-black uppercase tracking-wider text-slate-500">Fab.</th>
                                                        <th class="py-2.5 text-[9px] font-black uppercase tracking-wider text-slate-500">Venc.</th>
                                                        <th class="py-2.5 text-[9px] font-black uppercase tracking-wider text-slate-500 text-right">Alerta</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 text-xs">
                                                    @foreach($order->items as $item)
                                                        @php
                                                            $nearExpiry = $item->isNearExpiry();
                                                        @endphp
                                                        <tr class="hover:bg-slate-50/30">
                                                            <td class="py-3 font-bold text-slate-700">
                                                                {{ $item->product->name ?? 'N/A' }} 
                                                                <span class="text-[10px] text-gray-400 font-normal">({{ $item->product->presentation ?? '' }})</span>
                                                            </td>
                                                            <td class="py-3 font-semibold text-slate-600">{{ $item->lote }}</td>
                                                            <td class="py-3 font-bold text-slate-700 text-center">
                                                                {{ number_format($item->cantidad, 2) }} <span class="text-[9px] text-gray-400 font-normal">{{ $item->product->base_unit ?? 'KG' }}</span>
                                                            </td>
                                                            <td class="py-3 font-bold text-slate-700 text-center">
                                                                {{ number_format($item->cantidad_programada, 2) }} <span class="text-[9px] text-gray-400 font-normal">{{ $item->product->base_unit ?? 'KG' }}</span>
                                                            </td>
                                                            <td class="py-3 text-slate-500">{{ $item->fecha_fabricacion->format('d/m/Y') }}</td>
                                                            <td class="py-3 {{ $nearExpiry ? 'text-red-600 font-bold' : 'text-slate-500' }}">
                                                                {{ $item->fecha_vencimiento->format('d/m/Y') }}
                                                            </td>
                                                            <td class="py-3 text-right">
                                                                @if($nearExpiry)
                                                                    <span class="inline-flex items-center gap-1 text-[9px] font-black text-red-600 uppercase tracking-wider animate-pulse bg-red-50 border border-red-200 px-2.5 py-1 rounded-full">
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                                        Vencimiento Próximo
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center gap-1 text-[9px] font-black text-emerald-600 uppercase tracking-wider bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
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
                        </template>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center opacity-25">
                                <svg class="w-20 h-20 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                <p class="mt-4 text-sm font-black uppercase tracking-widest">No hay órdenes de maquila registradas</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>

<style>
    @keyframes fade-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in { animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
@endsection
