@extends('layouts.app')

@section('header_title', 'Radar 360° de Trazabilidad - ' . $order->numero_odm)

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12 animate-fade-in" x-data="{ showDeliveryModal: false, showCloseModal: false, selectedItemId: null }">
    
    <!-- Hero Header -->
    <div class="bg-[#0A2540] text-white p-6 rounded-2xl shadow-xl border border-slate-700 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center space-x-3">
                <span class="bg-[#04BFAD] text-slate-950 text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider">ODM</span>
                <h1 class="text-3xl font-black text-white tracking-tight">{{ $order->numero_odm }}</h1>
                @if($order->numero_sdm)
                <span class="text-xs text-slate-300 font-bold bg-slate-800 px-3 py-1 rounded-full">SDM: {{ $order->numero_sdm }}</span>
                @endif
            </div>
            <p class="text-xs text-slate-300 font-medium mt-2 flex items-center space-x-2">
                <span>Maquilador: <strong>{{ $order->maquilador->nombre }}</strong> (NIT: {{ $order->maquilador->nit }})</span>
                <span>•</span>
                <span>Tipo: <strong class="uppercase text-[#04BFAD]">{{ $order->tipo_producto }}</strong></span>
            </p>
        </div>

        <div class="flex items-center space-x-3">
            @if($order->estado !== 'liquidada' && $order->estado !== 'cerrada_tecnicamente')
            <button @click="showCloseModal = true" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-4 py-2.5 rounded-xl shadow-md transition text-xs flex items-center space-x-2">
                <i class="fa-solid fa-[#0A2540] fa-lock"></i>
                <span>Liquidar / Cierre con Doble Firma</span>
            </button>
            @else
            <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-4 py-2 rounded-xl text-xs font-black uppercase flex items-center space-x-2">
                <i class="fa-solid fa-seal-question text-emerald-400"></i>
                <span>Orden Liquidada & Cerrada</span>
            </span>
            @endif

            <a href="{{ route('maquila.index') }}" class="bg-slate-800 hover:bg-slate-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs transition">
                &larr; Volver
            </a>
        </div>
    </div>

    <!-- Barra de Progreso Semafórica 360° -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-2">
            <span class="text-xs font-black text-slate-500 uppercase tracking-wider">Avance Global de Recepción de Lotes</span>
            <span class="text-sm font-black text-[#0A2540]">{{ $order->porcentaje_avance_global }}%</span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-4 overflow-hidden border border-slate-200">
            <div class="h-4 rounded-full transition-all duration-700 {{ $order->porcentaje_avance_global >= 90 ? 'bg-emerald-500' : ($order->porcentaje_avance_global >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                 style="width: {{ min(100, $order->porcentaje_avance_global) }}%"></div>
        </div>
        <div class="grid grid-cols-3 gap-4 mt-4 text-center text-xs">
            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                <span class="text-slate-400 font-bold block uppercase">Programado Total</span>
                <span class="font-black text-slate-800 text-sm">{{ number_format($order->total_programado, 2) }}</span>
            </div>
            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                <span class="text-slate-400 font-bold block uppercase">Recibido a la Fecha</span>
                <span class="font-black text-emerald-600 text-sm">{{ number_format($order->total_recibido, 2) }}</span>
            </div>
            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                <span class="text-slate-400 font-bold block uppercase">Saldo Pendiente</span>
                <span class="font-black text-amber-600 text-sm">{{ number_format($order->saldo_total, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Tabla de Ítems Programados con Motor de Cálculo Yield % -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-black text-[#0A2540] uppercase tracking-wider">Ítems Programados en la Orden</h3>
            <span class="text-xs text-slate-400 font-bold">Motor de Cálculo Yield % v2.0</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 font-black uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Código / Producto</th>
                        <th class="px-5 py-3.5">Lote Físico</th>
                        <th class="px-5 py-3.5 text-right">Programado</th>
                        <th class="px-5 py-3.5 text-right">Recibido</th>
                        <th class="px-5 py-3.5 text-right">Saldo</th>
                        <th class="px-5 py-3.5 text-center">Yield % (Rendimiento)</th>
                        <th class="px-5 py-3.5 text-center">Desviación</th>
                        <th class="px-5 py-3.5 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($order->items as $item)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4">
                            <span class="font-black text-[#0A2540] block">{{ $item->codigo_item }}</span>
                            <span class="text-slate-600 font-bold block text-xs">{{ $item->descripcion_producto }}</span>
                            <span class="text-[10px] text-slate-400">{{ $item->presentacion }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="font-black text-slate-800 bg-slate-100 px-2.5 py-1 rounded border border-slate-200">{{ $item->lote_fisico }}</span>
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-slate-800">
                            {{ number_format($item->cantidad_programada, 2) }} {{ $item->unidad_medida }}
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-emerald-600">
                            {{ number_format($item->cantidad_recibida_total, 2) }} {{ $item->unidad_medida }}
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-amber-600">
                            {{ number_format($item->saldo_pendiente, 2) }} {{ $item->unidad_medida }}
                        </td>
                        <td class="px-5 py-4 text-center font-black">
                            <span class="text-sm {{ $item->rendimiento_pct >= 95 && $item->rendimiento_pct <= 105 ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $item->rendimiento_pct }}%
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($item->clasificacion_desviacion === 'Merma')
                            <span class="bg-red-100 text-red-800 text-[10px] font-black px-2 py-0.5 rounded uppercase">Merma ({{ $item->desviacion_rendimiento }}%)</span>
                            @elseif($item->clasificacion_desviacion === 'Exceso')
                            <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded uppercase">Exceso (+{{ $item->desviacion_rendimiento }}%)</span>
                            @else
                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded uppercase">Conforme</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($order->estado !== 'liquidada' && $order->estado !== 'cerrada_tecnicamente')
                            <button @click="selectedItemId = {{ $item->id }}; showDeliveryModal = true" 
                                    class="bg-[#04BFAD] hover:bg-[#048ABF] text-slate-950 font-black px-3 py-1.5 rounded-lg text-xs transition shadow">
                                + Registrar Entrega
                            </button>
                            @else
                            <span class="text-slate-400 font-bold text-[11px]">Cerrado</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Historial de Remisiones y Firmas Electrónicas Parte 11 -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-black text-[#0A2540] uppercase tracking-wider">Historial de Entregas Parciales y Firmas Electrónicas (Audit Trail)</h3>
            <span class="text-xs text-slate-400 font-bold"><i class="fa-solid fa-shield-halved text-emerald-500 mr-1"></i> 21 CFR Part 11 Hash Standard</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 font-black uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Fecha Recepción</th>
                        <th class="px-5 py-3.5">N° Remisión / Factura</th>
                        <th class="px-5 py-3.5">Ítem y Lote</th>
                        <th class="px-5 py-3.5 text-right">Cantidad Recibida</th>
                        <th class="px-5 py-3.5 text-right">% Aporte al Lote</th>
                        <th class="px-5 py-3.5">Sello de Firma Electrónica</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($order->deliveries as $del)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-4 font-bold text-slate-800">
                            {{ $del->fecha_recepcion->format('Y-m-d') }}
                        </td>
                        <td class="px-5 py-4 font-black text-[#0A2540]">
                            {{ $del->numero_remision_factura }}
                        </td>
                        <td class="px-5 py-4">
                            <span class="font-bold block text-slate-800">{{ $del->item->descripcion_producto }}</span>
                            <span class="text-[10px] text-slate-400">Lote: {{ $del->item->lote_fisico }}</span>
                        </td>
                        <td class="px-5 py-4 text-right font-black text-emerald-600">
                            +{{ number_format($del->cantidad_recibida, 2) }} {{ $del->item->unidad_medida }}
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-slate-700">
                            {{ $del->porcentaje_aporte_lote }}%
                        </td>
                        <td class="px-5 py-4">
                            @if($del->signature)
                            <div class="bg-slate-50 border border-slate-200 border-l-4 border-l-[#04BFAD] p-2 rounded text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="font-black text-[#0A2540] uppercase">{{ $del->signature->user->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $del->signature->signed_at->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="text-[9px] font-mono text-slate-400 truncate mt-0.5" title="SHA-256: {{ $del->hash_integridad }}">
                                    SHA-256: {{ substr($del->hash_integridad, 0, 16) }}...
                                </div>
                            </div>
                            @else
                            <span class="text-red-500 font-bold text-[10px]">Pendiente de Firma</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-slate-400">
                            No se han registrado entregas parciales para esta orden.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: Registro de Entrega Parcial con Firma Electrónica -->
    <div x-show="showDeliveryModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-sm font-black text-[#0A2540] uppercase tracking-wider">Registrar Entrega Parcial (Res. ICA)</h3>
                <button @click="showDeliveryModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
            </div>

            <form :action="`/maquilas/item/${selectedItemId}/delivery`" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">Fecha de Recepción</label>
                    <input type="date" name="fecha_recepcion" value="{{ date('Y-m-d') }}" required class="w-full border border-slate-300 rounded-xl p-2.5 text-xs font-bold">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">N° Remisión / Factura Maquilador</label>
                    <input type="text" name="numero_remision_factura" placeholder="Ej. REM-99482" required class="w-full border border-slate-300 rounded-xl p-2.5 text-xs font-bold">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">Cantidad Recibida Física</label>
                    <input type="number" step="0.001" name="cantidad_recibida" placeholder="0.00" required class="w-full border border-slate-300 rounded-xl p-2.5 text-sm font-black text-emerald-600">
                </div>

                <!-- Credenciales 21 CFR Parte 11 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                    <span class="text-xs font-black text-[#0A2540] uppercase tracking-wider block">Firma Electrónica de Verificación (21 CFR Parte 11)</span>
                    <div>
                        <input type="text" name="username" placeholder="Usuario o Email Verificador" required class="w-full border border-slate-300 rounded-lg p-2 text-xs font-bold">
                    </div>
                    <div>
                        <input type="password" name="password" placeholder="Contraseña de Confirmación" required class="w-full border border-slate-300 rounded-lg p-2 text-xs font-bold">
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="showDeliveryModal = false" class="bg-slate-100 text-slate-600 font-black px-4 py-2 rounded-xl text-xs">Cancelar</button>
                    <button type="submit" class="bg-[#04BFAD] hover:bg-[#048ABF] text-slate-950 font-black px-5 py-2 rounded-xl text-xs uppercase shadow">Firmar y Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Liquidación y Cierre Técnico con Doble Firma Parte 11 -->
    <div x-show="showCloseModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-sm font-black text-[#0A2540] uppercase tracking-wider">Cierre Técnico y Liquidación de ODM (Doble Firma)</h3>
                <button @click="showCloseModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
            </div>

            <form action="{{ route('maquila.close', $order->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">Justificación del Cierre / Observaciones de Liquidación <span class="text-red-500">*</span></label>
                    <textarea name="justificacion" rows="2" required class="w-full border border-slate-300 rounded-xl p-2.5 text-xs font-medium" placeholder="Justifique el rendimiento final de la orden..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <!-- Firma 1: Operador -->
                    <div class="space-y-2">
                        <span class="text-[11px] font-black text-slate-700 uppercase block border-b pb-1">Firma 1: Operador de Producción</span>
                        <input type="text" name="operator_username" placeholder="Usuario Operador" required class="w-full border border-slate-300 rounded-lg p-2 text-xs font-bold">
                        <input type="password" name="operator_password" placeholder="Contraseña Operador" required class="w-full border border-slate-300 rounded-lg p-2 text-xs font-bold">
                    </div>

                    <!-- Firma 2: Calidad -->
                    <div class="space-y-2">
                        <span class="text-[11px] font-black text-slate-700 uppercase block border-b pb-1">Firma 2: Supervisor de Calidad</span>
                        <input type="text" name="quality_username" placeholder="Usuario Calidad" required class="w-full border border-slate-300 rounded-lg p-2 text-xs font-bold">
                        <input type="password" name="quality_password" placeholder="Contraseña Calidad" required class="w-full border border-slate-300 rounded-lg p-2 text-xs font-bold">
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="showCloseModal = false" class="bg-slate-100 text-slate-600 font-black px-4 py-2 rounded-xl text-xs">Cancelar</button>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-5 py-2 rounded-xl text-xs uppercase shadow flex items-center space-x-1">
                        <i class="fa-solid fa-lock"></i>
                        <span>Ejecutar Doble Firma y Liquidar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
