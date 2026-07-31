<!-- Modal QA Verification Enterprise (21 CFR Part 11) -->
<div id="qaVerificationModal" class="fixed inset-0 z-[100] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border-t-4 border-aurofarma-blue">
            <div class="bg-white px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-black text-gray-900 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-aurofarma-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Verificación de Calidad
                </h3>
                <button type="button" onclick="closeQaModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="px-8 py-6">
                <form id="qa-auth-form" onsubmit="event.preventDefault(); submitQaVerification();" class="space-y-5">
                    <!-- Responsable selector (QA) -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-1">Responsable de Calidad</label>
                        <select id="qa_on_behalf_of_id" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 font-bold text-slate-800 focus:border-aurofarma-blue focus:ring-0 transition-colors">
                            @foreach($calidad as $qa_user)
                                <option value="{{ $qa_user->id }}" {{ Auth::id() == $qa_user->id ? 'selected' : '' }}>{{ $qa_user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-1">Contraseña</label>
                        <input type="password" id="qa_password" required class="w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 font-bold text-slate-800 bg-gray-50 focus:border-aurofarma-blue transition-all" placeholder="••••••••">
                    </div>

                    <div class="pt-4 flex gap-2">
                        <button type="button" onclick="closeQaModal()" class="w-1/3 py-3 border border-gray-300 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-all uppercase text-xs">
                            CANCELAR
                        </button>
                        <button type="submit" id="btn-qa-submit" class="w-2/3 py-4 border border-transparent rounded-xl shadow-lg shadow-blue-100 text-sm font-black text-white bg-aurofarma-blue hover:opacity-90 active:scale-[0.98] transition-all flex justify-center items-center uppercase tracking-widest">
                            VERIFICAR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

    function openQaModal(contextLabel) {
        document.getElementById('qaVerificationModal').classList.remove('hidden');
        // El label se puede usar si hay un placeholder para el título
        document.getElementById('qa-auth-form').reset();
        document.getElementById('qa_password').focus();
    }

    function closeQaModal() {
        document.getElementById('qaVerificationModal').classList.add('hidden');
    }
</script>
