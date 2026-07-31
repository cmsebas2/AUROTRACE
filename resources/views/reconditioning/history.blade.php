@extends('layouts.app')

@section('header_title', 'Historial de Salidas - Reacondicionamiento')

@section('content')
<div class="max-w-7xl mx-auto">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-700 flex items-center">
            <i class="fas fa-archive text-gray-500 mr-3"></i>
            Historial de Salidas (Archivo Muerto)
        </h2>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('reconditioning.history') }}" method="GET" class="flex flex-col md:flex-row md:items-end gap-4">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Buscar (Código, Lote, Traslados)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded focus:ring-gray-500 focus:border-gray-500 text-sm" placeholder="Ej. Q-MUTIN, TR-2026...">
                </div>
            </div>
            <div class="w-full md:w-64">
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nivel de Riesgo Original</label>
                <select name="risk" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-gray-500 focus:border-gray-500 text-sm">
                    <option value="">Todos los Riesgos</option>
                    <option value="1" {{ request('risk') == '1' ? 'selected' : '' }}>Nivel 1 (Crítico)</option>
                    <option value="2" {{ request('risk') == '2' ? 'selected' : '' }}>Nivel 2 (Medio)</option>
                    <option value="3" {{ request('risk') == '3' ? 'selected' : '' }}>Nivel 3 (Normal)</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full md:w-auto bg-gray-700 hover:bg-gray-800 text-white font-medium py-2 px-6 rounded transition-colors text-sm">
                    Filtrar
                </button>
                @if(request()->has('search') || request()->has('risk'))
                    <a href="{{ route('reconditioning.history') }}" class="w-full md:w-auto bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-6 rounded transition-colors text-sm ml-2 inline-block text-center mt-2 md:mt-0">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 uppercase text-xs tracking-wider border-b border-gray-300">
                        <th class="p-3 font-semibold border-b border-gray-200">Salida (Siesa)</th>
                        <th class="p-3 font-semibold border-b border-gray-200">Producto</th>
                        <th class="p-3 font-semibold border-b border-gray-200">Lote</th>
                        <th class="p-3 font-semibold border-b border-gray-200 text-right">Cantidad</th>
                        <th class="p-3 font-semibold border-b border-gray-200 text-center">Destino</th>
                        <th class="p-3 font-semibold border-b border-gray-200 text-center">Ingreso</th>
                        <th class="p-3 font-semibold border-b border-gray-200 text-center">Salida</th>
                        <th class="p-3 font-semibold border-b border-gray-200 text-center">Expediente</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-3 font-medium text-gray-900">
                            {{ $item->exit_transfer_number ?: 'N/A' }}
                        </td>
                        <td class="p-3 text-gray-700">
                            <div class="font-bold">{{ $item->item_code }}</div>
                            <div class="text-xs text-gray-500 truncate w-48">{{ $item->item ? $item->item->description : 'N/A' }}</div>
                        </td>
                        <td class="p-3 font-mono text-gray-600">{{ $item->lot_number }}</td>
                        <td class="p-3 text-right font-bold text-gray-800">
                            {{ number_format($item->quantity, 2) }} <span class="text-xs text-gray-500 font-normal">{{ $item->uom }}</span>
                        </td>
                        <td class="p-3 text-center">
                            @if($item->destination_warehouse === 'PT')
                                <span class="text-xs font-bold text-green-700 bg-green-100 px-2 py-1 rounded border border-green-200">Bodega PT</span>
                            @elseif($item->destination_warehouse === 'RZ')
                                <span class="text-xs font-bold text-red-700 bg-red-100 px-2 py-1 rounded border border-red-200">Bodega RZ</span>
                            @endif
                        </td>
                        <td class="p-3 text-center text-gray-600 text-xs">
                            {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '--' }}
                        </td>
                        <td class="p-3 text-center text-gray-600 text-xs font-bold">
                            {{ $item->released_at ? \Carbon\Carbon::parse($item->released_at)->format('Y-m-d H:i') : '--' }}
                        </td>
                        <td class="p-3 text-center">
                            <div class="flex justify-center items-center space-x-2">
                                @if($item->release_pdf_path)
                                    <a href="{{ Storage::url($item->release_pdf_path) }}" target="_blank" class="inline-flex items-center justify-center bg-[#0A2540] hover:bg-slate-800 text-white text-xs font-bold px-3 py-2 rounded shadow transition-colors" title="Descargar Expediente Completo (Acta + Siesa)">
                                        <i class="fas fa-file-pdf mr-1 text-red-400"></i> Expediente
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">No disponible</span>
                                @endif
                                
                                <form action="{{ route('reconditioning.destroy', $item->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro? Esta acción es irreversible y eliminará toda la trazabilidad del lote y los PDFs asociados del servidor.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center bg-red-100 hover:bg-red-200 text-red-600 text-xs px-3 py-2 rounded transition-colors" title="Eliminar Registro Permanente">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-500">No hay registros en el historial.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush
