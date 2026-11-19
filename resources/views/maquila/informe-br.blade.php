@extends('layouts.app')

@section('header_title', 'Informe Ejecutivo General de Batch Records')

@section('content')
<style>
@media print {
    .no-print, nav, aside, header { display: none !important; }
    body { background: #fff !important; }
    .print-card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; break-inside: avoid; }
    .page-break { page-break-before: always; }
}
</style>

<div class="w-full max-w-7xl mx-auto space-y-6">

    <!-- Barra Superior de Acciones y Filtros (No Imprimible) -->
    <div class="no-print bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('maquila.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-all">
                <i class="fas fa-arrow-left mr-2"></i> Volver a Maquilas
            </a>
            <a href="{{ route('maquila.index', ['estado' => 'para_destruccion']) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-black text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 transition-all">
                <i class="fas fa-fire-alt mr-2 text-amber-600"></i> Gestionar Para Destrucción ({{ $paraDestruccionCount }})
            </a>
        </div>

        <form method="GET" action="{{ route('maquila.informe_br') }}" class="flex flex-wrap items-center gap-2">
            <select name="maquilador_id" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white">
                <option value="">Todos los Maquiladores</option>
                @foreach($maquiladores as $maq)
                    <option value="{{ $maq->id }}" {{ (string)$maquiladorId === (string)$maq->id ? 'selected' : '' }}>
                        {{ $maq->nombre }}
                    </option>
                @endforeach
            </select>

            <select name="estado" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white">
                <option value="todos">Todos los Estados</option>
                <option value="BR CERRADO" {{ $estadoFiltro === 'BR CERRADO' ? 'selected' : '' }}>BR CERRADO</option>
                <option value="BR ABIERTO" {{ $estadoFiltro === 'BR ABIERTO' ? 'selected' : '' }}>BR ABIERTO</option>
                <option value="BR REVISION DT" {{ $estadoFiltro === 'BR REVISION DT' ? 'selected' : '' }}>BR REVISION DT</option>
                <option value="BR REVISION CALIDAD" {{ $estadoFiltro === 'BR REVISION CALIDAD' ? 'selected' : '' }}>BR REVISION CALIDAD</option>
                <option value="para_destruccion" {{ $estadoFiltro === 'para_destruccion' ? 'selected' : '' }}>LISTOS PARA DESTRUCCIÓN</option>
                <option value="BR DESTRUIDO" {{ $estadoFiltro === 'BR DESTRUIDO' ? 'selected' : '' }}>BR DESTRUIDO</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-black uppercase tracking-wider hover:bg-slate-800 transition-all">
                <i class="fas fa-filter mr-1"></i> Aplicar
            </button>

            <a href="{{ route('maquila.informe_br.csv', request()->query()) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all">
                <i class="fas fa-file-excel mr-1.5 text-emerald-600"></i> Excel / CSV
            </a>

            <button type="button" onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-indigo-600 to-blue-700 shadow-sm hover:from-indigo-500 hover:to-blue-600 transition-all">
                <i class="fas fa-print mr-1.5"></i> Imprimir / PDF
            </button>
        </form>
    </div>

    <!-- Membrete Corporativo del Informe -->
    <div class="print-card bg-slate-900 text-white rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-cyan-400">AUROFARMA S.A.S. · DIRECCIÓN TÉCNICA & ASEGURAMIENTO DE CALIDAD</p>
            <h1 class="font-display text-2xl font-black tracking-tight mt-1">INFORME EJECUTIVO DE BATCH RECORDS</h1>
            <p class="text-xs text-slate-300 mt-1">Estado de custodia documental, cumplimiento de retención ICA (+1 año post-vencimiento) y control de maquilas</p>
        </div>
        <div class="bg-white/10 border border-white/15 rounded-xl px-4 py-3 text-right text-xs">
            <span class="block text-[10px] uppercase tracking-wider text-slate-300 font-bold">Fecha de Emisión</span>
            <span class="font-mono font-black text-cyan-300">{{ now()->format('d/m/Y H:i') }}</span>
            <span class="block text-[10px] text-slate-300 mt-0.5">Emitido por: {{ auth()->user()->name ?? 'Sistema' }}</span>
        </div>
    </div>

    <!-- KPIs Ejecutivos -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="print-card bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Batch Records</span>
            <span class="font-display text-2xl font-black text-slate-900 mt-1 block">{{ number_format($totalBr) }}</span>
            <span class="text-[10px] text-slate-500 font-medium">100% del universo filtrado</span>
        </div>

        <div class="print-card bg-white p-4 rounded-2xl border border-emerald-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 block">BR Cerrados</span>
            <span class="font-display text-2xl font-black text-emerald-700 mt-1 block">{{ number_format($cerrados) }}</span>
            <span class="text-[10px] text-emerald-600 font-bold">{{ $totalBr > 0 ? round(($cerrados / $totalBr) * 100, 1) : 0 }}% en custodia</span>
        </div>

        <div class="print-card bg-amber-50/60 p-4 rounded-2xl border border-amber-300 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block">Para Destrucción</span>
            <span class="font-display text-2xl font-black text-amber-700 mt-1 block">{{ number_format($paraDestruccionCount) }}</span>
            <span class="text-[10px] text-amber-700 font-bold">Retención ICA cumplida</span>
        </div>

        <div class="print-card bg-rose-50/50 p-4 rounded-2xl border border-rose-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-rose-700 block">BR Destruidos</span>
            <span class="font-display text-2xl font-black text-rose-700 mt-1 block">{{ number_format($destruidos) }}</span>
            <span class="text-[10px] text-rose-600 font-bold">{{ $totalActasDestruccion }} acta(s) emitida(s)</span>
        </div>

        <div class="print-card bg-white p-4 rounded-2xl border border-cyan-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-cyan-700 block">En Archivo Físico</span>
            <span class="font-display text-2xl font-black text-cyan-800 mt-1 block">{{ number_format($enArchivoFisico) }}</span>
            <span class="text-[10px] text-slate-500 font-medium">{{ $sinArchivoFisico }} sin ubicar</span>
        </div>

        <div class="print-card bg-white p-4 rounded-2xl border border-indigo-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-wider text-indigo-700 block">Rendimiento Prom.</span>
            <span class="font-display text-2xl font-black text-indigo-700 mt-1 block">{{ number_format($rendimientoPromedio, 1) }}%</span>
            <span class="text-[10px] text-slate-500 font-medium">{{ $enRevision }} en rev. DT/QA</span>
        </div>
    </div>

    <!-- Análisis por Estado y por Maquilador -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Distribución por Estado -->
        <div class="print-card bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4 flex items-center">
                <i class="fas fa-layer-group text-cyan-600 mr-2"></i> Distribución por Estado del Ciclo
            </h2>
            <div class="space-y-3">
                @forelse($resumenEstados as $row)
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-bold text-slate-800">{{ $row['estado'] }}</span>
                        <span class="font-mono font-black text-slate-700">{{ $row['cantidad'] }} ({{ $row['porcentaje'] }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full {{ str_contains($row['estado'], 'CERRADO') ? 'bg-emerald-500' : (str_contains($row['estado'], 'DESTRUIDO') ? 'bg-rose-500' : (str_contains($row['estado'], 'CALIDAD') || str_contains($row['estado'], 'DT') ? 'bg-cyan-500' : 'bg-amber-500')) }}"
                             style="width: {{ min(100, $row['porcentaje']) }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-xs text-slate-400">Sin datos disponibles.</p>
                @endforelse
            </div>
        </div>

        <!-- Consolidado por Maquilador -->
        <div class="print-card bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4 flex items-center">
                <i class="fas fa-industry text-indigo-600 mr-2"></i> Consolidado por Maquilador
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-slate-100">
                    <thead class="text-[10px] font-black uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2">Maquilador</th>
                            <th class="px-3 py-2 text-center">Total</th>
                            <th class="px-3 py-2 text-center">Cerrados</th>
                            <th class="px-3 py-2 text-center">Para Dest.</th>
                            <th class="px-3 py-2 text-center">Destruidos</th>
                            <th class="px-3 py-2 text-right">Rend.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($resumenMaquiladores as $m)
                        <tr>
                            <td class="px-3 py-2 font-bold text-slate-800">{{ $m['maquilador'] }}</td>
                            <td class="px-3 py-2 text-center font-mono font-bold">{{ $m['total'] }}</td>
                            <td class="px-3 py-2 text-center font-mono text-emerald-700 font-bold">{{ $m['cerrados'] }}</td>
                            <td class="px-3 py-2 text-center font-mono text-amber-700 font-bold">{{ $m['para_destruccion'] }}</td>
                            <td class="px-3 py-2 text-center font-mono text-rose-700 font-bold">{{ $m['destruidos'] }}</td>
                            <td class="px-3 py-2 text-right font-mono">{{ !is_null($m['rendimiento_promedio']) ? number_format($m['rendimiento_promedio'], 1) . '%' : '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-slate-400">Sin datos.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sección Especial: Listos para Destrucción -->
    @if($paraDestruccionList->isNotEmpty())
    <div class="print-card bg-white rounded-2xl border border-amber-300 shadow-sm overflow-hidden">
        <div class="bg-amber-50 px-5 py-3.5 border-b border-amber-200 flex items-center justify-between">
            <div>
                <h2 class="text-xs font-black uppercase tracking-wider text-amber-900 flex items-center">
                    <i class="fas fa-exclamation-triangle text-amber-600 mr-2"></i>
                    Batch Records Listos para Destrucción ({{ $paraDestruccionCount }})
                </h2>
                <p class="text-[11px] text-amber-700 mt-0.5">Lotes cuya fecha programada de destrucción ICA es igual o anterior al mes actual ({{ now()->format('Y-m') }})</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs divide-y divide-amber-100">
                <thead class="bg-amber-50/50 text-[10px] font-black uppercase text-amber-800">
                    <tr>
                        <th class="px-4 py-2.5">Lote</th>
                        <th class="px-4 py-2.5">OP</th>
                        <th class="px-4 py-2.5">Producto</th>
                        <th class="px-4 py-2.5">Maquilador</th>
                        <th class="px-4 py-2.5">Vencimiento</th>
                        <th class="px-4 py-2.5">Destrucción ICA</th>
                        <th class="px-4 py-2.5">Ubicación Física</th>
                        <th class="px-4 py-2.5">Estado Actual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($paraDestruccionList as $pd)
                    <tr>
                        <td class="px-4 py-2.5 font-mono font-black text-slate-900">{{ $pd->lote }}</td>
                        <td class="px-4 py-2.5 font-mono text-slate-700">{{ $pd->op }}</td>
                        <td class="px-4 py-2.5 font-bold text-slate-800">{{ $pd->producto_nombre }}</td>
                        <td class="px-4 py-2.5 text-slate-700">{{ $pd->maquilador->nombre ?? '—' }}</td>
                        <td class="px-4 py-2.5 font-mono text-slate-600">{{ $pd->fecha_vencimiento ?? '—' }}</td>
                        <td class="px-4 py-2.5 font-mono font-black text-amber-700">{{ $pd->fecha_destruccion_br }}</td>
                        <td class="px-4 py-2.5 font-mono text-cyan-800 font-bold">{{ $pd->posicion_archivo_fisico ?: 'Sin ubicar' }}</td>
                        <td class="px-4 py-2.5 text-[11px] font-bold">{{ $pd->estado_label }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Inventario Detallado Completo -->
    <div class="print-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800">
                Detalle Completo de Batch Records ({{ $totalBr }})
            </h2>
            <span class="text-[10px] font-mono text-slate-400">21 CFR Part 11 · AUROTRACE</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs divide-y divide-slate-100">
                <thead class="bg-slate-900 text-white text-[10px] font-black uppercase">
                    <tr>
                        <th class="px-4 py-3">Lote</th>
                        <th class="px-4 py-3">OP / ODM</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3">Maquilador</th>
                        <th class="px-4 py-3">Vencimiento</th>
                        <th class="px-4 py-3">Destrucción ICA</th>
                        <th class="px-4 py-3 text-center">Rend.</th>
                        <th class="px-4 py-3">Ubicación</th>
                        <th class="px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $op)
                    @php
                        $aptoDest = $op->estado !== 'BR DESTRUIDO' && !empty($op->fecha_destruccion_br) && $op->fecha_destruccion_br <= $cutoffDestruccion;
                    @endphp
                    <tr class="{{ $aptoDest ? 'bg-amber-50/30' : '' }}">
                        <td class="px-4 py-2.5 font-mono font-black text-slate-900 whitespace-nowrap">{{ $op->lote }}</td>
                        <td class="px-4 py-2.5 font-mono text-slate-700 whitespace-nowrap">
                            {{ $op->op }}
                            @if($op->numero_odm)
                                <span class="block text-[10px] text-slate-400">{{ $op->numero_odm }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 font-bold text-slate-800">{{ $op->producto_nombre }}</td>
                        <td class="px-4 py-2.5 text-slate-700">{{ $op->maquilador->nombre ?? '—' }}</td>
                        <td class="px-4 py-2.5 font-mono text-slate-600">{{ $op->fecha_vencimiento ?? '—' }}</td>
                        <td class="px-4 py-2.5 font-mono {{ $aptoDest ? 'text-amber-700 font-black' : 'text-slate-600' }}">
                            {{ $op->fecha_destruccion_br ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-center font-mono">
                            {{ !is_null($op->rendimiento_real) ? number_format($op->rendimiento_real, 1) . '%' : '—' }}
                        </td>
                        <td class="px-4 py-2.5 font-mono text-xs">
                            @if($op->estado === 'BR DESTRUIDO')
                                <span class="text-rose-700 font-bold">Retirado ({{ $op->acta_destruccion_numero ?? 'Destruido' }})</span>
                            @else
                                {{ $op->posicion_archivo_fisico ?: 'Sin ubicar' }}
                            @endif
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-black border {{ $op->estado_badge_class }}">
                                {{ $op->estado_label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-slate-400">No se encontraron Batch Records con los filtros seleccionados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
