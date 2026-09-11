@php
    $preloadedLocations = \Illuminate\Support\Facades\DB::table('batch_record_archive_locations')
        ->select('archivador_numero', 'slot', 'lote', 'op_number', 'maquila_production_order_id')
        ->get();
    $preloadedMap = [];
    foreach ($preloadedLocations as $loc) {
        $key = "{$loc->archivador_numero}_{$loc->slot}";
        $preloadedMap[$key] = [
            'lote' => $loc->lote,
            'op' => $loc->op_number,
            'order_id' => $loc->maquila_production_order_id,
            'num_arch' => (int)$loc->archivador_numero,
            'slot' => (int)$loc->slot,
        ];
    }
@endphp

<div x-data="archivo3dModule(@js($targetPosition ?? ''), @js($order->lote ?? ''), @js($order->id ?? null), @js($preloadedMap), @js(auth()->check() && auth()->user()->isQualityUser()))" class="space-y-4">
    
    <!-- Barra Superior de Controles de Archivo Físico -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-900 text-white shadow-xl border border-slate-800">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-black shadow-md">
                <i class="fas fa-archive text-lg"></i>
            </div>
            <div>
                <h3 class="font-display text-sm font-black uppercase tracking-wider text-cyan-300">Archivo Físico Central (R 1)</h3>
                <p class="text-[11px] text-slate-400">
                    <template x-if="isQualityUser">
                        <span class="text-amber-400 font-bold">● MODO LECTURA Y CONSULTA (ROL DE CALIDAD)</span>
                    </template>
                    <template x-if="!isQualityUser">
                        <span>Slots ocupados bloqueados en rojo · Seleccione un slot libre o su ubicación actual</span>
                    </template>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Leyenda de Estados de Slots -->
            <div class="flex items-center space-x-2 text-[10px] font-bold mr-2">
                <span class="inline-flex items-center text-emerald-400"><span class="w-2 h-2 rounded-full bg-emerald-400 mr-1"></span> Actual</span>
                <span class="inline-flex items-center text-red-400"><span class="w-2 h-2 rounded-full bg-red-500 mr-1"></span> Ocupado</span>
                <span class="inline-flex items-center text-slate-300"><span class="w-2 h-2 rounded-full bg-slate-500 mr-1"></span> Libre</span>
            </div>

            <!-- Selector de Cara / Profundidad -->
            <div class="inline-flex p-1 bg-slate-800 rounded-xl border border-slate-700">
                <button type="button" @click="cambiarCara('VISIBLE')" 
                        :class="caraActual === 'VISIBLE' ? 'bg-[#005889] text-white font-black shadow-sm' : 'text-slate-400 hover:text-white font-bold'"
                        class="px-2.5 py-1.5 rounded-lg text-xs transition-all uppercase tracking-wider">
                    Frente (Impares)
                </button>
                <button type="button" @click="cambiarCara('POSTERIOR')" 
                        :class="caraActual === 'POSTERIOR' ? 'bg-[#005889] text-white font-black shadow-sm' : 'text-slate-400 hover:text-white font-bold'"
                        class="px-2.5 py-1.5 rounded-lg text-xs transition-all uppercase tracking-wider">
                    Atrás (Pares)
                </button>
            </div>

            <!-- Selector de Nivel (1 al 5) -->
            <div class="inline-flex p-1 bg-slate-800 rounded-xl border border-slate-700">
                <template x-for="n in [1, 2, 3, 4, 5]" :key="n">
                    <button type="button" @click="cambiarNivel(n)" 
                            :class="nivelActual === n ? 'bg-cyan-500 text-slate-950 font-black shadow-sm' : 'text-slate-400 hover:text-white font-bold'"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-mono transition-all">
                        <span x-text="'N' + n"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Escenario de la Balda / Rack -->
    <div class="p-5 rounded-3xl bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 border border-slate-800 shadow-2xl relative overflow-hidden space-y-4">
        
        <!-- Luz Ambiental -->
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Indicador de Posición Seleccionada en Vivo -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-slate-900/90 backdrop-blur px-4 py-2.5 rounded-2xl border border-slate-800 text-xs gap-2">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-ping"></span>
                <span class="text-slate-400 font-bold uppercase tracking-wider text-[11px]">Ubicación Asignada:</span>
                <strong class="text-cyan-300 font-mono font-black text-xs" x-text="posicionFormateada || (isQualityUser ? 'Sin ubicación asignada' : 'Haga clic en un Slot Libre o Disponible')"></strong>
            </div>

            <template x-if="archivadorSeleccionado">
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-mono font-black border border-emerald-500/40">
                        A <span x-text="archivadorSeleccionado"></span> · S <span x-text="slotSeleccionado"></span>
                    </span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase" x-text="caraActual === 'VISIBLE' ? '(Cara Visible · Frente)' : '(Doble Fondo · Atrás)'"></span>
                </div>
            </template>
        </div>

        <!-- Balda Industrial de Archivadores -->
        <div class="w-full transition-all duration-300 py-2">
            
            <div class="h-2.5 w-full bg-gradient-to-r from-slate-700 via-slate-500 to-slate-700 rounded-t-sm shadow-md border-b border-slate-800"></div>

            <div class="p-3 bg-slate-800/90 border-x-4 border-slate-700 shadow-inner">
                <div class="grid grid-cols-7 sm:grid-cols-11 md:grid-cols-21 gap-1.5">
                    <template x-for="arcNum in getArchivadoresNivel()" :key="arcNum">
                        <div :class="{
                                'ring-2 ring-cyan-400 bg-cyan-950/90 border-cyan-400 scale-105 -translate-y-1 shadow-[0_0_15px_#06B6D4] z-20': archivadorSeleccionado === arcNum,
                                'bg-slate-900 border-slate-700 hover:border-cyan-500/60': archivadorSeleccionado !== arcNum
                             }"
                             class="h-32 rounded-xl p-1.5 flex flex-col justify-between transition-all duration-200 border relative overflow-hidden group">
                            
                            <!-- Número de Archivador -->
                            <div class="text-center pt-0.5">
                                <span class="text-[10px] font-mono font-black tracking-tight block"
                                      :class="archivadorSeleccionado === arcNum ? 'text-cyan-300' : 'text-white'"
                                      x-text="'A ' + (arcNum < 10 ? '0' + arcNum : arcNum)"></span>
                            </div>

                            <!-- Aro metálico -->
                            <div class="w-3 h-3 rounded-full border border-slate-600 bg-slate-950 mx-auto my-0.5 flex items-center justify-center">
                                <div class="w-1 h-1 rounded-full bg-slate-500"></div>
                            </div>

                            <!-- 4 Slots interactivos (Bloqueados si están ocupados por otro lote o si es usuario de Calidad) -->
                            <div class="space-y-1">
                                <div class="grid grid-cols-2 gap-1 px-0.5">
                                    <template x-for="s in [1, 2, 3, 4]" :key="s">
                                        <button type="button" 
                                                @click.stop="handleSlotClick(arcNum, s)"
                                                :disabled="isQualityUser || isSlotOccupiedByOther(arcNum, s)"
                                                :title="getSlotTitle(arcNum, s)"
                                                :class="getSlotClass(arcNum, s)"
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
                <span class="text-[8px] font-mono font-bold text-slate-900 uppercase" x-text="'R 1 · N ' + nivelActual"></span>
                <span class="text-[8px] font-mono font-bold text-slate-900 uppercase" x-text="caraActual === 'VISIBLE' ? 'CARA VISIBLE (FRENTE · IMPARES)' : 'PARTE DE ATRÁS (DOBLE FONDO · PARES)'"></span>
                <span class="text-[8px] font-mono font-bold text-slate-900 uppercase">21 ARCHIVADORES EN FILA</span>
            </div>
        </div>

        <!-- Botón para Guardar la Nueva Ubicación Seleccionada -->
        <div x-show="!isQualityUser && posicionFormateada && posicionFormateada !== initialPosition" x-transition 
             class="p-4 rounded-2xl bg-gradient-to-r from-cyan-950 via-slate-900 to-cyan-950 border-2 border-cyan-500/60 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-3 animate-fade-in z-30 relative">
            <div>
                <span class="text-xs font-bold text-cyan-300 flex items-center space-x-1.5">
                    <i class="fas fa-exclamation-circle text-cyan-400"></i>
                    <span>¡Nueva localización seleccionada!</span>
                </span>
                <span class="text-sm font-mono font-black text-white block mt-0.5" x-text="posicionFormateada"></span>
            </div>

            <button type="button" 
                    @click="guardarNuevaUbicacionAjax()"
                    :disabled="saving"
                    class="w-full sm:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-500 text-slate-950 font-black text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(16,185,129,0.5)] hover:scale-105 active:scale-95 transition-all flex items-center justify-center space-x-2">
                <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                <span x-text="saving ? 'GUARDANDO CAMBIOS...' : 'GUARDAR NUEVA UBICACIÓN'"></span>
            </button>
        </div>
    </div>
</div>

<script>
function archivo3dModule(initialPosition, currentLote, currentOrderId, preloadedMap, isQualityUser = false) {
    return {
        initialPosition: initialPosition || '',
        isQualityUser: isQualityUser || false,
        nivelActual: 1,
        caraActual: 'VISIBLE',
        isometric: true,
        archivadorSeleccionado: null,
        slotSeleccionado: 1,
        posicionFormateada: initialPosition || '',
        currentLote: currentLote ? currentLote.toUpperCase().trim() : '',
        currentOrderId: currentOrderId || null,
        occupiedMap: preloadedMap || {},
        saving: false,

        init() {
            this.fetchOccupiedSlots();
            if (this.posicionFormateada) {
                this.parsePosition(this.posicionFormateada);
            }
        },

        fetchOccupiedSlots() {
            fetch('/archive-locations/occupied', {
                headers: { 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success && data.occupied) {
                        this.occupiedMap = data.occupied;
                    }
                })
                .catch(err => console.error('Error refrescando slots ocupados:', err));
        },

        parsePosition(posStr) {
            if (!posStr) return;
            const str = posStr.toUpperCase();

            // Extract Nivel
            const matchNivel = str.match(/(?:NIVEL|N)\s*#?\s*0?([1-5])/);
            if (matchNivel) {
                this.nivelActual = parseInt(matchNivel[1]);
            }

            // Extract Archivador #
            const matchArch = str.match(/(?:ARCHIVADOR|A)\s*#?\s*(\d+)/);
            if (matchArch) {
                const num = parseInt(matchArch[1]);
                if (num >= 1 && num <= 210) {
                    this.archivadorSeleccionado = num;
                    this.nivelActual = Math.ceil(num / 42);
                    this.caraActual = (num % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
                }
            }

            // Extract Slot
            const matchSlot = str.match(/(?:SLOT|S)\s*#?\s*([1-4])/);
            if (matchSlot) {
                this.slotSeleccionado = parseInt(matchSlot[1]);
            }
        },

        cambiarNivel(n) {
            this.nivelActual = n;
            if (!this.isQualityUser) {
                this.autoSeleccionarEnNivel(n);
            }
        },

        cambiarCara(c) {
            this.caraActual = c;
            if (!this.isQualityUser) {
                this.autoSeleccionarEnNivel(this.nivelActual);
            }
        },

        autoSeleccionarEnNivel(n) {
            if (this.isQualityUser) return;
            const list = this.getArchivadoresNivel();
            if (!list || list.length === 0) return;

            for (const numArch of list) {
                for (let s = 1; s <= 4; s++) {
                    if (!this.isSlotOccupiedByOther(numArch, s)) {
                        this.seleccionarArchivador(numArch, s);
                        return;
                    }
                }
            }
            this.seleccionarArchivador(list[0], 1);
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

        getSlotInfo(numArch, slot) {
            const key = `${numArch}_${slot}`;
            return this.occupiedMap[key] || null;
        },

        isSlotOccupiedByOther(numArch, slot) {
            const occ = this.getSlotInfo(numArch, slot);
            if (!occ) return false;

            if (this.currentLote && occ.lote && occ.lote.toUpperCase().trim() === this.currentLote) {
                return false;
            }
            if (this.currentOrderId && occ.order_id == this.currentOrderId) {
                return false;
            }
            return true;
        },

        getSlotClass(numArch, slot) {
            // Is it selected right now in component?
            if (this.archivadorSeleccionado === numArch && this.slotSeleccionado === slot) {
                return 'bg-cyan-400 text-slate-950 font-black shadow-[0_0_10px_#06B6D4] ring-2 ring-cyan-300 scale-110 z-10';
            }

            // Is it occupied by another lot?
            if (this.isSlotOccupiedByOther(numArch, slot)) {
                return 'bg-red-950/90 text-red-400 border border-red-600/60 opacity-70 cursor-not-allowed';
            }

            // Is it occupied by THIS lot?
            const occ = this.getSlotInfo(numArch, slot);
            if (occ && ((this.currentLote && occ.lote === this.currentLote) || (this.currentOrderId && occ.order_id == this.currentOrderId))) {
                return 'bg-emerald-500 text-slate-950 font-black shadow-[0_0_8px_#10B981] ring-1 ring-emerald-300';
            }

            if (this.isQualityUser) {
                return 'bg-slate-800 text-slate-500 opacity-60 cursor-not-allowed';
            }

            // Otherwise, free slot
            return 'bg-slate-800 text-slate-400 hover:bg-cyan-700 hover:text-white font-bold cursor-pointer';
        },

        getSlotTitle(numArch, slot) {
            if (this.isQualityUser) {
                const occ = this.getSlotInfo(numArch, slot);
                if (occ) {
                    return `UBICACIÓN: Lote ${occ.lote} (OP ${occ.op}) - Solo lectura`;
                }
                return `Modo Lectura (Calidad): Slot S${slot} (Archivador A${numArch})`;
            }
            const occ = this.getSlotInfo(numArch, slot);
            if (this.isSlotOccupiedByOther(numArch, slot)) {
                return `OCUPADO BLOQUEADO: Lote ${occ.lote} (OP ${occ.op})`;
            }
            if (occ) {
                return `ASIGNADO A ESTE EXPEDIENTE: Slot ${slot}`;
            }
            return `DISPONIBLE: Slot ${slot} (Archivador #${numArch})`;
        },

        handleSlotClick(numArch, slot) {
            if (this.isQualityUser) return;

            if (this.isSlotOccupiedByOther(numArch, slot)) {
                const occ = this.getSlotInfo(numArch, slot);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Slot Ocupado',
                        text: `El Slot ${slot} del Archivador #${numArch} ya está ocupado por el Lote ${occ.lote} (OP: ${occ.op}). Seleccione un slot disponible.`,
                        confirmButtonColor: '#DE2021'
                    });
                } else {
                    alert(`El Slot ${slot} del Archivador #${numArch} ya está ocupado por el Lote ${occ.lote}.`);
                }
                return;
            }

            this.seleccionarArchivador(numArch, slot);
        },

        seleccionarArchivador(numArch, slot) {
            if (this.isQualityUser) return;

            this.archivadorSeleccionado = numArch;
            this.slotSeleccionado = slot || 1;
            
            const formatted = `R 1 N ${this.nivelActual} A ${numArch} S ${this.slotSeleccionado}`;
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
        },

        guardarNuevaUbicacionAjax() {
            if (this.isQualityUser) {
                if (window.Swal) {
                    Swal.fire('Acceso Restringido', 'El rol de Calidad solo tiene permisos de lectura y consulta.', 'warning');
                }
                return;
            }

            if (!this.currentOrderId) {
                if (window.Swal) {
                    Swal.fire('Ubicación Seleccionada', 'La ubicación se guardará automáticamente al enviar el formulario.', 'info');
                }
                return;
            }

            this.saving = true;
            fetch(`/maquilas/${this.currentOrderId}/update-location`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    posicion_archivo_fisico: this.posicionFormateada
                })
            })
            .then(r => r.json())
            .then(data => {
                this.saving = false;
                if (data.success) {
                    this.initialPosition = this.posicionFormateada;
                    this.fetchOccupiedSlots();
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Ubicación Guardada',
                            text: data.message || `Ubicación actualizada a ${this.posicionFormateada}.`,
                            confirmButtonColor: '#005889'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        alert(data.message || 'Ubicación actualizada correctamente.');
                        location.reload();
                    }
                } else {
                    if (window.Swal) {
                        Swal.fire('Error', data.message || 'No se pudo guardar la ubicación.', 'error');
                    } else {
                        alert(data.message || 'Error al guardar');
                    }
                }
            })
            .catch(err => {
                this.saving = false;
                console.error(err);
                alert('Error al guardar la ubicación: ' + err);
            });
        }
    };
}
</script>
