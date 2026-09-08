@props([
    'module' => 'GLOBAL', 
    'action' => 'FIRMA', 
    'role' => null, 
    'compact' => false,
    'buttonText' => 'FIRMAR',
    'buttonClass' => "'bg-[#0A2540] text-white px-4 py-2 rounded text-[10px] hover:bg-blue-900 font-bold w-full shadow-sm transition-all'",
    'initialSigned' => false,
    'initialName' => '',
    'initialDate' => '',
    'initialHour' => '',
    'initialHtml' => ''
])

<div {{ $attributes }} x-data="{
    showModal: false,
    isSigning: false,
    error: null,
    username: '',
    password: '',
    reason: '{{ $action }}',
    
    // Estado de Firma Centralizado
    signed: @json($initialSigned),
    signerName: '{{ $initialName }}',
    signDate: '{{ $initialDate }}',
    signHour: '{{ $initialHour }}',
    signatureHtml: '{!! $initialHtml !!}',

    init() {
        // Escucha global por si se quiere disparar desde JS nativo
        window.addEventListener('open-signature-{{ $module }}-{{ $role }}', () => this.open());
    },

    open() {
        if (this.signed) return;
        this.showModal = true;
        this.error = null;
        this.username = '';
        this.password = '';
        setTimeout(() => this.$refs.userInput?.focus(), 100);
    },

    async validate() {
        if (!this.username || !this.password) {
            this.error = 'Usuario y contraseña requeridos.';
            return;
        }

        this.isSigning = true;
        this.error = null;

        try {
            const response = await axios.post('/api/system/validate-signature', {
                username: this.username,
                password: this.password,
                role: '{{ $role }}',
                reason: this.reason,
                context: '{{ $module }}',
                compact: {{ $compact ? 'true' : 'false' }}
            });

            if (response.data.success) {
                // Actualizar estado interno
                this.signed = true;
                this.signerName = response.data.user_name;
                this.signatureHtml = response.data.signature_html;
                
                const tsParts = response.data.timestamp ? response.data.timestamp.split(' ') : ['', ''];
                this.signDate = tsParts[0];
                this.signHour = tsParts[1];

                // FASE 1: Emitir evento universal desacoplado
                this.$dispatch('signature-verified', { 
                    ...response.data, 
                    role: '{{ $role }}',
                    username: this.username,
                    password: this.password
                });
                this.showModal = false;
            }
        } catch (err) {
            this.error = err.response?.data?.message || 'Error de comunicación con el servidor.';
        } finally {
            this.isSigning = false;
        }
    }
}" @open-cfr-modal.window="if($event.detail.role === '{{ $role }}') open()">

    <!-- ESTADO 1: YA FIRMADO (Visualización Universal) -->
    <template x-if="signed">
        <div class="flex flex-col items-center justify-center py-1 w-full bg-white">
            <div x-html="signatureHtml" class="flex justify-center w-full" style="min-height: 32px;"></div>
        </div>
    </template>

    <!-- ESTADO 2: PENDIENTE DE FIRMA (Botón Disparador) -->
    <template x-if="!signed">
        <div>
            <button type="button" @click="open" :class="{{ $buttonClass }}" {{ $attributes->filter(fn($v, $k) => $k !== 'class') }}>
                {{ $buttonText }}
            </button>
        </div>
    </template>

    <!-- Modal Teleportado al body para evitar problemas de Z-Index -->
    <template x-teleport="body">
        <div x-show="showModal" x-cloak style="display: none;" class="fixed z-[9999] inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="!isSigning && (showModal = false)"></div>

                <div class="inline-block align-middle bg-white rounded-none text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md sm:w-full border-4 border-[#0A2540]">
                    <div class="px-6 py-6 text-center border-b border-gray-200">
                        <div class="flex justify-center mb-4">
                            <div class="w-12 h-12 bg-[#0A2540] rounded-sm flex items-center justify-center text-white font-black text-xl">A</div>
                        </div>
                        <h3 class="text-xl font-black text-[#0A2540] tracking-widest uppercase">AUTENTICACIÓN CFR 21</h3>
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-2">{{ $module }} | {{ $action }}</p>
                    </div>

                    <div class="p-8 space-y-6">
                        <template x-if="error">
                            <div class="bg-red-50 border border-red-200 p-3 text-center">
                                <p class="text-[10px] text-red-700 font-bold uppercase tracking-widest" x-text="error"></p>
                            </div>
                        </template>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-[#0A2540] uppercase tracking-widest mb-1">Usuario</label>
                                <input type="text" x-model="username" x-ref="userInput" :disabled="isSigning"
                                       class="w-full border-2 border-gray-200 p-3 text-sm font-bold focus:border-[#0A2540] outline-none uppercase">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-[#0A2540] uppercase tracking-widest mb-1">Contraseña</label>
                                <input type="password" x-model="password" :disabled="isSigning" @keyup.enter="validate"
                                       class="w-full border-2 border-gray-200 p-3 text-sm font-bold focus:border-[#0A2540] outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="px-8 pb-8 space-y-3">
                        <button type="button" @click="validate" :disabled="isSigning"
                                class="w-full py-4 bg-[#0A2540] text-white text-xs font-black tracking-widest uppercase hover:bg-slate-800 disabled:opacity-50 transition-all">
                            <span x-show="!isSigning">VALIDAR IDENTIDAD</span>
                            <span x-show="isSigning" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                PROCESANDO...
                            </span>
                        </button>
                        <button type="button" @click="showModal = false" :disabled="isSigning"
                                class="w-full py-3 border border-gray-200 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:bg-gray-50">
                            CANCELAR
                        </button>
                    </div>

                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <p class="text-[8px] text-gray-400 leading-tight uppercase">
                            Este acto es una firma electrónica vinculante bajo la norma <strong>21 CFR Part 11</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
