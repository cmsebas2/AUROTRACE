@extends('layouts.app')

@section('header_title', 'Dashboard de Batch Records (Expedientes)')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-black text-[#0A2540] uppercase tracking-tighter">Control de Expedientes Maestros</h2>
                <p class="text-sm text-gray-500 font-medium uppercase tracking-widest mt-1">AuroTrace EBR System — CFR 21 Part 11</p>
            </div>
            <div class="bg-blue-50 p-3 border-l-4 border-blue-500">
                <p class="text-xs text-blue-700 font-bold uppercase leading-tight">
                    Expediente Acumulativo:<br>Genera un solo PDF con todos los hitos del lote.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Orden / Lote</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Producto</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Estado Actual</th>
                        <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-widest">Última Actividad</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-gray-500 uppercase tracking-widest">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($ops as $op)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                            <div class="flex flex-col">
                                <span>{{ $op->op_number }}</span>
                                <span class="text-[10px] text-blue-600 font-black uppercase tracking-tighter">Lote: {{ $op->lote }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $op->product->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $badgeColor = match($op->status) {
                                    'VERIFICADO' => 'bg-green-100 text-green-800 border-green-200',
                                    'AJ_FIRM' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'AJ_REALIZADO' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    default => 'bg-gray-100 text-gray-800 border-gray-200'
                                };
                            @endphp
                            <span class="px-3 py-1 inline-flex text-[10px] leading-5 font-black rounded-none border {{ $badgeColor }} uppercase tracking-widest">
                                {{ $op->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-medium">
                            {{ $op->updated_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('batch-records.pdf', $op->lote) }}" 
                               class="inline-flex items-center px-4 py-2 bg-[#0A2540] border border-transparent rounded-sm font-black text-[10px] text-white uppercase tracking-[0.2em] hover:bg-slate-800 focus:outline-none transition-all shadow-md">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Descargar Expediente
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 font-bold uppercase tracking-widest">
                            No se encontraron órdenes iniciadas en el sistema.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
