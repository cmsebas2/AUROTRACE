@extends('layouts.app')

@section('header_title', 'Tablero de Control - Aseguramiento de Calidad')

@section('content')
<div class="max-w-7xl mx-auto py-8">
    
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-black text-[#333333] tracking-tight">Módulo de Aseguramiento de Calidad</h2>
            <p class="text-[#333333] font-medium opacity-80">Gestione y cargue los Certificados de Análisis (COAs) de las órdenes completadas.</p>
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
                                $statusLabel = "PENDIENTE COAS";
                                $statusClass = "bg-amber-100 text-amber-700 border-amber-300";

                                if ($op->coas_aprobado_id) {
                                    $statusLabel = "COAS APROBADOS";
                                    $statusClass = "bg-emerald-100 text-emerald-700 border-emerald-300";
                                } elseif ($op->coas_realizado_id) {
                                    $statusLabel = "PENDIENTE APROBACIÓN COAS";
                                    $statusClass = "bg-blue-100 text-blue-700 border-blue-300";
                                }
                            @endphp
                            <span class="px-3 py-1.5 rounded-lg text-xs font-black border {{ $statusClass }} flex items-center w-fit">
                                @if($op->coas_aprobado_id)
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse mr-2"></div>
                                @endif
                                {{ $statusLabel }}
                            </span>
                            <div class="text-[10px] text-slate-400 mt-2 uppercase font-bold tracking-tight">
                                <i class="far fa-clock mr-1"></i>
                                OP Creada: {{ $op->created_at->format('Y-m-d') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                @if($op->coas_aprobado_id)
                                    <a href="{{ route('op.coas', $op->lote) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg text-[10px] font-black tracking-widest uppercase transition-all shadow-lg hover:bg-emerald-700">
                                        VER COAS
                                        <svg class="w-3.5 h-3.5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                @elseif($op->coas_realizado_id)
                                    <a href="{{ route('op.aprobar_coas', $op->lote) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-[10px] font-black tracking-widest uppercase transition-all shadow-lg hover:bg-blue-700">
                                        APROBAR COAS
                                        <svg class="w-3.5 h-3.5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </a>
                                @else
                                    <a href="{{ route('op.coas', $op->lote) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-[#0A2540] text-white rounded-lg text-[10px] font-black tracking-widest uppercase transition-all transform active:scale-95 shadow-lg">
                                        CARGAR COAS
                                        <svg class="w-3.5 h-3.5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800 uppercase">Sin órdenes pendientes</h3>
                                <p class="text-xs text-slate-500 mt-1">No hay OPs que hayan completado su codificado para cargar COAs.</p>
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
