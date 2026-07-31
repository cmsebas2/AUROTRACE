@extends('layouts.app')

@section('content')
<div class="w-full max-w-md bg-white rounded-xl shadow-xl overflow-hidden relative">
    <!-- Gradient Top Bar -->
    <div class="h-1.5 w-full flex">
        <div class="h-full flex-1 bg-[#04BFAD]"></div>
        <div class="h-full flex-1 bg-[#F23535]"></div>
        <div class="h-full flex-1 bg-[#F28E13]"></div>
        <div class="h-full flex-1 bg-[#048ABF]"></div>
    </div>
    
    <div class="bg-gray-50 py-6 px-8 text-center shadow-sm flex flex-col items-center">
        <div class="flex items-center justify-center mb-1">
            <img src="{{ asset('img/logo.png') }}" alt="Aurofarma Logo" class="h-14 object-contain">
        </div>
        <p class="text-gray-500 font-bold text-xs mt-1 tracking-widest uppercase">Sistema MES / EBR</p>
    </div>

    <div class="p-8">
        @if ($errors->any())
            <div class="mb-6 p-4 rounded bg-red-50 border-l-4 border-red-500 text-red-700">
                <p class="font-bold">Error de Autenticación</p>
                <ul class="list-disc pl-5 mt-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" class="space-y-6">
            @csrf

            <!-- User -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm text-lg"
                       placeholder="Ej: admin, operario, inspector_calidad">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <div class="relative">
                    <input id="password" type="password" name="password" required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-aurofarma-blue focus:border-aurofarma-blue transition shadow-sm text-lg pr-12"
                           placeholder="••••••••">
                    
                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-500 hover:text-aurofarma-blue focus:outline-none">
                        <svg id="eyeIcon" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <!-- Eye Open -->
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember" type="checkbox" name="remember" class="h-5 w-5 text-aurofarma-blue focus:ring-aurofarma-blue border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-900">Mantener sesión iniciada</label>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="w-full flex justify-center py-4 px-4 border border-transparent rounded-lg shadow-sm text-lg font-bold text-white bg-aurofarma-blue hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-aurofarma-blue transition-opacity uppercase tracking-wide">
                    INICIAR SESIÓN
                </button>
            </div>
            
            <p class="text-center text-xs text-gray-500 mt-4">
                El acceso a este sistema está auditado según CFR 21 Parte 11.
            </p>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
    });
</script>
@endpush
@endsection
