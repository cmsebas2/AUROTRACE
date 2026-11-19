@extends('layouts.app')

@section('header_title', 'Historial de Actas de Destrucción de Batch Records')

@section('content')
<div class="w-full space-y-6">
    <div class="card-3d p-6 border border-slate-200/80 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-3 h-8 bg-gradient-to-b from-rose-500 to-red-700 rounded-full"></div>
            <div>
                <h1 class="font-display text-2xl font-black text-slate-800 tracking-tight">Actas de Destrucción de Batch Records</h1>
                <p class="text-xs text-slate-500">Registro histórico auditable de lotes retirados del archivo físico y destruidos</p>
            </div>
        </div>

        <a href="{{ route('maquila.index') }}"
           class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Volver a Maquilas
        </a>
    </div>

    <div class="card-3d overflow-hidden border border-slate-200/80 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-slate-100 text-left text-xs">
                <thead>
                    <tr class="bg-slate-900 text-white text-[10px] font-black uppercase tracking-wider">
                        <th class="px-5 py-4 text-cyan-300">Acta N°</th>
                        <th class="px-5 py-4">Fecha Destrucción</th>
                        <th class="px-5 py-4">Lotes Destruidos</th>
                        <th class="px-5 py-4">Método</th>
                        <th class="px-5 py-4">Motivo</th>
                        <th class="px-5 py-4">Responsable</th>
                        <th class="px-5 py-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($actas as $acta)
                    <tr class="hover:bg-rose-50/30 transition-colors">
                        <td class="px-5 py-4 font-mono font-black text-rose-800 whitespace-nowrap">
                            {{ $acta->acta_numero }}
                        </td>
                        <td class="px-5 py-4 font-mono text-slate-700 whitespace-nowrap">
                            {{ $acta->fecha_destruccion ? $acta->fecha_destruccion->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-50 text-rose-800 border border-rose-200 mb-1">
                                {{ $acta->cantidad_lotes }} lote(s)
                            </span>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach(collect($acta->lotes_detalle ?? [])->take(5) as $det)
                                    <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $det['lote'] ?? '' }}
                                    </span>
                                @endforeach
                                @if(count($acta->lotes_detalle ?? []) > 5)
                                    <span class="text-[10px] text-slate-400 font-bold">+{{ count($acta->lotes_detalle) - 5 }} más</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-700 font-medium">{{ $acta->metodo_destruccion }}</td>
                        <td class="px-5 py-4 text-slate-600 max-w-xs truncate" title="{{ $acta->motivo }}">{{ $acta->motivo }}</td>
                        <td class="px-5 py-4 font-bold text-slate-800 whitespace-nowrap">{{ $acta->usuario_nombre ?? 'Sistema' }}</td>
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <a href="{{ route('maquila.acta_destruccion.show', $acta->id) }}"
                               class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-500 hover:to-red-600 shadow-sm transition-all">
                                <i class="fas fa-file-pdf mr-1.5"></i> Ver / Imprimir Acta
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            No se han emitido actas de destrucción de Batch Records aún.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($actas->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $actas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
