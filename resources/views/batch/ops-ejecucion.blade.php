@extends('layouts.app')

@section('header_title', 'Módulo de Ejecución de Manufactura')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    
    <!-- Top Action Card -->
    <div class="card-3d p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border border-slate-200/80">
        <div class="flex items-center space-x-3">
            <div class="w-3 h-7 bg-gradient-to-b from-cyan-500 to-aurofarma rounded-full shadow-3d-cyan"></div>
            <div>
                <h2 class="font-display text-2xl font-black text-slate-800 tracking-tight">Ejecución de Manufactura en Planta</h2>
                <p class="text-xs text-slate-500 font-medium">Gestione y ejecute las fases de las órdenes activas en el piso de planta.</p>
            </div>
        </div>

        @if(auth()->user()->hasPermission('crear_op'))
        <a href="{{ route('op.crear') }}" 
           class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-cyan-500 via-[#005889] to-[#003B5C] text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-3d-button hover:shadow-3d-cyan transform hover:-translate-y-0.5 transition-all">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Crear Orden de Producción
        </a>
        @endif
    </div>

    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm flex items-center space-x-3">
            <div class="p-1 rounded-lg bg-emerald-100 text-emerald-600 flex-shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
            </div>
            <div class="text-xs font-bold">{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-sm">
            @foreach ($errors->all() as $error)
                <p class="text-xs font-bold">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- 3D Table Container -->
    <div class="card-3d overflow-hidden border border-slate-200/80">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left">
                <thead>
                    <tr class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white">
                        <th class="px-6 py-4 text-[11px] font-black text-cyan-300 uppercase tracking-wider"># OP / Placa Lote</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-200 uppercase tracking-wider">Producto Farmacéutico</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-200 uppercase tracking-wider">Plan de Envasado</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-200 uppercase tracking-wider">Progreso / Estado</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-200 uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($ops as $op)
                    <tr class="hover:bg-cyan-50/40 transition-colors group">
                        
                        <!-- Lote / OP Placa 3D -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="font-display text-sm font-black text-slate-900 tracking-tight">OP: {{ $op->op_number }}</span>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-cyan-50 text-cyan-800 border border-cyan-300 shadow-3d-badge">
                                        LOTE: {{ $op->lote }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Producto -->
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-800 leading-snug">{{ $op->product->name }}</div>
                            <div class="text-[10px] text-slate-400 mt-1 font-semibold">
                                Vencimiento: <strong class="text-slate-600">{{ \Carbon\Carbon::parse($op->expiration_date)->format('m-Y') }}</strong>
                            </div>
                        </td>

                        <!-- Presentaciones y Granel -->
                        <td class="px-6 py-4">
                            <div class="text-xs space-y-1">
                                @foreach($op->opPresentations as $pres)
                                    <div class="flex items-center text-slate-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 mr-2 flex-shrink-0"></span>
                                        <span><strong>{{ $pres->units_to_produce }}</strong> u de {{ $pres->presentation->name }}</span>
                                    </div>
                                @endforeach
                                <div class="pt-1 font-black text-slate-900 border-t border-slate-100 text-[11px]">
                                    Total Granel: <span class="text-cyan-700 font-display">{{ number_format($op->bulk_size_kg, 2, '.', '') }} KG</span>
                                </div>
                            </div>
                        </td>

                        <!-- Estado y Despeje de Línea -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $lastClearance = $op->lineClearances->last();
                                $statusLabel = $op->friendly_status;
                                $statusClass = "bg-slate-100 text-slate-600 border-slate-200";
                                
                                if (in_array($op->status, ['OP_CREADA', 'AJ_PENDIENTE'])) {
                                    $statusClass = "bg-amber-50 text-amber-800 border-amber-300";
                                } elseif (in_array($op->status, ['AJ_CREADO', 'AJ_VERIFICADO'])) {
                                    $statusClass = "bg-blue-50 text-blue-800 border-blue-300";
                                } elseif (in_array($op->status, ['OP_VERIFICADA'])) {
                                    $statusClass = "bg-indigo-50 text-indigo-800 border-indigo-300";
                                } elseif ($op->status === 'LIBERADO') {
                                    $statusClass = "bg-emerald-50 text-emerald-800 border-emerald-300";
                                }
                                
                                if($lastClearance) {
                                    $statusLabel = "DESPEJE " . mb_strtoupper($lastClearance->area) . " COMPLETADO";
                                    $statusClass = "bg-emerald-50 text-emerald-800 border-emerald-300 shadow-3d-badge";
                                }
                            @endphp
                            <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider border {{ $statusClass }} inline-flex items-center">
                                @if($lastClearance)
                                    <svg class="w-3.5 h-3.5 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping mr-2"></span>
                                @endif
                                {{ $statusLabel }}
                            </span>

                            <div class="text-[10px] text-slate-400 mt-1.5 font-medium flex items-center space-x-1">
                                <i class="far fa-clock text-slate-400"></i>
                                <span>Actividad: {{ $op->updated_at->format('Y-m-d H:i') }}</span>
                            </div>
                        </td>

                        <!-- Acciones Reforzadas 3D -->
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-2">
                                @php $action = $op->current_action; @endphp

                                <a href="{{ $action['route'] }}" 
                                   title="Siguiente fase: {{ $action['next'] }}"
                                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#005889] to-[#06B6D4] text-white rounded-xl text-[10px] font-black tracking-wider uppercase transition-all shadow-3d-button hover:shadow-3d-cyan transform hover:-translate-y-0.5 active:translate-y-0">
                                    <span>{{ $action['label'] }}</span>
                                    <svg class="w-3.5 h-3.5 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>

                                <form action="{{ route('op.destroy', $op) }}" method="POST" onsubmit="return confirm('¿Confirma la anulación de esta OP bajo auditoría CFR 21?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors border border-transparent hover:border-red-200" title="Eliminar OP">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <div class="w-14 h-14 bg-slate-100 text-slate-300 rounded-2xl flex items-center justify-center mb-3">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <h3 class="text-sm font-bold text-slate-700">Sin órdenes activas en planta</h3>
                                <p class="text-xs text-slate-400 mt-1">Todas las órdenes han sido completadas o no se han emitido nuevas.</p>
                                <a href="{{ route('op.crear') }}" class="mt-4 text-cyan-600 font-black text-xs hover:underline uppercase tracking-widest">
                                    Crear Orden de Producción →
                                </a>
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
