    @props(['title' => 'FIRMA DIGITAL', 'subtitle' => 'AuroTrace Security Protocol', 'defaultReason' => 'AUTORIZACIÓN DE REGISTRO INDUSTRIAL'])
    
    <div x-data="{
        cfrModalOpen: false,
        username: '',
        password: '',
        reason: '{{ $defaultReason }}',
        submitting: false,
        formElement: null,
        errorMessage: null,
        callbackFn: null,
        
        init() {
            window.openCfr21ModalAjax = (callback, customReason = '') => {
                this.callbackFn = callback;
                this.cfrModalOpen = true;
                this.username = '';
                this.password = '';
                this.reason = customReason || '{{ $defaultReason }}';
                this.errorMessage = null;
                setTimeout(() => this.$refs.cfrUserInput.focus(), 100);
            };
            window.closeCfr21ModalAjax = () => {
                this.cfrModalOpen = false;
                this.submitting = false;
            };
            window.setCfr21ErrorAjax = (msg) => {
                this.errorMessage = msg;
                this.submitting = false;
            };
        },

        openModal(event) {
            event.preventDefault();
            this.formElement = event.target;
            this.callbackFn = null;
            this.cfrModalOpen = true;
            this.username = '';
            this.password = '';
            this.reason = '{{ $defaultReason }}';
            this.errorMessage = null;
            setTimeout(() => this.$refs.cfrUserInput.focus(), 100);
        },

        submitSignature() {
            if (!this.username || !this.password) {
                this.errorMessage = 'Debe ingresar Usuario y Contraseña Personal.';
                return;
            }
            this.submitting = true;
            this.errorMessage = null;
            
            if (this.callbackFn) {
                // Modo AJAX - Se pasan los parámetros al callback
                this.callbackFn(this.password, this.reason, this.username);
            } else if (this.formElement) {
                // Modo Formulario Tradicional
                let userInput = document.createElement('input');
                userInput.type = 'hidden'; userInput.name = 'username'; userInput.value = this.username;
                let pwdInput = document.createElement('input');
                pwdInput.type = 'hidden'; pwdInput.name = 'password'; pwdInput.value = this.password;
                let reasonInput = document.createElement('input');
                reasonInput.type = 'hidden'; reasonInput.name = 'reason'; reasonInput.value = this.reason;
                
                this.formElement.appendChild(userInput);
                this.formElement.appendChild(pwdInput);
                this.formElement.appendChild(reasonInput);
                this.formElement.submit();
            }
        }
    }">

    <div @submit="openModal($event)">
        {{ $slot }}
    </div>

    <!-- The Modal Backdrop -->
    <div x-show="cfrModalOpen" x-cloak class="fixed z-[9999] inset-0 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="cfrModalOpen" x-transition.opacity class="fixed inset-0 transition-opacity" aria-hidden="true" @click="if(!submitting) cfrModalOpen = false">
                <div class="absolute inset-0 bg-[#0A2540]/60 backdrop-blur-sm"></div>
            </div>

            <!-- Modal Content -->
            <div x-show="cfrModalOpen" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 class="inline-block align-middle bg-white rounded-none text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border-4 border-[#0A2540]">
                
                <!-- Encabezado Institucional -->
                <div class="px-6 py-6 text-center border-b border-[#D9D9D9] bg-white">
                    <!-- Logo Placeholder -->
                    <div class="flex justify-center mb-4">
                        <div class="w-12 h-12 bg-[#0A2540] rounded-sm flex items-center justify-center text-white font-black text-xl">A</div>
                    </div>
                    <h3 class="text-xl font-black text-[#0A2540] tracking-[0.2em] uppercase">FIRMA DIGITAL</h3>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-[0.3em] mt-2">AuroTrace Security Protocol</p>
                </div>
                
                <div class="px-10 py-8 space-y-6">
                    <!-- Errores -->
                    <template x-if="errorMessage">
                        <div class="bg-red-50 border border-red-200 p-3 rounded-none animate-pulse">
                            <p class="text-[10px] text-red-700 font-bold uppercase tracking-widest text-center" x-text="errorMessage"></p>
                        </div>
                    </template>

                    <div class="space-y-5">
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-[#0A2540] uppercase tracking-[0.2em]">
                                USUARIO AUTORIZADO
                            </label>
                            <input type="text" x-model="username" x-ref="cfrUserInput" :disabled="submitting"
                                   class="w-full border-2 border-[#D9D9D9] p-3 text-sm font-bold focus:border-[#0A2540] outline-none transition-all placeholder-slate-300 uppercase" 
                                   placeholder="NOMBRE DE USUARIO">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-[#0A2540] uppercase tracking-[0.2em]">
                                CONTRASEÑA PERSONAL
                            </label>
                            <input type="password" x-model="password" x-ref="cfrPwdInput" :disabled="submitting"
                                   class="w-full border-2 border-[#D9D9D9] p-3 text-sm font-bold focus:border-[#0A2540] outline-none transition-all placeholder-slate-300" 
                                   placeholder="••••••••" @keyup.enter="submitSignature">
                        </div>
                    </div>
                </div>

                <!-- Footer & Acciones -->
                <div class="px-10 pb-10 space-y-4">
                    <button type="button" @click="submitSignature" :disabled="submitting" 
                            class="w-full py-4 bg-[#0A2540] text-white text-xs font-black tracking-[0.3em] uppercase hover:bg-slate-800 transition-all disabled:opacity-50">
                        <span x-show="!submitting">SELLAR REGISTRO</span>
                        <span x-show="submitting" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            VALIDANDO...
                        </span>
                    </button>
                    
                    <button type="button" @click="cfrModalOpen = false" :disabled="submitting" 
                            class="w-full py-3 border border-[#D9D9D9] text-[10px] font-bold text-slate-400 tracking-[0.2em] uppercase hover:bg-slate-50 transition-all">
                        CANCELAR
                    </button>

                    <!-- Advertencia Legal CFR 21 -->
                    <div class="pt-4 border-t border-[#D9D9D9]">
                        <p class="text-[8px] text-slate-400 font-medium leading-relaxed uppercase tracking-tighter">
                            Este registro electrónico está sujeto a la norma <strong>21 CFR Part 11</strong>. El uso de sus credenciales personales equivale legalmente a una firma manuscrita vinculante.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
