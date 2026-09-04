@extends('layouts.app')

@section('header_title', 'Consultas BR - Archivo Físico 3D')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="consultasBrApp()">

    <!-- Header y Cabecera de la Sala de Archivo 3D -->
    <div class="card-3d p-6 border border-slate-200/80 bg-white relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
            <div class="space-y-1.5">
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-0.5 rounded-full bg-slate-900 text-cyan-300 font-mono text-[10px] font-black uppercase tracking-widest">
                        CFR 21 Part 11 · Archivo Físico Central
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-black border border-emerald-200">
                        ● RACK 1 ACTIVO (5 NIVELES)
                    </span>
                </div>
                <h1 class="font-display text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">
                    Consultas BR · Archivo Físico de Batch Records
                </h1>
                <p class="text-xs text-slate-500 font-medium max-w-2xl">
                    Estantería industrial con <strong>5 niveles verticales</strong> (de arriba hacia abajo: Nivel 1 al 5) y <strong>doble profundidad</strong> (21 archivadores frontales impares y 21 posteriores pares por balda, 4 Batch Records por archivador).
                </p>
            </div>

            <!-- Métricas de Capacidad del Rack 1 (5 Niveles) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-200 min-w-[100px]">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Capacidad Total</span>
                    <span class="font-display text-lg font-black text-slate-800">{{ number_format($capacidadTotalBatch) }}</span>
                    <span class="text-[9px] text-slate-400 font-bold block">Expedientes (BR)</span>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-200 min-w-[100px]">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Archivadores</span>
                    <span class="font-display text-lg font-black text-cyan-700">{{ number_format($totalArchivadores) }}</span>
                    <span class="text-[9px] text-slate-400 font-bold block">Físicos (5 Niveles)</span>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-200 min-w-[100px]">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Lotes Archivados</span>
                    <span class="font-display text-lg font-black text-emerald-600">{{ number_format($totalLotesArchivados) }}</span>
                    <span class="text-[9px] text-slate-400 font-bold block">En Custodia</span>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-200 min-w-[100px]">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Disponibles</span>
                    <span class="font-display text-lg font-black text-amber-600">{{ number_format($espaciosDisponibles) }}</span>
                    <span class="text-[9px] text-slate-400 font-bold block">Slots Libres</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Buscador Espacial y Localizador Inmediato -->
    <div class="card-3d p-4 border border-slate-200/80 bg-white">
        <form @submit.prevent="ejecutarBusquedaRapida()" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-cyan-600">
                    <i class="fas fa-search"></i>
                </div>
                <input type="text" x-model="searchQuery" placeholder="Buscar por Lote (ej: 604MT01), OP, Producto o # Archivador (1 al 210)..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 text-xs font-bold text-slate-800 uppercase tracking-wide shadow-inner">
            </div>

            <button type="submit" 
                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-[#005889] to-[#06B6D4] shadow-3d-button hover:shadow-3d-cyan transition-all flex items-center justify-center space-x-2">
                <i class="fas fa-crosshairs text-xs"></i>
                <span>Ubicar en Archivo 3D</span>
            </button>
        </form>

        <!-- Resultado de Búsqueda Destacado -->
        <template x-if="searchResult">
            <div class="mt-3 p-3 rounded-2xl bg-cyan-50 border border-cyan-200 text-xs text-cyan-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2 animate-fade-in">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-cyan-500 animate-ping"></span>
                    <span>Lote Encontrado: <strong x-text="searchResult.lote"></strong> (OP: <span x-text="searchResult.op"></span>) — <span class="font-semibold" x-text="searchResult.producto"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="font-mono font-black text-cyan-800 bg-white px-2.5 py-1 rounded-lg border border-cyan-300 shadow-sm" x-text="searchResult.posicion_formateada"></span>
                    <button @click="navegarAUbicacion(searchResult)" 
                            class="px-3 py-1 bg-slate-900 text-white rounded-lg font-black text-[11px] uppercase tracking-wider hover:bg-cyan-800">
                        Ver Archivador →
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Barra de Controles: Vista (Todo vs Balda) | Profundidad (Frente vs Atrás) | Nivel -->
    <div class="card-3d p-4 border border-slate-200/80 bg-white flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <!-- 1. Selector de Modo de Vista (Todo el Rack vs Balda Individual) -->
        <div class="flex items-center space-x-2">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">Modo de Vista:</span>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200">
                <button @click="cambiarVistaModo('TODO')" 
                        :class="vistaModo === 'TODO' ? 'bg-slate-900 text-white font-black shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                        class="px-3.5 py-2 rounded-lg text-xs transition-all uppercase tracking-wider flex items-center space-x-1.5">
                    <i class="fas fa-th-large text-xs"></i>
                    <span>Todo el Rack (5 Niveles)</span>
                </button>
                <button @click="cambiarVistaModo('BALDA')" 
                        :class="vistaModo === 'BALDA' ? 'bg-slate-900 text-white font-black shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                        class="px-3.5 py-2 rounded-lg text-xs transition-all uppercase tracking-wider flex items-center space-x-1.5">
                    <i class="fas fa-layer-group text-xs"></i>
                    <span>Nivel Individual</span>
                </button>
            </div>
        </div>

        <!-- 2. Selector de Profundidad: Frente (Impares) vs Parte de Atrás (Pares) -->
        <div class="flex items-center space-x-2">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">Cara / Profundidad:</span>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200">
                <button @click="cambiarCara('VISIBLE')" 
                        :class="caraActual === 'VISIBLE' ? 'bg-[#005889] text-white font-black shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                        class="px-3.5 py-2 rounded-lg text-xs transition-all uppercase tracking-wider flex items-center space-x-1.5">
                    <i class="fas fa-eye text-xs"></i>
                    <span>Frente (Impares)</span>
                </button>
                <button @click="cambiarCara('POSTERIOR')" 
                        :class="caraActual === 'POSTERIOR' ? 'bg-[#005889] text-white font-black shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                        class="px-3.5 py-2 rounded-lg text-xs transition-all uppercase tracking-wider flex items-center space-x-1.5">
                    <i class="fas fa-undo-alt text-xs"></i>
                    <span>Parte de Atrás (Pares)</span>
                </button>
            </div>
        </div>

        <!-- 3. Selector Rápido de Nivel (de arriba hacia abajo: 1 al 5) -->
        <div class="flex items-center space-x-2">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">Nivel (1 a 5):</span>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200">
                <template x-for="n in [1, 2, 3, 4, 5]" :key="n">
                    <button @click="cambiarNivel(n)" 
                            :class="nivelActual === n ? 'bg-cyan-600 text-white font-black shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                            class="px-2.5 py-2 rounded-lg text-xs transition-all font-mono">
                        <span x-text="'N' + n"></span>
                    </button>
                </template>
            </div>

            <!-- Botón Perspectiva Isométrica -->
            <button @click="isometric = !isometric" 
                    :class="isometric ? 'bg-slate-900 text-cyan-300' : 'bg-slate-100 text-slate-600'"
                    class="p-2 rounded-xl text-xs font-bold border border-slate-200 hover:bg-slate-200 transition-all flex items-center"
                    title="Alternar Inclinación Isométrica 3D">
                <i class="fas fa-cube text-sm"></i>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VISTA 1: VISTA DE TODO EL RACK (LOS 5 NIVELES APILADOS DE ARRIBA HACIA ABAJO) -->
    <!-- ========================================================================= -->
    <template x-if="vistaModo === 'TODO'">
        <div class="p-6 rounded-3xl bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 border border-slate-800 shadow-2xl relative overflow-hidden space-y-5"
             style="perspective: 1600px;">
            
            <!-- Luces Ambientales de Bodega Farmacéutica -->
            <div class="absolute -top-20 left-1/4 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 right-1/4 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Cabecera del Rack Completo -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-3 bg-slate-900/90 rounded-2xl border border-slate-800 backdrop-blur z-10 relative">
                <div class="flex items-center space-x-3 text-white">
                    <div class="w-9 h-9 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-black text-sm font-mono border border-cyan-500/30">
                        <span>R1</span>
                    </div>
                    <div>
                        <span class="font-display font-black text-sm uppercase tracking-wider text-cyan-300">
                            RACK 1 CENTRAL · VISTA GLOBAL COMPLETA (5 NIVELES)
                        </span>
                        <span class="text-[11px] text-slate-400 block font-medium">
                            <span x-text="caraActual === 'VISIBLE' ? 'Cara Visible (Frente · Impares del 1 al 209)' : 'Parte de Atrás (Doble Fondo · Pares del 2 al 210)'"></span>
                            • 21 archivadores por nivel • 105 archivadores por cara
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-3 text-xs font-mono">
                    <span class="px-3 py-1 rounded-xl bg-slate-800 text-slate-300 font-bold border border-slate-700">
                        De Arriba (N1) a Abajo (N5)
                    </span>
                </div>
            </div>

            <!-- Marco Estructural del Mueble Industrial Rack (5 Balda apiladas) -->
            <div class="w-full space-y-4 py-2 transition-all duration-700 ease-out transform"
                 :style="isometric ? 'transform: rotateX(8deg) rotateY(-2deg);' : 'transform: rotateX(0deg) rotateY(0deg);'">
                
                <!-- Bucle de los 5 Niveles (1 al 5 de arriba hacia abajo) -->
                <template x-for="nivelObj in [rackCompleto[1], rackCompleto[2], rackCompleto[3], rackCompleto[4], rackCompleto[5]]" :key="nivelObj.nivel">
                    <div class="rounded-2xl bg-slate-900/90 border border-slate-800 p-2.5 space-y-2 relative transition-all"
                         :class="nivelActual === nivelObj.nivel ? 'ring-2 ring-cyan-500/50 bg-slate-900' : 'hover:border-slate-700'">
                        
                        <!-- Rótulo de la Balda y Acción para enfocar nivel -->
                        <div class="flex items-center justify-between px-2 text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-mono font-black"
                                      :class="nivelActual === nivelObj.nivel ? 'bg-cyan-500 text-slate-950' : 'bg-slate-800 text-cyan-300'"
                                      x-text="'NIVEL 0' + nivelObj.nivel"></span>
                                <span class="text-[11px] font-black uppercase text-slate-300" 
                                      x-text="nivelObj.nivel === 1 ? 'NIVEL SUPERIOR (ARRIBA)' : (nivelObj.nivel === 5 ? 'NIVEL INFERIOR (ABAJO)' : 'NIVEL INTERMEDIO')"></span>
                                <span class="text-[10px] font-mono text-slate-500" x-text="'(' + nivelObj.rango_texto + ')'"></span>
                            </div>

                            <button @click="enfocarNivel(nivelObj.nivel)" 
                                    class="text-[10px] font-bold text-cyan-400 hover:text-cyan-300 hover:underline flex items-center space-x-1">
                                <span>Ver Balda en Detalle</span>
                                <i class="fas fa-search-plus text-[9px]"></i>
                            </button>
                        </div>

                        <!-- 21 Archivadores en la Balda -->
                        <div class="grid grid-cols-7 sm:grid-cols-11 md:grid-cols-21 gap-1">
                            <template x-for="arc in nivelObj.archivadores" :key="arc.numero">
                                <div @click="seleccionarArchivador(arc, nivelObj.nivel)"
                                     :class="{
                                        'ring-2 ring-cyan-400 bg-cyan-900 scale-105 -translate-y-1 shadow-[0_0_12px_#06B6D4] z-20': archivadorSeleccionado && archivadorSeleccionado.numero === arc.numero,
                                        'hover:-translate-y-1 hover:border-cyan-400': !(archivadorSeleccionado && archivadorSeleccionado.numero === arc.numero),
                                        'bg-slate-800 border-slate-700': arc.ocupacion_count === 0,
                                        'bg-slate-800/90 border-cyan-500/50': arc.ocupacion_count > 0 && arc.ocupacion_count < 4,
                                        'bg-emerald-950 border-emerald-500/60': arc.ocupacion_count === 4
                                     }"
                                     class="h-16 sm:h-20 rounded-lg p-1 flex flex-col justify-between cursor-pointer transition-all duration-200 border relative group"
                                     :title="'Archivador #' + arc.numero + ' (' + arc.ocupacion_count + '/4 BR)'">
                                    
                                    <!-- Número del archivador -->
                                    <span class="text-[9px] font-mono font-black text-center truncate block"
                                          :class="archivadorSeleccionado && archivadorSeleccionado.numero === arc.numero ? 'text-cyan-300' : 'text-white'"
                                          x-text="'#' + (arc.numero < 10 ? '0' + arc.numero : arc.numero)"></span>

                                    <!-- Aro central -->
                                    <div class="w-2.5 h-2.5 rounded-full border border-slate-600 bg-slate-950 mx-auto"></div>

                                    <!-- 4 Mini LEDs de Ocupación -->
                                    <div class="grid grid-cols-4 gap-0.5 px-0.5">
                                        <template x-for="s in [1, 2, 3, 4]" :key="s">
                                            <div class="h-1 rounded-sm"
                                                 :class="s <= arc.ocupacion_count ? 'bg-cyan-400 shadow-[0_0_2px_#06B6D4]' : 'bg-slate-700'"></div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- VISTA 2: VISTA DETALLADA DE NIVEL (BALDA INDIVIDUAL DE 21 ARCHIVADORES) -->
    <!-- ========================================================================= -->
    <template x-if="vistaModo === 'BALDA'">
        <div class="p-6 rounded-3xl bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 border border-slate-800 shadow-2xl relative overflow-hidden"
             style="perspective: 1400px;">
            
            <!-- Luces Ambientales -->
            <div class="absolute -top-20 left-1/4 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Rótulo Superior de Balda -->
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-4 py-2 bg-slate-900/90 rounded-2xl border border-slate-800 backdrop-blur z-10 relative">
                <div class="flex items-center space-x-3 text-white">
                    <div class="w-8 h-8 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-black text-xs font-mono">
                        <span x-text="nivelActual"></span>
                    </div>
                    <div>
                        <span class="font-display font-black text-sm uppercase tracking-wider text-cyan-300" 
                              x-text="'RACK 1 · NIVEL 0' + nivelActual + ' (' + (caraActual === 'VISIBLE' ? 'CARA VISIBLE · FRENTE' : 'PARTE DE ATRÁS · PARES') + ')'"></span>
                        <span class="text-[11px] text-slate-400 block font-medium">
                            21 Archivadores físicos numerados de izquierda a derecha (Capacidad: 84 Batch Records)
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-3 text-xs font-mono">
                    <button @click="cambiarVistaModo('TODO')" class="text-xs text-cyan-400 hover:text-cyan-300 font-bold hover:underline">
                        ← Volver a Ver Todo el Rack
                    </button>
                    <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-cyan-300 font-bold border border-slate-700" 
                          x-text="'#' + archivadores[0].numero + ' AL #' + archivadores[archivadores.length - 1].numero"></span>
                </div>
            </div>

            <!-- Balda Volumétrica 3D -->
            <div class="w-full transition-all duration-700 ease-out transform py-4"
                 :style="isometric ? 'transform: rotateX(16deg) rotateY(-4deg) scale(0.98);' : 'transform: rotateX(0deg) rotateY(0deg) scale(1);'">
                
                <div class="h-2.5 w-full bg-gradient-to-r from-slate-700 via-slate-500 to-slate-700 rounded-t-sm shadow-md border-b border-slate-800"></div>

                <div class="p-3 bg-slate-800/80 border-x-4 border-slate-700 shadow-inner relative">
                    <div class="grid grid-cols-7 sm:grid-cols-11 md:grid-cols-21 gap-1.5 sm:gap-2">
                        <template x-for="(arc, idx) in archivadores" :key="arc.numero">
                            <div @click="seleccionarArchivador(arc, nivelActual)"
                                 :class="{
                                    'ring-4 ring-cyan-400 shadow-[0_0_25px_rgba(6,182,212,0.9)] scale-105 -translate-y-2 z-20': archivadorSeleccionado && archivadorSeleccionado.numero === arc.numero,
                                    'hover:-translate-y-2 hover:shadow-lg': !(archivadorSeleccionado && archivadorSeleccionado.numero === arc.numero),
                                    'bg-gradient-to-b from-slate-700 via-slate-800 to-slate-900 border-slate-600': arc.ocupacion_count === 0,
                                    'bg-gradient-to-b from-cyan-900 via-slate-800 to-slate-900 border-cyan-500/50': arc.ocupacion_count > 0 && arc.ocupacion_count < 4,
                                    'bg-gradient-to-b from-emerald-900 via-slate-800 to-slate-900 border-emerald-500/60': arc.ocupacion_count === 4
                                 }"
                                 class="h-40 rounded-xl p-1.5 flex flex-col justify-between cursor-pointer transition-all duration-300 transform border relative overflow-hidden group">
                                
                                <div class="text-center pt-1">
                                    <div class="w-full h-1 bg-cyan-400/40 rounded-full mb-1"></div>
                                    <span class="text-[11px] font-mono font-black tracking-tight"
                                          :class="archivadorSeleccionado && archivadorSeleccionado.numero === arc.numero ? 'text-cyan-300' : 'text-white'"
                                          x-text="'#' + (arc.numero < 10 ? '0' + arc.numero : arc.numero)"></span>
                                </div>

                                <div class="flex flex-col items-center justify-center my-auto space-y-1">
                                    <div class="w-4 h-4 rounded-full border-2 border-slate-500/80 bg-slate-950 flex items-center justify-center group-hover:border-cyan-400 transition-colors">
                                        <div class="w-1.5 h-1.5 rounded-full bg-slate-600"></div>
                                    </div>
                                    <span class="text-[8px] font-mono font-bold uppercase tracking-tighter"
                                          :class="arc.cara === 'VISIBLE' ? 'text-slate-400' : 'text-amber-400'"
                                          x-text="arc.cara === 'VISIBLE' ? 'FRENTE' : 'DETRÁS'"></span>
                                </div>

                                <div class="space-y-1 pb-1">
                                    <div class="grid grid-cols-4 gap-0.5 px-0.5">
                                        <template x-for="s in [1, 2, 3, 4]" :key="s">
                                            <div class="h-1.5 rounded-sm transition-colors"
                                                 :class="s <= arc.ocupacion_count ? 'bg-cyan-400 shadow-[0_0_4px_#06B6D4]' : 'bg-slate-700/60'"></div>
                                        </template>
                                    </div>
                                    <div class="text-[8px] font-bold text-center text-slate-400 font-mono"
                                         x-text="arc.ocupacion_count + '/4 BR'"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="h-5 w-full bg-gradient-to-r from-slate-700 via-slate-500 to-slate-700 rounded-b-sm shadow-xl border-t-2 border-slate-400/30 flex items-center justify-between px-6">
                    <span class="text-[8px] font-mono font-bold text-slate-900 tracking-wider">CAPACIDAD: 84 EXPEDIENTES</span>
                    <span class="text-[8px] font-mono font-bold text-slate-900 tracking-wider" x-text="'RACK 1 · NIVEL 0' + nivelActual"></span>
                    <span class="text-[8px] font-mono font-bold text-slate-900 tracking-wider">DOBLE PROFUNDIDAD</span>
                </div>
            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- PANEL DE INSPECCIÓN DEL ARCHIVADOR SELECCIONADO (LOS 4 BATCH RECORDS) -->
    <!-- ========================================================================= -->
    <template x-if="archivadorSeleccionado">
        <div class="card-3d p-6 border border-slate-200/80 bg-white space-y-6 animate-fade-in">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 text-cyan-300 font-mono text-lg font-black flex items-center justify-center shadow-md">
                        <span x-text="'#' + archivadorSeleccionado.numero"></span>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-display text-lg font-black text-slate-900 tracking-tight"
                                x-text="'Archivador #' + archivadorSeleccionado.numero"></h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                  :class="archivadorSeleccionado.cara === 'VISIBLE' ? 'bg-blue-50 text-blue-800' : 'bg-amber-50 text-amber-800'"
                                  x-text="archivadorSeleccionado.cara === 'VISIBLE' ? 'Cara Visible (Frente · Impar)' : 'Parte de Atrás (Doble Fondo · Par)'"></span>
                        </div>
                        <p class="text-xs text-slate-500">
                            Ubicación: <strong class="text-slate-800" x-text="'RACK 1 · Nivel 0' + (archivadorSeleccionado.nivel || nivelActual)"></strong> 
                            • Posición en fila: <strong class="text-slate-800" x-text="'Casilla ' + archivadorSeleccionado.posicion_en_hilera + ' de 21'"></strong>
                            • Contraparte: <button @click="saltarAContraparte(archivadorSeleccionado.par_contraparte)" class="text-cyan-600 font-black hover:underline" x-text="'Archivador #' + archivadorSeleccionado.par_contraparte"></button>
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-500">Ocupación:</span>
                    <span class="px-3 py-1 rounded-xl text-xs font-mono font-black"
                          :class="detalleSlots.total_ocupados === 4 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-cyan-50 text-cyan-800 border border-cyan-200'"
                          x-text="detalleSlots.total_ocupados + ' de 4 Slots Ocupados'"></span>
                </div>
            </div>

            <!-- Cuadrícula de los 4 Slots Físicos (1 al 4) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <template x-for="slotInfo in detalleSlots.slots" :key="slotInfo.slot">
                    <div class="p-4 rounded-2xl border transition-all flex flex-col justify-between space-y-3"
                         :class="slotInfo.ocupado 
                            ? 'bg-gradient-to-b from-slate-50 to-white border-cyan-300 shadow-3d-card' 
                            : 'bg-slate-50/60 border-slate-200 border-dashed'">
                        
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-black font-mono"
                                  :class="slotInfo.ocupado ? 'text-cyan-800' : 'text-slate-400'"
                                  x-text="'SLOT 0' + slotInfo.slot"></span>
                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase"
                                  :class="slotInfo.ocupado ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-200 text-slate-600'"
                                  x-text="slotInfo.ocupado ? 'OCUPADO' : 'DISPONIBLE'"></span>
                        </div>

                        <template x-if="slotInfo.ocupado">
                            <div class="space-y-2">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Lote Físico:</span>
                                    <span class="font-mono text-sm font-black text-slate-900 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200 inline-block" 
                                          x-text="slotInfo.lote"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">OP / Producto:</span>
                                    <span class="text-xs font-bold text-slate-800 block truncate" x-text="slotInfo.op_number ? 'OP: ' + slotInfo.op_number : '---'"></span>
                                    <span class="text-[11px] text-slate-600 block truncate" x-text="slotInfo.producto"></span>
                                </div>
                                <div class="text-[10px] text-slate-400 pt-1">
                                    Archivado: <span class="font-bold text-slate-600" x-text="slotInfo.fecha_archivo || '---'"></span>
                                </div>

                                <div class="pt-2 border-t border-slate-100 flex items-center space-x-2">
                                    <template x-if="slotInfo.radar_url">
                                        <a :href="slotInfo.radar_url" class="flex-1 text-center py-1 bg-slate-900 text-white rounded-lg text-[10px] font-black uppercase hover:bg-cyan-800">
                                            Radar 360°
                                        </a>
                                    </template>
                                    <a :href="'/genealogia/' + slotInfo.lote" class="flex-1 text-center py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-bold uppercase border border-slate-300">
                                        Genealogía
                                    </a>
                                </div>
                            </div>
                        </template>

                        <template x-if="!slotInfo.ocupado">
                            <div class="text-center py-4 space-y-2 my-auto">
                                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                    <i class="fas fa-plus text-xs"></i>
                                </div>
                                <p class="text-[11px] text-slate-400 font-medium">Compartimiento vacío</p>
                                <button @click="abrirModalAsignar(archivadorSeleccionado, slotInfo.slot)" 
                                        class="px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider text-cyan-700 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200">
                                    + Asignar Lote
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- MODAL: ASIGNAR BATCH RECORD A UN SLOT ESPECÍFICO -->
    <div x-show="modalAsignar" x-cloak style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.away="modalAsignar = false" 
             class="w-full max-w-md card-3d p-6 bg-white border border-slate-200 rounded-3xl shadow-2xl space-y-4">
            
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center font-black">
                    <i class="fas fa-folder-plus"></i>
                </div>
                <div>
                    <h3 class="font-display text-base font-black text-slate-900">Archivar Expediente Físico</h3>
                    <p class="text-xs text-slate-500" x-text="'RACK 1 · Nivel 0' + formAsignar.nivel + ' · Archivador #' + formAsignar.archivador_numero + ' · Slot 0' + formAsignar.slot"></p>
                </div>
            </div>

            <form @submit.prevent="guardarAsignacion()" class="space-y-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                        Número de Lote <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="formAsignar.lote" required placeholder="Ej: 604MT01"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-black uppercase text-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                        Número de OP
                    </label>
                    <input type="text" x-model="formAsignar.op_number" placeholder="Ej: OP-2026-042"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold uppercase text-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                        Nombre de Producto Farmacéutico
                    </label>
                    <input type="text" x-model="formAsignar.producto_nombre" placeholder="Ej: AUROFLOXACINA 10%"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:border-cyan-500 text-xs font-bold uppercase text-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1">
                        Origen del Lote
                    </label>
                    <select x-model="formAsignar.tipo_origen" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold">
                        <option value="PLANTA">PLANTA INTERNA</option>
                        <option value="MAQUILA">MAQUILA EXTERNA</option>
                    </select>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="modalAsignar = false" class="px-4 py-2 text-xs font-bold text-slate-500">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-black uppercase tracking-wider text-white bg-cyan-600 hover:bg-cyan-700 rounded-xl shadow-md">
                        Confirmar Custodia
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function consultasBrApp() {
    return {
        rackActual: 'RACK 1',
        nivelActual: {{ $nivelSeleccionado }},
        caraActual: '{{ $caraSeleccionada }}',
        vistaModo: '{{ $vistaModo }}',
        isometric: true,
        rackCompleto: @json($rackCompleto),
        archivadores: @json($archivadores),
        archivadorSeleccionado: null,
        detalleSlots: { total_ocupados: 0, slots: [] },

        searchQuery: '{{ $search }}',
        searchResult: @json($resultadoBusqueda),

        modalAsignar: false,
        formAsignar: {
            rack: 'RACK 1',
            nivel: 1,
            archivador_numero: 1,
            cara: 'VISIBLE',
            slot: 1,
            lote: '',
            op_number: '',
            producto_nombre: '',
            tipo_origen: 'PLANTA'
        },

        init() {
            // Seleccionar por defecto el primer archivador ocupado o el primero de la lista
            if (this.archivadores.length > 0) {
                const conDatos = this.archivadores.find(a => a.ocupacion_count > 0);
                this.seleccionarArchivador(conDatos || this.archivadores[0], this.nivelActual);
            }
        },

        cambiarVistaModo(modo) {
            this.vistaModo = modo;
            const url = new URL(window.location.href);
            url.searchParams.set('vista', modo);
            window.history.replaceState({}, '', url.toString());
        },

        cambiarNivel(n) {
            this.nivelActual = n;
            if (this.rackCompleto && this.rackCompleto[n]) {
                this.archivadores = this.rackCompleto[n].archivadores;
                const conDatos = this.archivadores.find(a => a.ocupacion_count > 0);
                this.seleccionarArchivador(conDatos || this.archivadores[0], n);
            }
            const url = new URL(window.location.href);
            url.searchParams.set('nivel', n);
            window.history.replaceState({}, '', url.toString());
        },

        enfocarNivel(n) {
            this.nivelActual = n;
            this.vistaModo = 'BALDA';
            if (this.rackCompleto && this.rackCompleto[n]) {
                this.archivadores = this.rackCompleto[n].archivadores;
                const conDatos = this.archivadores.find(a => a.ocupacion_count > 0);
                this.seleccionarArchivador(conDatos || this.archivadores[0], n);
            }
            const url = new URL(window.location.href);
            url.searchParams.set('nivel', n);
            url.searchParams.set('vista', 'BALDA');
            window.history.replaceState({}, '', url.toString());
        },

        cambiarCara(c) {
            window.location.href = `/consultas-br?nivel=${this.nivelActual}&cara=${c}&vista=${this.vistaModo}`;
        },

        seleccionarArchivador(arc, nivelContexto) {
            this.archivadorSeleccionado = { ...arc, nivel: nivelContexto || this.nivelActual };
            fetch(`/api/consultas-br/archivador/${arc.numero}`)
                .then(r => r.json())
                .then(data => {
                    this.detalleSlots = data;
                })
                .catch(err => console.error('Error cargando slots:', err));
        },

        saltarAContraparte(numeroContraparte) {
            const nuevaCara = (numeroContraparte % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
            window.location.href = `/consultas-br?nivel=${this.nivelActual}&cara=${nuevaCara}&vista=${this.vistaModo}`;
        },

        ejecutarBusquedaRapida() {
            const q = this.searchQuery ? this.searchQuery.trim() : '';
            if (!q) return;

            fetch(`/api/consultas-br/search?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.found) {
                        this.searchResult = data;
                        this.navegarAUbicacion(data);
                    } else {
                        if (window.Swal) {
                            Swal.fire('No Encontrado', data.message || 'No se encontró el lote en el archivo físico.', 'info');
                        } else {
                            alert(data.message || 'No encontrado');
                        }
                    }
                });
        },

        navegarAUbicacion(loc) {
            if (this.nivelActual !== loc.nivel || this.caraActual !== loc.cara) {
                window.location.href = `/consultas-br?nivel=${loc.nivel}&cara=${loc.cara}&vista=${this.vistaModo}&buscar=${encodeURIComponent(loc.lote)}`;
            } else {
                // Ya estamos en la cara y nivel adecuados
                const targetArc = this.archivadores.find(a => a.numero === loc.archivador_numero);
                if (targetArc) {
                    this.seleccionarArchivador(targetArc, loc.nivel);
                }
            }
        },

        abrirModalAsignar(archivador, slot) {
            this.formAsignar = {
                rack: 'RACK 1',
                nivel: archivador.nivel || this.nivelActual,
                archivador_numero: archivador.numero,
                cara: archivador.cara,
                slot: slot,
                lote: '',
                op_number: '',
                producto_nombre: '',
                tipo_origen: 'PLANTA'
            };
            this.modalAsignar = true;
        },

        guardarAsignacion() {
            fetch('/api/consultas-br/assign-slot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.formAsignar)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.modalAsignar = false;
                    if (window.Swal) {
                        Swal.fire('Archivado', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        alert(data.message);
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Error al guardar: ' + err));
        }
    };
}
</script>
@endsection
