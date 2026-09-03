<!-- MAQUETA 3D: ARCHIVO FÍSICO DE BATCH RECORDS Y EXPEDIENTES FARMACÉUTICOS -->
<div x-data="archivo3dModule()" class="space-y-6">
    
    <!-- Barra de Control y Selector 3D -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-900 text-white shadow-3d-card border border-slate-800">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-aurofarma flex items-center justify-center text-white shadow-3d-cyan">
                <i class="fas fa-cubes text-lg"></i>
            </div>
            <div>
                <h3 class="font-display text-sm font-black uppercase tracking-wider text-cyan-300">Maqueta 3D · Archivo Físico de Batch Records</h3>
                <p class="text-[11px] text-slate-400">Localizador espacial para custodia y auditoría de expedientes físicos de manufactura</p>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Selector de Estante Activo -->
            <div class="inline-flex p-1 bg-slate-800 rounded-xl border border-slate-700">
                <template x-for="e in ['ESTANTE A', 'ESTANTE B', 'ESTANTE C', 'ESTANTE D']" :key="e">
                    <button @click="estanteActivo = e" 
                            :class="estanteActivo === e ? 'bg-cyan-500 text-slate-950 font-black shadow-md' : 'text-slate-400 hover:text-white font-bold'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-all uppercase tracking-wider">
                        <span x-text="e"></span>
                    </button>
                </template>
            </div>

            <!-- Botón Perspectiva 3D -->
            <button @click="isometric = !isometric" 
                    class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-cyan-400 border border-slate-700 transition-all flex items-center space-x-1.5"
                    title="Alternar Perspectiva Isométrica">
                <i class="fas fa-cube"></i>
                <span x-text="isometric ? 'Vista 3D' : 'Vista Frontal'"></span>
            </button>
        </div>
    </div>

    <!-- Escenario 3D del Archivador -->
    <div class="p-6 rounded-3xl bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 border border-slate-800 shadow-2xl relative overflow-hidden min-h-[460px] flex items-center justify-center"
         style="perspective: 1200px;">
        
        <!-- Efecto Iluminación Ambiental de Bodega -->
        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 left-1/4 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Indicador de Posición Resaltada -->
        <div class="absolute top-4 left-6 z-20 flex items-center space-x-2 bg-slate-900/80 backdrop-blur px-3 py-1.5 rounded-xl border border-slate-700/80 text-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="text-slate-400 font-semibold">Ubicación Seleccionada:</span>
            <strong class="text-cyan-300 font-mono font-black" x-text="posicionVisual || 'Haga clic en una caja o estante'"></strong>
        </div>

        <!-- Mueble 3D de Archivo Físico -->
        <div class="w-full max-w-4xl transition-all duration-700 ease-out transform"
             :style="isometric ? 'transform: rotateX(15deg) rotateY(-8deg) scale(0.95);' : 'transform: rotateX(0deg) rotateY(0deg) scale(1);'">
            
            <!-- Marco Estructural del Estante (Industrial Steel) -->
            <div class="p-4 rounded-3xl bg-slate-800/90 border-4 border-slate-700 shadow-[0_20px_50px_rgba(0,0,0,0.8),inset_0_2px_4px_rgba(255,255,255,0.1)] relative">
                
                <!-- Rótulo de Estante Superior -->
                <div class="mb-4 flex items-center justify-between px-4 py-2 bg-slate-900 rounded-xl border border-slate-700">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-barcode text-cyan-400 text-sm"></i>
                        <span class="font-display font-black text-xs uppercase tracking-widest text-slate-200" x-text="estanteActivo + ' · ARCHIVO CENTRAL'"></span>
                    </div>
                    <span class="text-[10px] font-mono text-slate-400 font-bold">ZONA BPM 04 - CLIMATIZADA</span>
                </div>

                <!-- 5 Niveles de Balda -->
                <div class="space-y-3.5">
                    <template x-for="nivel in [5, 4, 3, 2, 1]" :key="nivel">
                        <div class="p-2.5 rounded-2xl bg-slate-900/90 border-2 border-slate-700/80 shadow-inner flex flex-col relative group">
                            
                            <!-- Etiqueta lateral del Nivel -->
                            <div class="flex items-center justify-between mb-1.5 px-2">
                                <span class="text-[10px] font-black text-slate-400 tracking-wider" x-text="'NIVEL 0' + nivel"></span>
                                <span class="text-[9px] text-slate-500 font-mono">Capacidad: 6 Cajas Archivo</span>
                            </div>

                            <!-- Cajas de Archivo del Nivel (6 cajas por balda) -->
                            <div class="grid grid-cols-6 gap-2">
                                <template x-for="caja in [1, 2, 3, 4, 5, 6]" :key="caja">
                                    <div @click="seleccionarPosicion(estanteActivo, nivel, caja)"
                                         :class="esPosicionActiva(estanteActivo, nivel, caja) 
                                            ? 'bg-gradient-to-b from-cyan-400 to-cyan-600 text-slate-950 ring-4 ring-cyan-400/50 shadow-[0_0_25px_rgba(6,182,212,0.8)] scale-105' 
                                            : 'bg-slate-800/80 hover:bg-slate-700/80 text-slate-300 border border-slate-600/50 hover:border-cyan-400'"
                                         class="h-14 rounded-xl p-1.5 flex flex-col justify-between cursor-pointer transition-all transform hover:-translate-y-1 active:scale-95 relative overflow-hidden">
                                        
                                        <!-- Solapa / Manija de Caja 3D -->
                                        <div class="w-full flex justify-between items-center">
                                            <div class="w-2.5 h-1 rounded-full bg-slate-500/50"></div>
                                            <span class="text-[9px] font-mono font-black" x-text="'C-' + (caja < 10 ? '0' + caja : caja)"></span>
                                        </div>

                                        <!-- Contenido / Indicador de Lote -->
                                        <div class="text-center">
                                            <i class="fas fa-folder-open text-xs opacity-75"></i>
                                            <div class="text-[8px] font-bold uppercase truncate" 
                                                 x-text="esPosicionActiva(estanteActivo, nivel, caja) ? '★ AQUI' : 'VACÍO'"></div>
                                        </div>

                                        <!-- Sombra inferior de caja -->
                                        <div class="w-full h-1 bg-black/20 rounded-full"></div>
                                    </div>
                                </template>
                            </div>

                            <!-- Balda Metálica Reforzada (Efecto 3D Estante) -->
                            <div class="h-2 w-full mt-2 bg-gradient-to-r from-slate-600 via-slate-500 to-slate-600 rounded-sm shadow-md border-t border-slate-400/40"></div>
                        </div>
                    </template>
                </div>

                <!-- Patas del Mueble 3D -->
                <div class="flex justify-between px-6 -mb-4 pt-2">
                    <div class="w-6 h-4 bg-slate-900 border-2 border-slate-700 rounded-b-md shadow-lg"></div>
                    <div class="w-6 h-4 bg-slate-900 border-2 border-slate-700 rounded-b-md shadow-lg"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function archivo3dModule() {
    return {
        estanteActivo: 'ESTANTE A',
        isometric: true,
        posicionVisual: '{{ $targetPosition ?? "" }}',

        init() {
            // Si viene una posición preconfigurada, auto-seleccionar el estante
            if (this.posicionVisual) {
                const pos = this.posicionVisual.toUpperCase();
                if (pos.includes('ESTANTE B') || pos.includes('B -')) this.estanteActivo = 'ESTANTE B';
                else if (pos.includes('ESTANTE C') || pos.includes('C -')) this.estanteActivo = 'ESTANTE C';
                else if (pos.includes('ESTANTE D') || pos.includes('D -')) this.estanteActivo = 'ESTANTE D';
                else this.estanteActivo = 'ESTANTE A';
            }
        },

        esPosicionActiva(estante, nivel, caja) {
            if (!this.posicionVisual) return false;
            const target = this.posicionVisual.toUpperCase();
            
            // Match flexible
            const matchEstante = target.includes(estante) || target.includes(estante.replace('ESTANTE ', ''));
            const matchNivel = target.includes('NIVEL ' + nivel) || target.includes('NIVEL 0' + nivel) || target.includes('FILA ' + nivel);
            const matchCaja = target.includes('CAJA ' + caja) || target.includes('CAJA 0' + caja) || target.includes('C-' + caja) || target.includes('C-0' + caja);

            return matchEstante && matchNivel && matchCaja;
        },

        seleccionarPosicion(estante, nivel, caja) {
            const formatted = `${estante} · NIVEL 0${nivel} · CAJA 0${caja}`;
            this.posicionVisual = formatted;

            // Si hay un input en el formulario con id 'posicion_archivo_fisico', autollenarlo
            const inputTarget = document.getElementById('posicion_archivo_fisico');
            if (inputTarget) {
                inputTarget.value = formatted;
                inputTarget.dispatchEvent(new Event('input'));
            }

            // Notificación visual rápida
            if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Posición asignada: ' + formatted,
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        }
    };
}
</script>
