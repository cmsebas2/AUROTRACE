@extends('layouts.app')

@section('header_title', 'Acta de Destrucción de Batch Records - ' . $acta->acta_numero)

@section('content')
<style>
@media print {
    .no-print, nav, aside, header { display: none !important; }
    body { background: #fff !important; }
    .print-card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
}
</style>

<div class="max-w-5xl mx-auto space-y-6">

    <!-- Barra de acciones superior (no imprimible) -->
    <div class="no-print flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center space-x-2">
            <a href="{{ route('maquila.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-all">
                <i class="fas fa-arrow-left mr-2"></i> Volver a Maquilas
            </a>
            <a href="{{ route('maquila.actas_destruccion') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-all">
                <i class="fas fa-archive mr-2"></i> Historial de Actas
            </a>
        </div>

        <button type="button" onclick="window.print()"
                class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-rose-600 to-red-700 shadow-lg hover:from-rose-500 hover:to-red-600 transition-all">
            <i class="fas fa-print mr-2"></i> Imprimir / Guardar PDF
        </button>
    </div>

    @if (session('success'))
        <div class="no-print p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm flex items-center space-x-3">
            <i class="fas fa-check-circle text-emerald-600 text-lg"></i>
            <span class="text-xs font-bold">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Documento Oficial del Acta -->
    <div class="print-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <!-- Membrete -->
        <div class="bg-slate-900 text-white p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-cyan-400">AUROFARMA S.A.S. · SISTEMA DE GESTIÓN DE CALIDAD BPM / ICA</p>
                <h1 class="font-display text-2xl font-black tracking-tight mt-1">ACTA DE DESTRUCCIÓN DE BATCH RECORDS</h1>
                <p class="text-xs text-slate-300 mt-1">Registro oficial de retiro del archivo físico y disposición final de expedientes</p>
            </div>
            <div class="bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-right">
                <span class="block text-[10px] uppercase tracking-wider text-slate-300 font-bold">Número de Acta</span>
                <span class="font-mono text-lg font-black text-cyan-300">{{ $acta->acta_numero }}</span>
            </div>
        </div>

        <!-- Metadatos -->
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 border-b border-slate-200 bg-slate-50/70 text-xs">
            <div>
                <span class="block text-[10px] font-black uppercase text-slate-400">Fecha de Destrucción</span>
                <span class="font-bold text-slate-900">{{ $acta->fecha_destruccion ? $acta->fecha_destruccion->format('d/m/Y') : '—' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-black uppercase text-slate-400">Método Aplicado</span>
                <span class="font-bold text-slate-900">{{ $acta->metodo_destruccion }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-black uppercase text-slate-400">Cantidad de Lotes</span>
                <span class="font-mono font-black text-rose-700">{{ $acta->cantidad_lotes }} expediente(s)</span>
            </div>
            <div>
                <span class="block text-[10px] font-black uppercase text-slate-400">Responsable Registro</span>
                <span class="font-bold text-slate-900">{{ $acta->usuario_nombre ?? ($acta->user->name ?? 'Sistema') }}</span>
            </div>
        </div>

        <!-- Motivo y Observaciones -->
        <div class="p-6 border-b border-slate-200 space-y-3 text-xs">
            <div>
                <span class="block text-[10px] font-black uppercase text-slate-400 mb-1">Motivo / Justificación de Destrucción</span>
                <p class="font-medium text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-200">{{ $acta->motivo }}</p>
            </div>
            @if($acta->observaciones)
            <div>
                <span class="block text-[10px] font-black uppercase text-slate-400 mb-1">Observaciones Adicionales</span>
                <p class="font-medium text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-200">{{ $acta->observaciones }}</p>
            </div>
            @endif
        </div>

        <!-- Tabla de Batch Records Destruidos -->
        <div class="p-6">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-3">
                Detalle de Batch Records Retirados del Archivo Físico y Destruidos
            </h2>
            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full text-left divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-100 text-[10px] font-black uppercase text-slate-600">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Lote</th>
                            <th class="px-4 py-3">OP / ODM</th>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Maquilador</th>
                            <th class="px-4 py-3">Vencimiento</th>
                            <th class="px-4 py-3">Ubicación Física Liberada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse(($acta->lotes_detalle ?? []) as $idx => $item)
                        <tr>
                            <td class="px-4 py-3 font-mono text-slate-500">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3 font-mono font-black text-slate-900">{{ $item['lote'] ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-slate-700">
                                <span class="font-bold">{{ $item['op'] ?? '—' }}</span>
                                @if(!empty($item['numero_odm']))
                                    <span class="block text-[10px] text-slate-400">{{ $item['numero_odm'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-800">{{ $item['producto_nombre'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item['maquilador'] ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $item['fecha_vencimiento'] ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-rose-700 font-bold">{{ $item['ubicacion_anterior'] ?? 'Sin ubicación' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-400">Sin registros detallados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bloque de Firmas -->
        <div class="p-6 pt-4 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-8 text-center text-xs">
            <div class="pt-10">
                <div class="border-t border-slate-400 pt-2 font-bold text-slate-800">Archivo Técnico / Producción</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Entrega y Retiro Físico</div>
            </div>
            <div class="pt-10">
                <div class="border-t border-slate-400 pt-2 font-bold text-slate-800">Dirección Técnica</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Autorización</div>
            </div>
            <div class="pt-10">
                <div class="border-t border-slate-400 pt-2 font-bold text-slate-800">Garantía de Calidad (QA)</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Verificación y Cierre</div>
            </div>
        </div>

        <!-- Pie de Trazabilidad 21 CFR Part 11 -->
        <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between text-[10px] text-slate-500 font-mono">
            <span>Trazabilidad 21 CFR Part 11: Los lotes permanecen registrados en base de datos (estado BR DESTRUIDO) y en Audit Trail.</span>
            <span>Generado: {{ $acta->created_at ? $acta->created_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>
</div>
@endsection
