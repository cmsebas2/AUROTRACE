@extends('layouts.app')

@section('header_title', 'Recepción de Producto de Maquila')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Navegación Superior -->
    <div class="flex items-center justify-between">
        <a href="{{ route('maquila.index') }}" class="text-xs font-black uppercase tracking-wider text-slate-500 hover:text-cyan-600 flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Dashboard de Maquilas
        </a>
        <span class="px-3 py-1 rounded-full bg-cyan-50 text-cyan-800 text-[10px] font-black border border-cyan-200">
            ODM: {{ $order->numero_odm }}
        </span>
    </div>

    <!-- Ficha Técnica 3D de la OP de Maquila -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="px-3 py-1 bg-slate-900 text-white font-mono text-xs font-black rounded-lg shadow-sm">
                        {{ $order->pre_orden ?? 'PL-XX-G' }}
                    </span>
                    <h1 class="font-display text-2xl font-black text-slate-900 tracking-tight">
                        OP: {{ $order->op }}
                    </h1>
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-cyan-50 text-cyan-800 border border-cyan-300 shadow-3d-badge">
                        LOTE: {{ $order->lote }}
                    </span>
                    <span class="px-3 py-1 rounded-xl text-xs font-bold border {{ $order->estado_badge_class }}">
                        {{ $order->estado_label }}
                    </span>
                </div>

                <div class="text-sm font-bold text-slate-700">
                    {{ $order->producto_nombre }} 
                    <span class="text-xs font-normal text-slate-500">• Forma Farmacéutica: <strong class="text-slate-700">{{ $order->forma_farmaceutica }}</strong></span>
                </div>

                <div class="text-xs text-slate-500 flex flex-wrap items-center gap-4 pt-1 font-medium">
                    <span>Maquilador: <strong class="text-slate-800">{{ $order->maquilador->nombre }}</strong></span>
                    <span>Fabricación: <strong class="text-slate-800">{{ $order->fecha_fabricacion }}</strong></span>
                    <span>Vencimiento: <strong class="text-slate-800">{{ $order->fecha_vencimiento }}</strong></span>
                </div>
            </div>

            <!-- KPIs de Cumplimiento / Avance -->
            <div class="grid grid-cols-3 gap-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-center">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Programado</span>
                    <p class="font-display text-lg font-black text-slate-800">{{ number_format($order->total_programado, 2) }}</p>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-cyan-700">Recibido</span>
                    <p class="font-display text-lg font-black text-cyan-600">{{ number_format($order->total_recibido, 2) }}</p>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-700">Saldo</span>
                    <p class="font-display text-lg font-black text-amber-600">{{ number_format($order->saldo_total, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm flex items-center space-x-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
            <span class="text-xs font-bold">{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-sm">
            @foreach ($errors->all() as $error)
                <p class="text-xs font-bold">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- Formulario de Recepción 3D -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white">
        <div class="flex items-center space-x-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#005889] to-[#06B6D4] flex items-center justify-center text-white shadow-3d-cyan">
                <i class="fas fa-truck-loading text-lg"></i>
            </div>
            <div>
                <h2 class="font-display text-lg font-black text-slate-900 tracking-tight">Registro de Entrada de Producto Terminado</h2>
                <p class="text-xs text-slate-500">Ingrese los datos de remisión/factura y cantidades recibidas por presentación</p>
            </div>
        </div>

        <form action="{{ route('maquila.recepcion.store', $order->id) }}" method="POST" class="space-y-6">
            @csrf

            <!-- Fila de Metadatos de la Entrada -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha de Ingreso / Recepción <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha_ingreso" required value="{{ old('fecha_ingreso', date('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Número de Factura / Remisión <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="numero_factura" required value="{{ old('numero_factura') }}" placeholder="Ej: FAC-9821, REM-104"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800 uppercase">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                        Número ESM (Entrada Suministro Maquila) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="esm" required value="{{ old('esm') }}" placeholder="Ej: ESM-2026-042"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800 uppercase">
                </div>
            </div>

            <!-- Tabla de Presentaciones Programadas y Cantidades a Recibir -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-900 text-white">
                        <tr>
                            <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider text-cyan-300">Ítem / Presentación</th>
                            <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider text-slate-300">SDM</th>
                            <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider text-right text-slate-300">Programado</th>
                            <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider text-right text-emerald-400">Recibido Antes</th>
                            <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider text-right text-amber-400">Saldo Pendiente</th>
                            <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider text-right text-cyan-300">Cantidad en este Ingreso</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($order->items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-mono text-xs font-black text-cyan-800">{{ $item->codigo_item }}</span>
                                <div class="text-xs font-bold text-slate-800">{{ $item->presentacion }}</div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs font-mono text-slate-600 font-bold">
                                {{ $item->sdm ?? '---' }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right font-mono text-xs font-bold text-slate-700">
                                {{ number_format($item->cantidad_programada, 2) }} {{ $item->unidad_medida }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right font-mono text-xs font-bold text-emerald-700 bg-emerald-50/40">
                                {{ number_format($item->cantidad_recibida_total, 2) }} {{ $item->unidad_medida }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right font-mono text-xs font-bold text-amber-700 bg-amber-50/40">
                                {{ number_format($item->saldo_pendiente, 2) }} {{ $item->unidad_medida }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                <div class="inline-flex items-center space-x-2">
                                    <input type="number" step="0.001" min="0" 
                                           name="cantidades[{{ $item->id }}]" 
                                           value="{{ old('cantidades.'.$item->id, $item->saldo_pendiente > 0 ? $item->saldo_pendiente : 0) }}"
                                           class="w-32 px-3 py-1.5 rounded-xl border-2 border-cyan-300 focus:border-cyan-500 text-right font-mono text-xs font-black text-slate-900 bg-cyan-50/30">
                                    <span class="text-xs font-bold text-slate-500">{{ $item->unidad_medida }}</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Selector de Tipo de Ingreso: Parcial vs Total -->
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                <label class="block text-xs font-black text-slate-800 uppercase tracking-wider">
                    Clasificación de la Entrega <span class="text-red-500">*</span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-start p-3.5 rounded-xl border border-slate-300 bg-white cursor-pointer hover:border-cyan-500 transition-all shadow-sm">
                        <input type="radio" name="tipo_recepcion" value="PARCIAL" checked class="mt-0.5 text-cyan-600 focus:ring-cyan-500">
                        <div class="ml-3">
                            <span class="block text-xs font-black text-slate-900 uppercase">Ingreso Parcial</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Permite seguir registrando entregas posteriores en el dashboard. La orden permanece abierta en planta.</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3.5 rounded-xl border border-slate-300 bg-white cursor-pointer hover:border-emerald-500 transition-all shadow-sm">
                        <input type="radio" name="tipo_recepcion" value="TOTAL" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                        <div class="ml-3">
                            <span class="block text-xs font-black text-slate-900 uppercase text-emerald-700">Ingreso Total (Finalizar Producción)</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Declara completada la manufactura. Cambia el estado a <strong>OP TERMINADA - BR PENDIENTE</strong> para registrar la llegada del Batch Record.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">
                    Observaciones Técnicas de Recepción
                </label>
                <textarea name="observaciones" rows="2" placeholder="Condiciones de transporte, precintos, aspecto físico del embalaje..."
                          class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-medium text-slate-800">{{ old('observaciones') }}</textarea>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('maquila.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-slate-600 hover:bg-slate-100 transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-[#005889] to-[#06B6D4] shadow-3d-button hover:shadow-3d-cyan transition-all transform hover:-translate-y-0.5">
                    Guardar Ingreso de Producto
                </button>
            </div>
        </form>
    </div>

    <!-- Historial de Entregas Previas Registradas -->
    @if($order->deliveries->count() > 0)
    <div class="card-3d p-6 border border-slate-200/80 bg-white">
        <h3 class="font-display text-sm font-black uppercase tracking-wider text-slate-800 mb-3 flex items-center space-x-2">
            <i class="fas fa-history text-cyan-600"></i>
            <span>Historial Forense de Entregas Registradas (21 CFR Part 11)</span>
        </h3>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-100 text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Factura / Remisión</th>
                        <th class="px-4 py-3">ESM</th>
                        <th class="px-4 py-3">Ítem / Presentación</th>
                        <th class="px-4 py-3 text-right">Cant. Recibida</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Registrado Por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-xs">
                    @foreach($order->deliveries as $del)
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-3 font-mono font-bold text-slate-700">{{ \Carbon\Carbon::parse($del->fecha_recepcion)->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 font-mono font-black text-cyan-800">{{ $del->numero_remision_factura }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $del->esm ?? '---' }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $del->item->presentacion ?? $del->item->codigo_item }}</td>
                        <td class="px-4 py-3 font-mono font-black text-right text-emerald-700">+{{ number_format($del->cantidad_recibida, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $del->tipo_entrega === 'TOTAL' ? 'bg-emerald-50 text-emerald-800' : 'bg-blue-50 text-blue-800' }}">
                                {{ $del->tipo_entrega }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 font-medium">{{ $del->user->name ?? 'Sistema' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
