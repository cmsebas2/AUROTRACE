<!-- MAQUETA 3D REGLAMENTARIA: ARCHIVO FÍSICO RACK 1 (5 NIVELES · 210 ARCHIVADORES · 4 SLOTS POR ARCHIVADOR) -->
<div x-data="archivo3dModule(@js($targetPosition ?? ''))" class="space-y-4">
    
    <!-- Barra Superior de Controles de Espacio 3D -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-900 text-white shadow-xl border border-slate-800">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-black shadow-md">
                <i class="fas fa-cube text-lg"></i>
            </div>
            <div>
                <h3 class="font-display text-sm font-black uppercase tracking-wider text-cyan-300">Maqueta 3D · Archivo Físico Central (RACK 1)</h3>
                <p class="text-[11px] text-slate-400">Localizador espacial sincronizado (RACK 1 · 5 Niveles · 210 Archivadores · 4 Slots/Archivador)</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Selector de Cara / Profundidad -->
            <div class="inline-flex p-1 bg-slate-800 rounded-xl border border-slate-700">
                <button type="button" @click="caraActual = 'VISIBLE'" 
                        :class="caraActual === 'VISIBLE' ? 'bg-[#005889] text-white font-black shadow-sm' : 'text-slate-400 hover:text-white font-bold'"
                        class="px-2.5 py-1.5 rounded-lg text-xs transition-all uppercase tracking-wider">
                    Frente (Impares)
                </button>
                <button type="button" @click="caraActual = 'POSTERIOR'" 
                        :class="caraActual === 'POSTERIOR' ? 'bg-[#005889] text-white font-black shadow-sm' : 'text-slate-400 hover:text-white font-bold'"
                        class="px-2.5 py-1.5 rounded-lg text-xs transition-all uppercase tracking-wider">
                    Atrás (Pares)
                </button>
            </div>

            <!-- Selector de Nivel (1 al 5) -->
            <div class="inline-flex p-1 bg-slate-800 rounded-xl border border-slate-700">
                <template x-for="n in [1, 2, 3, 4, 5]" :key="n">
                    <button type="button" @click="nivelActual = n" 
                            :class="nivelActual === n ? 'bg-cyan-500 text-slate-950 font-black shadow-sm' : 'text-slate-400 hover:text-white font-bold'"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-mono transition-all">
                        <span x-text="'N' + n"></span>
                    </button>
                </template>
            </div>

            <!-- Perspectiva Isométrica 3D -->
            <button type="button" @click="isometric = !isometric" 
                    class="p-2.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-slate-700 transition-all"
                    title="Alternar Inclinación 3D">
                <i class="fas fa-cubes"></i>
            </button>
        </div>
    </div>

    <!-- Escenario 3D de la Balda / Rack -->
    <div class="p-5 rounded-3xl bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 border border-slate-800 shadow-2xl relative overflow-hidden space-y-4"
         style="perspective: 1400px;">
        
        <!-- Luz Ambiental -->
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Indicador de Posición Seleccionada en Vivo -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-slate-900/90 backdrop-blur px-4 py-2.5 rounded-2xl border border-slate-800 text-xs gap-2">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-ping"></span>
                <span class="text-slate-400 font-bold uppercase tracking-wider text-[11px]">Ubicación Asignada:</span>
                <strong class="text-cyan-300 font-mono font-black text-xs" x-text="posicionFormateada || 'Haga clic en un Archivador y Slot'"></strong>
            </div>

            <template x-if="archivadorSeleccionado">
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-mono font-black border border-emerald-500/40">
                        Archivador #<span x-text="archivadorSeleccionado"></span> · Slot <span x-text="slotSeleccionado"></span>
                    </span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase" x-text="caraActual === 'VISIBLE' ? '(Cara Visible · Frente)' : '(Doble Fondo · Atrás)'"></span>
                </div>
            </template>
        </div>

        <!-- Balda Industrial 3D -->
        <div class="w-full transition-all duration-700 ease-out transform py-2"
             :style="isometric ? 'transform: rotateX(14deg) rotateY(-3deg) scale(0.99);' : 'transform: rotateX(0deg) rotateY(0deg) scale(1);'">
            
            <div class="h-2.5 w-full bg-gradient-to-r from-slate-700 via-slate-500 to-slate-700 rounded-t-sm shadow-md border-b border-slate-800"></div>

            <div class="p-3 bg-slate-800/90 border-x-4 border-slate-700 shadow-inner">
                <div class="grid grid-cols-7 sm:grid-cols-11 md:grid-cols-21 gap-1.5">
                    <template x-for="arcNum in getArchivadoresNivel()" :key="arcNum">
                        <div @click="seleccionarArchivador(arcNum, 1)"
                             :class="{
                                'ring-2 ring-cyan-400 bg-cyan-950/90 border-cyan-400 scale-105 -translate-y-1 shadow-[0_0_15px_#06B6D4] z-20': archivadorSeleccionado === arcNum,
                                'bg-slate-900 border-slate-700 hover:border-cyan-500/60 hover:-translate-y-0.5': archivadorSeleccionado !== arcNum
                             }"
                             class="h-32 rounded-xl p-1.5 flex flex-col justify-between cursor-pointer transition-all duration-200 border relative overflow-hidden group">
                            
                            <!-- Número de Archivador -->
                            <div class="text-center pt-0.5">
                                <span class="text-[10px] font-mono font-black tracking-tight block"
                                      :class="archivadorSeleccionado === arcNum ? 'text-cyan-300' : 'text-white'"
                                      x-text="'#' + (arcNum < 10 ? '0' + arcNum : arcNum)"></span>
                            </div>

                            <!-- Aro metálico -->
                            <div class="w-3 h-3 rounded-full border border-slate-600 bg-slate-950 mx-auto my-0.5 flex items-center justify-center">
                                <div class="w-1 h-1 rounded-full bg-slate-500"></div>
                            </div>

                            <!-- 4 Slots para seleccionar -->
                            <div class="space-y-1">
                                <div class="grid grid-cols-2 gap-1 px-0.5">
                                    <template x-for="s in [1, 2, 3, 4]" :key="s">
                                        <button type="button" 
                                                @click.stop="seleccionarArchivador(arcNum, s)"
                                                :class="{
                                                    'bg-cyan-400 text-slate-950 font-black shadow-[0_0_6px_#06B6D4]': archivadorSeleccionado === arcNum && slotSeleccionado === s,
                                                    'bg-slate-800 text-slate-400 hover:bg-cyan-800 hover:text-white font-bold': !(archivadorSeleccionado === arcNum && slotSeleccionado === s)
                                                }"
                                                class="h-4 rounded font-mono text-[8px] flex items-center justify-center transition-all">
                                            <span x-text="'S' + s"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="h-4 w-full bg-gradient-to-r from-slate-700 via-slate-500 to-slate-700 rounded-b-sm shadow-xl border-t border-slate-400/30 flex items-center justify-between px-4">
                <span class="text-[8px] font-mono font-bold text-slate-900 uppercase" x-text="'RACK 1 · NIVEL 0' + nivelActual"></span>
                <span class="text-[8px] font-mono font-bold text-slate-900 uppercase" x-text="caraActual === 'VISIBLE' ? 'CARA VISIBLE (FRENTE · IMPARES)' : 'PARTE DE ATRÁS (DOBLE FONDO · PARES)'"></span>
                <span class="text-[8px] font-mono font-bold text-slate-900 uppercase">21 ARCHIVADORES EN FILA</span>
            </div>
        </div>
    </div>
</div>

<script>
function archivo3dModule(initialPosition) {
    return {
        nivelActual: 1,
        caraActual: 'VISIBLE',
        isometric: true,
        archivadorSeleccionado: null,
        slotSeleccionado: 1,
        posicionFormateada: initialPosition || '',

        init() {
            if (this.posicionFormateada) {
                this.parsePosition(this.posicionFormateada);
            }
        },

        parsePosition(posStr) {
            if (!posStr) return;
            const str = posStr.toUpperCase();

            // Extract Nivel
            const matchNivel = str.match(/NIVEL\s*0?([1-5])/);
            if (matchNivel) {
                this.nivelActual = parseInt(matchNivel[1]);
            }

            // Extract Archivador #
            const matchArch = str.match(/ARCHIVADOR\s*#?\s*(\d+)/);
            if (matchArch) {
                const num = parseInt(matchArch[1]);
                if (num >= 1 && num <= 210) {
                    this.archivadorSeleccionado = num;
                    this.nivelActual = Math.ceil(num / 42);
                    this.caraActual = (num % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
                }
            }

            // Extract Slot
            const matchSlot = str.match(/SLOT\s*([1-4])/);
            if (matchSlot) {
                this.slotSeleccionado = parseInt(matchSlot[1]);
            }
        },

        getArchivadoresNivel() {
            const list = [];
            const base = (this.nivelActual - 1) * 42;
            const isVisible = this.caraActual === 'VISIBLE';

            for (let i = 0; i < 21; i++) {
                const num = base + (i * 2 + (isVisible ? 1 : 2));
                list.push(num);
            }
            return list;
        },

        seleccionarArchivador(numArch, slot) {
            this.archivadorSeleccionado = numArch;
            this.slotSeleccionado = slot || 1;
            
            const formatted = `RACK 1 · NIVEL 0${this.nivelActual} · ARCHIVADOR #${numArch} · SLOT ${this.slotSeleccionado}`;
            this.posicionFormateada = formatted;

            // Auto-fill form inputs named posicion_archivo_fisico or id posicion_archivo_fisico
            const inputTargets = document.querySelectorAll('input[name="posicion_archivo_fisico"], #posicion_archivo_fisico');
            inputTargets.forEach(input => {
                input.value = formatted;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            // Also check for archivador_numero input if present
            const inputArch = document.querySelectorAll('input[name="archivador_numero"], #archivador_numero');
            inputArch.forEach(input => {
                input.value = numArch;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Ubicación seleccionada: ' + formatted,
                    showConfirmButton: false,
                    timer: 1800
                });
            }
        }
    };
}
</script>
