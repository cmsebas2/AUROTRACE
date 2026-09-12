@extends('layouts.app')

@section('header_title', 'Llegada de Batch Record & Asignación de Archivo Físico')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="llegadaBrPage()">

    <!-- Navegación Superior -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <a href="{{ route('maquila.show', $order->id) }}" 
               class="text-xs font-black uppercase tracking-wider text-slate-500 hover:text-cyan-600 flex items-center transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver al Radar OP {{ $order->op }}
            </a>
            <span class="text-slate-300">|</span>
            <a href="{{ route('maquila.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
                Torre de Control Maquilas
            </a>
        </div>
        <div class="flex items-center space-x-2 self-start sm:self-auto">
            <span class="px-3 py-1 rounded-full bg-purple-50 text-purple-800 text-[11px] font-black border border-purple-200 shadow-sm flex items-center space-x-1.5">
                <i class="fas fa-file-medical text-purple-600"></i>
                <span>PASO 4: ARCHIVO FÍSICO BATCH RECORD</span>
            </span>
            <span class="px-3 py-1 rounded-full bg-cyan-50 text-cyan-800 text-[11px] font-mono font-black border border-cyan-200">
                ODM: {{ $order->numero_odm }}
            </span>
        </div>
    </div>

    <!-- Ficha Técnica de la OP de Maquila -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

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
                    <span>Maquilador: <strong class="text-slate-800">{{ $order->maquilador->nombre ?? 'N/A' }}</strong></span>
                    <span>Fabricación: <strong class="text-slate-800">{{ $order->fecha_fabricacion }}</strong></span>
                    <span>Vencimiento: <strong class="text-slate-800">{{ $order->fecha_vencimiento }}</strong></span>
                </div>
            </div>

            <!-- KPIs de Control -->
            <div class="grid grid-cols-3 gap-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-center">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Programado</span>
                    <p class="font-display text-lg font-black text-slate-800">{{ number_format($order->total_programado > 0 ? $order->total_programado : $order->tamano_lote, 2) }}</p>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-cyan-700">Recibido Almacén</span>
                    <p class="font-display text-lg font-black text-cyan-600">{{ number_format($order->total_recibido, 2) }}</p>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-purple-700">Rendimiento</span>
                    <p class="font-display text-lg font-black text-purple-700" x-text="rendimientoProyectado + '%'"></p>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm flex items-center space-x-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
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

    <!-- Formulario Principal -->
    <form action="{{ route('maquila.llegada_br', $order->id, false) }}" method="POST" class="space-y-6">
        @csrf

        <!-- SECCIÓN 1: Desglose por Presentación y Cálculo de Unidades Fabricadas -->
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-md">
                        <i class="fas fa-boxes text-lg"></i>
                    </div>
                    <div>
                        <h2 class="font-display text-lg font-black text-slate-900 tracking-tight">
                            Desglose de Unidades Fabricadas por Presentación
                        </h2>
                        <p class="text-xs text-slate-500">
                            El <strong>Total PT Fabricado</strong> se calcula automáticamente sumando las unidades fabricadas de cada presentación.
                        </p>
                    </div>
                </div>

                <div class="px-3.5 py-1.5 rounded-xl bg-purple-50 border border-purple-200 flex items-center space-x-2 self-start sm:self-auto">
                    <span class="w-2 h-2 rounded-full bg-purple-600 animate-pulse"></span>
                    <span class="text-xs font-bold text-purple-900">Suma Dinámica en Tiempo Real</span>
                </div>
            </div>

            <!-- Tabla de Presentaciones -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Código Ítem</th>
                            <th class="px-4 py-3">Presentación / Referencia</th>
                            <th class="px-4 py-3 text-center">Unidad</th>
                            <th class="px-4 py-3 text-right">Cant. Programada</th>
                            <th class="px-4 py-3 text-right">Cant. Recibida Bodega</th>
                            <th class="px-4 py-3 text-right w-56">Unidades Fabricadas *</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700 bg-white">
                        <template x-for="(item, index) in items" :key="item.id || index">
                            <tr class="hover:bg-purple-50/40 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-slate-900" x-text="item.codigo_item"></td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900" x-text="item.presentacion"></div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-mono font-bold" x-text="item.unidad_medida"></span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono" x-text="formatearNumero(item.cantidad_programada)"></td>
                                <td class="px-4 py-3 text-right font-mono font-bold" 
                                    :class="item.cantidad_recibida > 0 ? 'text-cyan-700' : 'text-slate-400'"
                                    x-text="formatearNumero(item.cantidad_recibida)"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="relative flex items-center justify-end">
                                        <input type="number" step="any" min="0" 
                                               :name="'cantidades_fabricadas[' + item.id + ']'"
                                               x-model.number="item.cantidad_fabricada"
                                               @input="recalcularTotal()"
                                               required
                                               class="w-40 px-3 py-1.5 rounded-xl border border-purple-300 focus:border-purple-600 focus:ring-2 focus:ring-purple-200 text-right font-mono text-xs font-black text-slate-900 bg-purple-50/20">
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-50 border-t-2 border-slate-300">
                        <tr>
                            <td colspan="4" class="px-4 py-3 font-display font-black text-slate-800 text-right uppercase tracking-wider">
                                Total Unidades Fabricadas (Suma de Presentaciones):
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-cyan-800" x-text="formatearNumero(totalRecibidoBodega)"></td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-display font-black text-base text-purple-700 font-mono" x-text="formatearNumero(totalPT) + ' UND'"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Fila de Metadatos de la Entrega & Cálculo Final -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <!-- Fecha de Llegada del BR -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                        Fecha Llegada BR <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha_llegada_br" required x-model="fechaLlegada"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 text-xs font-bold text-slate-800 shadow-sm">
                </div>

                <!-- Total PT Fabricado (Arrastrado de la Suma) -->
                <div>
                    <label class="block text-xs font-black text-purple-900 uppercase tracking-wider mb-1 flex items-center justify-between">
                        <span>Total PT Fabricado (Unidades) <span class="text-red-500">*</span></span>
                        <span class="text-[10px] text-emerald-600 font-bold">✓ Arrastrado de suma</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="any" min="0.001" name="total_producto_terminado_fabricado" 
                               x-model.number="totalPT"
                               readonly
                               required 
                               placeholder="0.00"
                               class="w-full px-3.5 py-2.5 rounded-xl border-2 border-purple-400 bg-purple-50/60 focus:outline-none text-sm font-mono font-black text-purple-950 shadow-sm cursor-default">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-purple-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Retención & Destrucción ICA -->
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                        Retención & Destrucción Normativa ICA
                    </label>
                    <div class="px-3.5 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-xs font-mono font-bold text-amber-900 flex items-center space-x-2 shadow-sm">
                        <i class="fas fa-calendar-times text-amber-600 text-sm"></i>
                        <span>{{ $order->fecha_destruccion_br ? 'Destrucción BR: ' . $order->fecha_destruccion_br : 'Calculada (+1 año post-venc)' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: Selector Interactivo de Posición Física de Archivo (RACK 1 CENTRAL) -->
        <div class="bg-slate-950 text-white rounded-3xl p-6 sm:p-8 border border-slate-800 shadow-2xl space-y-6">
            
            <!-- Header Consola Archivo -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-2xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-mono font-black text-sm border border-cyan-500/40 shadow-3d-cyan">
                        R1
                    </div>
                    <div>
                        <h3 class="font-display font-black text-base uppercase tracking-wider text-cyan-300">
                            Seleccionar Casilla Física de Archivo (RACK 1 CENTRAL)
                        </h3>
                        <p class="text-xs text-slate-400 font-medium">
                            5 Niveles · 210 Archivadores · 4 Slots por archivador (Capacidad Total: 840 Batch Records)
                        </p>
                    </div>
                </div>

                <!-- Botón Sugerir Primer Slot Libre -->
                <button type="button" @click="sugerirPrimerSlotLibre()" 
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-slate-950 bg-gradient-to-r from-cyan-400 to-emerald-400 hover:from-cyan-300 hover:to-emerald-300 shadow-lg hover:shadow-cyan-500/30 transition-all flex items-center space-x-2 self-start sm:self-auto transform hover:-translate-y-0.5">
                    <i class="fas fa-bolt text-xs"></i>
                    <span>Sugerir Primer Slot Libre</span>
                </button>
            </div>

            <!-- Controles de Nivel, Cara y Leyenda -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
                
                <!-- Selector de Nivel -->
                <div class="flex items-center space-x-2.5">
                    <span class="text-xs font-black uppercase text-slate-400 tracking-wider">Nivel:</span>
                    <div class="inline-flex p-1 bg-slate-950 rounded-xl border border-slate-800">
                        <template x-for="n in [1, 2, 3, 4, 5]" :key="n">
                            <button type="button" @click="llegadaBrNivel = n"
                                    :class="llegadaBrNivel === n ? 'bg-cyan-500 text-slate-950 font-black shadow-md' : 'text-slate-400 hover:text-white font-bold'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs font-mono transition-all">
                                <span x-text="'Nivel 0' + n"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Selector de Cara -->
                <div class="flex items-center space-x-2.5">
                    <span class="text-xs font-black uppercase text-slate-400 tracking-wider">Cara:</span>
                    <div class="inline-flex p-1 bg-slate-950 rounded-xl border border-slate-800">
                        <button type="button" @click="llegadaBrCara = 'VISIBLE'"
                                :class="llegadaBrCara === 'VISIBLE' ? 'bg-[#005889] text-white font-black shadow-md' : 'text-slate-400 hover:text-white font-bold'"
                                class="px-3.5 py-1.5 rounded-lg text-xs transition-all uppercase tracking-wider">
                            Frente (Impares)
                        </button>
                        <button type="button" @click="llegadaBrCara = 'POSTERIOR'"
                                :class="llegadaBrCara === 'POSTERIOR' ? 'bg-[#005889] text-white font-black shadow-md' : 'text-slate-400 hover:text-white font-bold'"
                                class="px-3.5 py-1.5 rounded-lg text-xs transition-all uppercase tracking-wider">
                            Atrás (Pares)
                        </button>
                    </div>
                </div>

                <!-- Leyenda de Estados -->
                <div class="flex items-center space-x-4 text-xs font-bold text-slate-400">
                    <span class="flex items-center">
                        <span class="w-3 h-3 rounded bg-emerald-400 mr-1.5 shadow-[0_0_8px_#10B981]"></span> Seleccionado
                    </span>
                    <span class="flex items-center">
                        <span class="w-3 h-3 rounded bg-red-600/80 mr-1.5"></span> Ocupado
                    </span>
                    <span class="flex items-center">
                        <span class="w-3 h-3 rounded bg-slate-700 mr-1.5"></span> Libre
                    </span>
                </div>
            </div>

            <!-- Balda Física con los 21 Archivadores -->
            <div class="p-4 bg-slate-900/90 rounded-2xl border border-slate-800 overflow-x-auto">
                <div class="grid grid-cols-7 sm:grid-cols-11 md:grid-cols-21 gap-2 min-w-[840px]">
                    <template x-for="archNum in getArchivadoresNivel()" :key="archNum">
                        <div :class="llegadaBrArchivador === archNum ? 'ring-2 ring-cyan-400 bg-cyan-950/70 border-cyan-400 shadow-[0_0_15px_rgba(6,182,212,0.25)]' : 'bg-slate-900 border-slate-800 hover:border-slate-700'"
                             class="rounded-xl p-2 flex flex-col justify-between border transition-all h-32">
                            
                            <!-- Número de Archivador -->
                            <div class="text-center">
                                <span class="text-[10px] font-mono font-black block"
                                      :class="llegadaBrArchivador === archNum ? 'text-cyan-300 font-black' : 'text-slate-300'"
                                      x-text="'#' + (archNum < 10 ? '0' + archNum : archNum)"></span>
                            </div>

                            <!-- Aro central del Archivador Físico -->
                            <div class="w-3 h-3 rounded-full border border-slate-600 bg-slate-950 mx-auto shadow-inner"></div>

                            <!-- 4 Slots (S1..S4) -->
                            <div class="grid grid-cols-2 gap-1.5">
                                <template x-for="s in [1, 2, 3, 4]" :key="s">
                                    <button type="button" 
                                            @click="seleccionarSlot(archNum, s)"
                                            :disabled="isSlotOccupied(archNum, s)"
                                            :title="getSlotTooltip(archNum, s)"
                                            :class="getSlotClass(archNum, s)"
                                            class="h-5 rounded-md font-mono text-[9px] flex items-center justify-center transition-all font-black">
                                        <span x-text="'S' + s"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Banner de Confirmación de Posición Asignada -->
            <div class="p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all"
                 :class="llegadaBrPosicion ? 'bg-emerald-950/50 border-emerald-500/50 text-emerald-300 shadow-[0_0_20px_rgba(16,185,129,0.2)]' : 'bg-amber-950/40 border-amber-600/50 text-amber-300'">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-base flex-shrink-0"
                         :class="llegadaBrPosicion ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-amber-500/20 text-amber-400 border border-amber-500/40'">
                        <i class="fas" :class="llegadaBrPosicion ? 'fa-check-circle text-lg' : 'fa-hand-pointer animate-pulse'"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-black uppercase tracking-wider block"
                              x-text="llegadaBrPosicion ? 'Casilla Asignada en Archivo Físico:' : 'Paso Requerido:'"></span>
                        <strong class="font-mono text-base font-black text-white block tracking-wide" 
                                x-text="llegadaBrPosicion || 'Haga clic en un slot disponible (S1..S4) o presione Sugerir Primer Slot Libre'"></strong>
                    </div>
                </div>

                <input type="hidden" name="posicion_archivo_fisico" :value="llegadaBrPosicion" required>

                <template x-if="llegadaBrPosicion">
                    <div class="flex items-center space-x-2 self-start sm:self-auto">
                        <span class="px-3.5 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-300 text-xs font-mono font-bold border border-emerald-500/30 flex items-center space-x-1.5">
                            <i class="fas fa-shield-check text-emerald-400"></i>
                            <span>Slot Disponible Verificado</span>
                        </span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Notificación Legal ICA -->
        <div class="p-4 bg-purple-50 rounded-2xl border border-purple-200 text-xs text-purple-900 flex items-center space-x-3">
            <div class="w-8 h-8 rounded-xl bg-purple-200 text-purple-800 flex items-center justify-center flex-shrink-0 font-bold">
                <i class="fas fa-info text-sm"></i>
            </div>
            <div>
                Al confirmar la llegada y archivar, se calculará el <strong>Rendimiento Operativo Real</strong> de la orden en base al total fabricado, 
                la ubicación quedará sincronizada en tiempo real con el módulo de <strong>Consultas BR</strong> y la orden avanzará al estado <strong>BR REVISION DT</strong>.
            </div>
        </div>

        <!-- Barra de Acciones / Submit -->
        <div class="card-3d p-4 bg-white border border-slate-200 flex items-center justify-between">
            <a href="{{ route('maquila.show', $order->id) }}" 
               class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                Cancelar y Volver
            </a>

            <button type="submit" 
                    :disabled="!llegadaBrPosicion || totalPT <= 0"
                    :class="(!llegadaBrPosicion || totalPT <= 0) ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-md hover:shadow-xl hover:-translate-y-0.5'"
                    class="px-8 py-3 rounded-xl text-xs font-black uppercase tracking-wider text-white transition-all flex items-center space-x-2.5">
                <i class="fas fa-save text-sm"></i>
                <span>Confirmar Llegada & Archivar Batch Record</span>
            </button>
        </div>
    </form>
</div>

<script>
function llegadaBrPage() {
    return {
        items: @json($itemsData),
        orderId: {{ $order->id }},
        orderLote: '{{ $order->lote }}',
        fechaLlegada: '{{ $order->fecha_llegada_br ? $order->fecha_llegada_br->format("Y-m-d") : date("Y-m-d") }}',
        totalPT: {{ $totalFabricadoSum }},
        baseProgramada: {{ $order->total_programado > 0 ? $order->total_programado : $order->tamano_lote }},
        llegadaBrNivel: 1,
        llegadaBrCara: 'VISIBLE',
        llegadaBrArchivador: null,
        llegadaBrSlot: 1,
        llegadaBrPosicion: '{{ $order->posicion_archivo_fisico ?? "" }}',
        archiveOccupiedMap: {},
        archiveLoading: false,

        init() {
            this.recalcularTotal();
            if (this.llegadaBrPosicion) {
                const matchArch = this.llegadaBrPosicion.match(/ARCHIVADOR\s*#?\s*(\d+)/i);
                if (matchArch) {
                    const num = parseInt(matchArch[1]);
                    this.llegadaBrArchivador = num;
                    this.llegadaBrNivel = Math.ceil(num / 42);
                    this.llegadaBrCara = (num % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
                }
                const matchSlot = this.llegadaBrPosicion.match(/SLOT\s*([1-4])/i);
                if (matchSlot) {
                    this.llegadaBrSlot = parseInt(matchSlot[1]);
                }
            }
            this.cargarSlotsOcupados();
        },

        get totalRecibidoBodega() {
            return this.items.reduce((acc, it) => acc + (parseFloat(it.cantidad_recibida) || 0), 0);
        },

        get rendimientoProyectado() {
            if (this.baseProgramada <= 0) return '100.00';
            const r = (this.totalPT / this.baseProgramada) * 100;
            return r.toFixed(2);
        },

        recalcularTotal() {
            let sum = 0;
            for (let i = 0; i < this.items.length; i++) {
                const val = parseFloat(this.items[i].cantidad_fabricada) || 0;
                sum += val;
            }
            this.totalPT = Math.round(sum * 1000) / 1000;
        },

        formatearNumero(num) {
            return (parseFloat(num) || 0).toLocaleString('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        cargarSlotsOcupados() {
            this.archiveLoading = true;
            fetch('/archive-locations/occupied', {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                this.archiveLoading = false;
                if (data && data.occupied) {
                    this.archiveOccupiedMap = data.occupied;
                }
                if (!this.llegadaBrPosicion) {
                    this.sugerirPrimerSlotLibre();
                }
            })
            .catch(err => {
                this.archiveLoading = false;
                console.error('Error cargando slots ocupados:', err);
                if (!this.llegadaBrPosicion) {
                    this.sugerirPrimerSlotLibre();
                }
            });
        },

        getArchivadoresNivel() {
            const list = [];
            const base = (this.llegadaBrNivel - 1) * 42;
            const isVisible = (this.llegadaBrCara === 'VISIBLE');
            for (let i = 0; i < 21; i++) {
                const num = base + (i * 2 + (isVisible ? 1 : 2));
                list.push(num);
            }
            return list;
        },

        isSlotOccupied(archNum, slot) {
            const key = `${archNum}_${slot}`;
            const occ = this.archiveOccupiedMap[key];
            if (!occ) return false;
            if (this.orderLote && occ.lote && occ.lote.toUpperCase().trim() === this.orderLote.toUpperCase().trim()) {
                return false;
            }
            if (this.orderId && occ.order_id == this.orderId) {
                return false;
            }
            return true;
        },

        getSlotTooltip(archNum, slot) {
            const key = `${archNum}_${slot}`;
            const occ = this.archiveOccupiedMap[key];
            if (this.isSlotOccupied(archNum, slot)) {
                return `OCUPADO: Lote ${occ.lote} (OP: ${occ.op || 'N/A'})`;
            }
            if (occ) {
                return `ASIGNADO A ESTA ORDEN: Slot ${slot}`;
            }
            return `DISPONIBLE: Archivador #${archNum} · Slot ${slot}`;
        },

        getSlotClass(archNum, slot) {
            const isSelected = (this.llegadaBrArchivador === archNum && this.llegadaBrSlot === slot);
            if (isSelected) {
                return 'bg-emerald-400 text-slate-950 font-black ring-2 ring-emerald-300 shadow-[0_0_12px_#10B981] scale-110 z-10';
            }
            if (this.isSlotOccupied(archNum, slot)) {
                return 'bg-red-950/80 text-red-400 border border-red-700/60 opacity-60 cursor-not-allowed';
            }
            return 'bg-slate-800 text-slate-300 hover:bg-cyan-600 hover:text-white font-bold cursor-pointer';
        },

        seleccionarSlot(archNum, slot) {
            if (this.isSlotOccupied(archNum, slot)) {
                const key = `${archNum}_${slot}`;
                const occ = this.archiveOccupiedMap[key];
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Slot Ocupado',
                        text: `El Slot ${slot} del Archivador #${archNum} ya está ocupado por el lote ${occ.lote}. Seleccione un slot disponible.`,
                        confirmButtonColor: '#005889'
                    });
                } else {
                    alert(`El Slot ${slot} del Archivador #${archNum} ya está ocupado por el lote ${occ.lote}.`);
                }
                return;
            }

            this.llegadaBrArchivador = archNum;
            this.llegadaBrSlot = slot;
            this.llegadaBrNivel = Math.ceil(archNum / 42);
            this.llegadaBrCara = (archNum % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
            this.llegadaBrPosicion = `RACK 1 · NIVEL 0${this.llegadaBrNivel} · ARCHIVADOR #${archNum} · SLOT ${slot}`;
        },

        sugerirPrimerSlotLibre() {
            for (let n = 1; n <= 5; n++) {
                const base = (n - 1) * 42;
                for (let i = 0; i < 21; i++) {
                    const arch = base + (i * 2 + 1);
                    for (let s = 1; s <= 4; s++) {
                        if (!this.isSlotOccupied(arch, s)) {
                            this.llegadaBrNivel = n;
                            this.llegadaBrCara = 'VISIBLE';
                            this.seleccionarSlot(arch, s);
                            return;
                        }
                    }
                }
                for (let i = 0; i < 21; i++) {
                    const arch = base + (i * 2 + 2);
                    for (let s = 1; s <= 4; s++) {
                        if (!this.isSlotOccupied(arch, s)) {
                            this.llegadaBrNivel = n;
                            this.llegadaBrCara = 'POSTERIOR';
                            this.seleccionarSlot(arch, s);
                            return;
                        }
                    }
                }
            }
        }
    };
}
</script>
@endsection
