@extends('layouts.app')

@section('header_title', 'Seguimiento y Consulta de Órdenes de Maquila')

@section('content')
<div x-data="maquilaTracking()" x-init="init()" class="space-y-6 max-w-7xl mx-auto pb-12">
    
    <!-- Top Action Banner & Header -->
    <div class="bg-slate-900 rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border border-slate-800 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
            <svg class="w-64 h-64 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </div>

        <div class="z-10">
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 rounded-full text-xs font-semibold uppercase tracking-wider border border-emerald-500/30">
                    Buscador 360° Maquilas
                </span>
                <span class="text-slate-400 text-xs font-mono">
                    {{ $totalOrders ?? 0 }} órdenes sincronizadas
                </span>
            </div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-white mt-2 tracking-tight">
                Control de Producción y Entregas
            </h2>
            <p class="text-slate-300 text-sm mt-1 max-w-xl">
                Consulte la trazabilidad completa por lote, orden de producción o producto, e inspeccione sus entregas parciales en tiempo real.
            </p>
        </div>

        <div class="z-10 flex items-center gap-3">
            <button @click="showUploadModal = true" class="inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-sm rounded-xl transition-all shadow-lg hover:shadow-emerald-600/30 active:scale-95 space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>Subir / Sincronizar Excel</span>
            </button>
        </div>
    </div>

    <!-- Buscador Reactivo con Sugerencias Flotantes -->
    <div class="relative max-w-3xl mx-auto">
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
            Buscar Lote, OP, Producto o Maquilador
        </label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input 
                type="text" 
                x-model="searchQuery" 
                @input.debounce.300ms="onSearchInput()"
                @focus="if(suggestions.length) showSuggestions = true"
                @click.away="showSuggestions = false"
                placeholder="Ingrese N° de Lote (ej. LOTE123), OP, o nombre del producto..." 
                class="w-full pl-12 pr-12 py-4 bg-white border border-slate-300 rounded-2xl shadow-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-base md:text-lg transition-all"
            />
            <div x-show="isLoadingSearch" class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                <svg class="animate-spin h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown de Sugerencias Flotantes -->
        <div 
            x-show="showSuggestions && suggestions.length > 0" 
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1"
            class="absolute left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden divide-y divide-slate-100 max-h-96 overflow-y-auto"
        >
            <template x-for="item in suggestions" :key="item.id">
                <div 
                    @click="selectSuggestion(item)"
                    class="p-4 hover:bg-slate-50 cursor-pointer transition-colors flex items-center justify-between group"
                >
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-slate-900 text-base group-hover:text-emerald-600 transition-colors" x-text="item.lote"></span>
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-semibold rounded-md border border-slate-200" x-text="item.maquilador"></span>
                            <template x-if="item.op">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-md border border-blue-200" x-text="'OP: ' + item.op"></span>
                            </template>
                        </div>
                        <p class="text-sm text-slate-600 truncate max-w-lg" x-text="item.descripcion || 'Sin descripción'"></p>
                    </div>
                    <div class="text-right">
                        <span 
                            class="inline-block px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                            :class="{
                                'bg-emerald-100 text-emerald-800': item.estatus && item.estatus.toUpperCase().includes('ABIER'),
                                'bg-slate-100 text-slate-700': item.estatus && item.estatus.toUpperCase().includes('CERR'),
                                'bg-amber-100 text-amber-800': !item.estatus || (!item.estatus.toUpperCase().includes('ABIER') && !item.estatus.toUpperCase().includes('CERR'))
                            }"
                            x-text="item.estatus || 'N/A'"
                        ></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Loading State Detalle -->
    <div x-show="isLoadingDetail" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-slate-200 my-8">
        <div class="inline-flex items-center space-x-3 text-emerald-600 font-semibold">
            <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Cargando trazabilidad 360° del Lote...</span>
        </div>
    </div>

    <!-- State cuando no hay selección -->
    <div x-show="!selectedOrder && !isLoadingDetail" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-slate-200 text-slate-500 my-8">
        <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        <h3 class="text-lg font-bold text-slate-700">Sin Lote Seleccionado</h3>
        <p class="text-sm text-slate-500 max-w-md mx-auto mt-1">Escriba en el buscador el número de Lote, OP o producto para visualizar la tarjeta 360° con sus métricas y tabla de entregas.</p>
    </div>

    <!-- Vista 360° del Lote Seleccionado -->
    <div x-show="selectedOrder && !isLoadingDetail" x-transition.opacity class="space-y-6">
        
        <!-- Tarjeta Principal del Lote -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-mono font-bold px-2.5 py-1 bg-slate-900 text-emerald-400 rounded-lg" x-text="'LOTE: ' + (selectedOrder ? selectedOrder.order.lote : '')"></span>
                        <span class="text-xs font-semibold px-3 py-1 bg-slate-100 text-slate-700 rounded-lg border border-slate-200" x-text="selectedOrder ? selectedOrder.order.maquilador : ''"></span>
                        <template x-if="selectedOrder && selectedOrder.order.op">
                            <span class="text-xs font-semibold px-3 py-1 bg-blue-50 text-blue-700 rounded-lg border border-blue-200" x-text="'OP: ' + selectedOrder.order.op"></span>
                        </template>
                    </div>
                    <h3 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight mt-1" x-text="selectedOrder ? selectedOrder.order.descripcion : ''"></h3>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Badge Estado -->
                    <div class="text-right">
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Estado</span>
                        <span 
                            class="inline-block px-3 py-1 text-xs font-extrabold rounded-lg uppercase mt-0.5"
                            :class="{
                                'bg-emerald-100 text-emerald-800 border border-emerald-200': selectedOrder && selectedOrder.order.estatus && selectedOrder.order.estatus.toUpperCase().includes('ABIER'),
                                'bg-slate-100 text-slate-800 border border-slate-200': selectedOrder && selectedOrder.order.estatus && selectedOrder.order.estatus.toUpperCase().includes('CERR'),
                                'bg-amber-100 text-amber-800 border border-amber-200': !selectedOrder || !selectedOrder.order.estatus || (!selectedOrder.order.estatus.toUpperCase().includes('ABIER') && !selectedOrder.order.estatus.toUpperCase().includes('CERR'))
                            }"
                            x-text="selectedOrder && selectedOrder.order.estatus ? selectedOrder.order.estatus : 'PENDIENTE'"
                        ></span>
                    </div>

                    <!-- Badge Balance -->
                    <div class="text-right border-l border-slate-200 pl-3">
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Balance</span>
                        <span 
                            class="inline-block px-3 py-1 text-xs font-extrabold rounded-lg uppercase mt-0.5"
                            :class="{
                                'bg-emerald-100 text-emerald-800 border border-emerald-200': selectedOrder && selectedOrder.order.balance && selectedOrder.order.balance.toUpperCase().includes('OK'),
                                'bg-rose-100 text-rose-800 border border-rose-200': selectedOrder && selectedOrder.order.balance && !selectedOrder.order.balance.toUpperCase().includes('OK'),
                                'bg-slate-100 text-slate-700 border border-slate-200': !selectedOrder || !selectedOrder.order.balance
                            }"
                            x-text="selectedOrder && selectedOrder.order.balance ? selectedOrder.order.balance : 'N/A'"
                        ></span>
                    </div>
                </div>
            </div>

            <!-- Fila de Metadatos Detallados -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 text-xs">
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block font-semibold">Código Item</span>
                    <span class="font-bold text-slate-800 mt-0.5 block" x-text="selectedOrder && selectedOrder.order.codigo_item ? selectedOrder.order.codigo_item : 'N/A'"></span>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block font-semibold">Fecha Fabricación</span>
                    <span class="font-bold text-slate-800 mt-0.5 block" x-text="selectedOrder && selectedOrder.order.fecha_fabricacion ? selectedOrder.order.fecha_fabricacion : 'N/A'"></span>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block font-semibold">Fecha Vencimiento</span>
                    <span class="font-bold text-slate-800 mt-0.5 block" x-text="selectedOrder && selectedOrder.order.fecha_vencimiento ? selectedOrder.order.fecha_vencimiento : 'N/A'"></span>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block font-semibold">Despacho Maquila</span>
                    <span class="font-bold text-slate-800 mt-0.5 block" x-text="selectedOrder && selectedOrder.order.fecha_despacho_maquila ? selectedOrder.order.fecha_despacho_maquila : 'N/A'"></span>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block font-semibold">Llegada Aurofarma</span>
                    <span class="font-bold text-slate-800 mt-0.5 block" x-text="selectedOrder && selectedOrder.order.fecha_llegada_aurofarma ? selectedOrder.order.fecha_llegada_aurofarma : 'N/A'"></span>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block font-semibold">Doc. Traslado</span>
                    <span class="font-bold text-slate-800 mt-0.5 block" x-text="selectedOrder && selectedOrder.order.documento_traslado ? selectedOrder.order.documento_traslado : 'N/A'"></span>
                </div>
            </div>
        </div>

        <!-- 4 Cards de KPI -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            
            <!-- Card 1: Cantidad Programada -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 flex flex-col justify-between relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cantidad Programada</span>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-black text-slate-900 tracking-tight" x-text="selectedOrder ? formatNumber(selectedOrder.metrics.cantidad_programada) : '0'"></div>
                    <span class="text-xs text-slate-400 font-medium">Unidades o Kg totales</span>
                </div>
            </div>

            <!-- Card 2: Total Entregado -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 flex flex-col justify-between relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Entregado</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-black text-emerald-600 tracking-tight" x-text="selectedOrder ? formatNumber(selectedOrder.metrics.total_entregado) : '0'"></div>
                    <span class="text-xs text-slate-400 font-medium" x-text="selectedOrder ? selectedOrder.deliveries.length + ' entrega(s) parciales' : '0 entregas'"></span>
                </div>
            </div>

            <!-- Card 3: Saldo Pendiente -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 flex flex-col justify-between relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Saldo Pendiente</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-black text-amber-600 tracking-tight" x-text="selectedOrder ? formatNumber(selectedOrder.metrics.saldo_pendiente) : '0'"></div>
                    <span class="text-xs text-slate-400 font-medium">Restante por despachar</span>
                </div>
            </div>

            <!-- Card 4: Barra de Porcentaje de Avance -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 flex flex-col justify-between relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cumplimiento</span>
                    <span class="text-xl font-black text-slate-900" x-text="selectedOrder ? selectedOrder.metrics.cumplimiento_real + '%' : '0%'"></span>
                </div>
                <div class="mt-4 space-y-2">
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200">
                        <div 
                            class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-700 ease-out" 
                            :style="'width: ' + (selectedOrder ? selectedOrder.metrics.cumplimiento_porcentaje : 0) + '%'"
                        ></div>
                    </div>
                    <span class="text-xs text-slate-400 font-medium block text-right">Porcentaje de avance</span>
                </div>
            </div>

        </div>

        <!-- Tabla de Entregas Parciales -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h4 class="text-base font-bold text-slate-900">Histórico de Entregas Parciales</h4>
                    <p class="text-xs text-slate-500">Detalle de remisiones y cantidades entregadas registradas en la hoja de maquila</p>
                </div>
                <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold" x-text="selectedOrder ? selectedOrder.deliveries.length + ' Registros' : '0'"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 text-xs text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5">N° Entrega</th>
                            <th class="px-6 py-3.5">Documento Remisión</th>
                            <th class="px-6 py-3.5 text-right">Cantidad Entregada</th>
                            <th class="px-6 py-3.5 text-right">% Aporte de la Entrega</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-if="selectedOrder && selectedOrder.deliveries.length === 0">
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                                    No hay entregas parciales registradas para este lote.
                                </td>
                            </tr>
                        </template>
                        <template x-for="delivery in (selectedOrder ? selectedOrder.deliveries : [])" :key="delivery.id">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-900 text-white text-xs font-bold" x-text="'#' + delivery.numero_entrega"></span>
                                </td>
                                <td class="px-6 py-4 font-mono font-semibold text-slate-800" x-text="delivery.documento_remision"></td>
                                <td class="px-6 py-4 text-right font-black text-emerald-600 text-base" x-text="formatNumber(delivery.cantidad_entregada)"></td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-700">
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold" x-text="delivery.porcentaje_aporte + '%'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Observaciones -->
        <template x-if="selectedOrder && selectedOrder.order.observaciones">
            <div class="bg-amber-50 rounded-2xl p-5 border border-amber-200 text-amber-900 space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700 block">Observaciones de la Orden</span>
                <p class="text-sm" x-text="selectedOrder.order.observaciones"></p>
            </div>
        </template>

    </div>

    <!-- Modal de Carga / Actualización Excel -->
    <div 
        x-show="showUploadModal" 
        x-transition.opacity 
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div 
            @click.away="if(!isUploading) showUploadModal = false"
            class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-200"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Sincronizar Archivo Excel</h3>
                        <p class="text-xs text-slate-500">Actualizar Control_de_Produccion_Aurofarma_2026.xlsx</p>
                    </div>
                </div>
                <button @click="showUploadModal = false" :disabled="isUploading" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form @submit.prevent="uploadExcel($event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                        Seleccionar Archivo (.xlsx)
                    </label>
                    <input 
                        type="file" 
                        name="excel_file" 
                        accept=".xlsx, .xls"
                        required
                        :disabled="isUploading"
                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all border border-slate-200 rounded-2xl p-1"
                    />
                </div>

                <div x-show="isUploading" class="space-y-2 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                    <div class="flex items-center space-x-3 text-emerald-600 font-semibold text-sm">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Sincronizando pestañas y lotes...</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button 
                        type="button" 
                        @click="showUploadModal = false" 
                        :disabled="isUploading"
                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm rounded-xl transition-all"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        :disabled="isUploading"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm rounded-xl transition-all shadow-lg hover:shadow-emerald-600/30 flex items-center space-x-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Procesar Sincronización</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function maquilaTracking() {
    return {
        searchQuery: '',
        suggestions: [],
        showSuggestions: false,
        isLoadingSearch: false,
        isLoadingDetail: false,
        selectedOrder: null,
        showUploadModal: false,
        isUploading: false,

        init() {
            // If URL contains lote parameter, auto load
            const urlParams = new URLSearchParams(window.location.search);
            const loteParam = urlParams.get('lote');
            if (loteParam) {
                this.searchQuery = loteParam;
                this.fetchDetail(loteParam);
            }
        },

        onSearchInput() {
            if (this.searchQuery.trim().length < 2) {
                this.suggestions = [];
                this.showSuggestions = false;
                return;
            }

            this.isLoadingSearch = true;
            axios.get('/api/maquilas/buscar', {
                params: { q: this.searchQuery }
            })
            .then(res => {
                if (res.data && res.data.success) {
                    this.suggestions = res.data.data;
                    this.showSuggestions = true;
                }
            })
            .catch(err => {
                console.error("Error buscando maquilas:", err);
            })
            .finally(() => {
                this.isLoadingSearch = false;
            });
        },

        selectSuggestion(item) {
            this.searchQuery = item.lote;
            this.showSuggestions = false;
            this.fetchDetail(item.lote);
        },

        fetchDetail(lote) {
            this.isLoadingDetail = true;
            axios.get(`/api/maquilas/detalle/${encodeURIComponent(lote)}`)
            .then(res => {
                if (res.data && res.data.success) {
                    this.selectedOrder = res.data.data;
                }
            })
            .catch(err => {
                const msg = err.response && err.response.data && err.response.data.message 
                    ? err.response.data.message 
                    : 'No se pudo obtener el detalle del lote';
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Consulta',
                    text: msg
                });
                this.selectedOrder = null;
            })
            .finally(() => {
                this.isLoadingDetail = false;
            });
        },

        uploadExcel(event) {
            const formData = new FormData(event.target);
            this.isUploading = true;

            axios.post('/maquilas/subir-excel', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(res => {
                if (res.data && res.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sincronización Exitosa',
                        text: res.data.message || 'El libro de Excel fue procesado correctamente.',
                        confirmButtonColor: '#059669'
                    });
                    this.showUploadModal = false;
                    event.target.reset();
                    // If an order is currently open, refresh it
                    if (this.selectedOrder && this.selectedOrder.order.lote) {
                        this.fetchDetail(this.selectedOrder.order.lote);
                    }
                }
            })
            .catch(err => {
                const msg = err.response && err.response.data && err.response.data.message 
                    ? err.response.data.message 
                    : 'Error al sincronizar el archivo Excel.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Sincronización',
                    text: msg
                });
            })
            .finally(() => {
                this.isUploading = false;
            });
        },

        formatNumber(val) {
            if (val === null || val === undefined) return '0.00';
            return parseFloat(val).toLocaleString('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
}
</script>
@endpush
@endsection
