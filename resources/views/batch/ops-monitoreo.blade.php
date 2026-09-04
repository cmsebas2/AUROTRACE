@extends('layouts.app')

@section('header_title', 'Tablero de Control - OPs Activas')

@section('content')
<div class="max-w-7xl mx-auto py-8">
    
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Tablero de Supervisión y Monitoreo</h2>
            <p class="text-slate-500 font-medium">Visualice el avance en tiempo real de todas las órdenes activas en piso.</p>
        </div>
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
                        @if(auth()->user()->hasRole(['ADMIN', 'Administrador', 'admin']))
                        <th class="px-6 py-4 text-center text-xs font-black text-red-400 uppercase tracking-widest">Acciones</th>
                        @endif
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
                            <div class="text-sm font-bold text-slate-800">{{ $op->product->name ?? 'Producto no especificado' }}</div>
                            <div class="text-xs text-slate-500 mt-1">Vto: {{ $op->expiration_date ? \Carbon\Carbon::parse($op->expiration_date)->format('Y-m') : 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs space-y-1">
                                @foreach($op->opPresentations as $pres)
                                    <div class="flex items-center text-slate-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-2"></span>
                                        {{ $pres->units_to_produce }}u de {{ $pres->presentation->name ?? 'Presentación' }}
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
                                $statusLabel = $op->friendly_status;
                                $statusClass = "bg-gray-100 text-gray-600 border-gray-200";
                                
                                if (in_array($op->status, ['OP_CREADA', 'AJ_PENDIENTE'])) {
                                    $statusClass = "bg-amber-50 text-amber-600 border-amber-100";
                                } elseif (in_array($op->status, ['AJ_CREADO', 'AJ_VERIFICADO'])) {
                                    $statusClass = "bg-blue-50 text-blue-600 border-blue-200";
                                } elseif (in_array($op->status, ['OP_VERIFICADA'])) {
                                    $statusClass = "bg-indigo-50 text-indigo-700 border-indigo-200";
                                } elseif ($op->status === 'LIBERADO') {
                                    $statusClass = "bg-emerald-50 text-emerald-700 border-emerald-200";
                                }

                                if($lastClearance) {
                                    $statusLabel = "DESPEJE " . mb_strtoupper($lastClearance->area) . " COMPLETADO";
                                    $statusClass = "bg-aurofarma-teal/10 text-aurofarma-teal border-aurofarma-teal/20";
                                }
                            @endphp
                            <span class="px-3 py-1.5 rounded-lg text-[10px] font-black border {{ $statusClass }} flex items-center w-fit uppercase tracking-tighter whitespace-nowrap">
                                @if($lastClearance)
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-current animate-pulse mr-2"></span>
                                @endif
                                {{ $statusLabel }}
                            </span>
                            <div class="text-[10px] text-slate-400 mt-2 uppercase font-bold tracking-tight">
                                <i class="far fa-clock mr-1"></i>
                                Actividad: {{ $op->updated_at->format('Y-m-d H:i') }}
                            </div>
                        </td>
                        @if(auth()->user()->hasRole(['ADMIN', 'Administrador', 'admin']))
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <x-cfr21-signature-modal title="AUTORIZAR ELIMINACIÓN PERMANENTE" subtitle="ESTA ACCIÓN NO SE PUEDE DESHACER" defaultReason="Eliminación de Orden de Producción por Administrador">
                                <form action="{{ route('op.destroy', $op) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 rounded-lg text-[10px] font-black uppercase transition-all group">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        <span>Eliminar OP</span>
                                    </button>
                                </form>
                            </x-cfr21-signature-modal>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->hasRole(['ADMIN', 'Administrador', 'admin']) ? 5 : 4 }}" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800 uppercase">Sin órdenes activas</h3>
                                <p class="text-xs text-slate-500 mt-1">Todas las órdenes han sido completadas o no se han iniciado nuevas.</p>
                                <p class="mt-6 text-aurofarma-blue font-black text-xs uppercase tracking-widest">EN ESPERA DE NUEVAS OPCIONES DE PRODUCCIÓN</p>
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
