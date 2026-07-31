@extends('layouts.app')

@section('header_title', 'Tablero de Control - OPs Activas')

@section('content')
<div class="max-w-7xl mx-auto py-8">
    
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Monitoreo de Producción</h2>
            <p class="text-slate-500 font-medium">Visualice el estado actual de todas las órdenes en curso.</p>
        </div>
        <a href="{{ route('batch.iniciar') }}" class="inline-flex items-center px-6 py-3 bg-aurofarma-blue text-white font-bold rounded-xl shadow-lg shadow-blue-200 hover:opacity-90 transition-all">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Nueva OP
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl bg-green-50 p-4 border border-green-200 animate-in fade-in slide-in-from-top duration-300">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                </div>
                <div class="ml-3 font-bold text-green-800">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl bg-red-50 p-4 border border-red-200">
            @foreach ($errors->all() as $error)
                <p class="text-sm font-bold text-red-800">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-black text-slate-300 uppercase tracking-widest"># OP / Lote</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-slate-300 uppercase tracking-widest">Producto</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-slate-300 uppercase tracking-widest">Plan de Envasado</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-slate-300 uppercase tracking-widest">Progreso / Estado</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-slate-300 uppercase tracking-widest">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($ops as $op)
                    <tr class="hover:bg-slate-50 shadow-sm transition-colors border-l-4 border-transparent hover:border-aurofarma-blue">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-black text-slate-900">{{ $op->op_number }}</div>
                            <div class="text-xs font-bold text-aurofarma-blue bg-blue-50 inline-block px-2 py-0.5 rounded-full mt-1">LOTE: {{ $op->lote }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-800">{{ $op->product->name }}</div>
                            <div class="text-xs text-slate-500 mt-1">Vto: {{ \Carbon\Carbon::parse($op->expiration_date)->format('m-Y') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs space-y-1">
                                @foreach($op->opPresentations as $pres)
                                    <div class="flex items-center text-slate-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-2"></span>
                                        {{ $pres->units_to_produce }}u de {{ $pres->presentation->name }}
                                    </div>
                                @endforeach
                                <div class="pt-1 font-black text-slate-900 border-t border-slate-100 mt-1">
                                    Total: {{ number_format($op->bulk_size_kg, 2, '.', '') }} KG
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $lastClearance = $op->lineClearances->last();
                                $statusLabel = "OP CREADA - PENDIENTE DESPEJE";
                                $statusClass = "bg-gray-100 text-gray-600 border-gray-200";
                                
                                if($lastClearance) {
                                    $statusLabel = "DESPEJE " . mb_strtoupper($lastClearance->area) . " COMPLETADO";
                                    $statusClass = "bg-aurofarma-teal/10 text-aurofarma-teal border-aurofarma-teal/20";
                                }
                            @endphp
                            <span class="px-3 py-1.5 rounded-lg text-xs font-black border {{ $statusClass }} flex items-center w-fit">
                                @if($lastClearance)
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-gray-400 animate-pulse mr-2"></span>
                                @endif
                                {{ $statusLabel }}
                            </span>
                            <div class="text-[10px] text-slate-400 mt-2 uppercase font-bold tracking-tight">
                                <i class="far fa-clock mr-1"></i>
                                Actividad: {{ $op->updated_at->format('Y-m-d H:i') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                @php
                                    $route = route('batch.despeje', $op);
                                    $label = 'CONTINUAR';
                                    $btnClass = 'bg-aurofarma-blue/10 text-aurofarma-blue hover:bg-aurofarma-blue hover:text-white';

                                    if ($op->status === 'ACONDICIONAMIENTO') {
                                        $route = route('batch.despeje', $op->lote);
                                        $label = 'INICIAR ENVASE';
                                        $btnClass = 'bg-green-600/10 text-green-700 hover:bg-green-600 hover:text-white';
                                    }
                                @endphp

                                <a href="{{ $route }}" class="inline-flex items-center px-4 py-2 {{ $btnClass }} rounded-lg text-xs font-black transition-all transform active:scale-95">
                                    {{ $label }}
                                    <svg class="w-3.5 h-3.5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
 
                                <form action="{{ route('ops.destroy', $op) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta OP y todos sus registros asociados? Esta acción es irreversible.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar OP">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800 uppercase">Sin órdenes activas</h3>
                                <p class="text-xs text-slate-500 mt-1">Todas las órdenes han sido completadas o no se han iniciado nuevas.</p>
                                <a href="{{ route('batch.iniciar') }}" class="mt-6 text-aurofarma-blue font-black text-xs hover:underline uppercase tracking-widest">Crear mi primera OP →</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
