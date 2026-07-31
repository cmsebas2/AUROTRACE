@extends('layouts.app')

@section('header_title', 'Dashboard de Reacondicionamiento')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">



    <!-- Advanced Analytics KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Lead Time -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6 flex flex-col relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition text-6xl text-blue-500">
                <i class="fas fa-stopwatch"></i>
            </div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1 z-10">Lead Time Promedio</p>
            <h3 class="text-3xl font-black text-[#0A2540] z-10">{{ $avgLeadTime }} <span class="text-lg font-normal text-gray-400">días</span></h3>
            <p class="text-xs text-gray-400 mt-2 z-10">Tiempo en piso hasta salida</p>
        </div>

        <!-- Tasa de Recuperación -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6 flex flex-col relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition text-6xl text-green-500">
                <i class="fas fa-shield-alt"></i>
            </div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1 z-10">Tasa de Recuperación</p>
            <h3 class="text-3xl font-black text-green-600 z-10">{{ $recoveryRate }}%</h3>
            <p class="text-xs font-bold text-green-700 z-10">({{ number_format($ptQtyKilos, 2) }} KIL / {{ number_format($ptQtyUnits, 0) }} UND)</p>
            <p class="text-[10px] text-gray-400 mt-1 z-10">Unidades salvadas a Bodega PT</p>
        </div>

        <!-- Merma -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6 flex flex-col relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition text-6xl text-orange-500">
                <i class="fas fa-boxes-packing"></i>
            </div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1 z-10">Índice de Merma</p>
            <h3 class="text-3xl font-black text-orange-500 z-10">{{ $wasteIndex }}%</h3>
            <p class="text-xs text-gray-400 mt-2 z-10">Exceso de insumos consumidos</p>
        </div>

        <!-- Countdown Vencimiento -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6 flex flex-col relative overflow-hidden group hover:shadow-md transition {{ isset($closestExpirationDays) && $closestExpirationDays < 15 ? 'ring-2 ring-red-500 bg-red-50' : '' }}">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition text-6xl {{ isset($closestExpirationDays) && $closestExpirationDays < 15 ? 'text-red-500' : 'text-purple-500' }}">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1 z-10">Vencimiento Crítico</p>
            <h3 class="text-3xl font-black z-10 {{ isset($closestExpirationDays) && $closestExpirationDays < 15 ? 'text-red-600 animate-pulse' : 'text-[#0A2540]' }}">
                @if(isset($closestExpirationDays))
                    {{ $closestExpirationDays }} <span class="text-lg font-normal text-gray-400">días</span>
                @else
                    --
                @endif
            </h3>
            <p class="text-xs {{ isset($closestExpirationDays) && $closestExpirationDays < 15 ? 'text-red-500 font-bold' : 'text-gray-400' }} mt-2 z-10">Lote activo más próximo a caducar</p>
        </div>
    </div>

    <!-- Inventory & Risk Cards (Current) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Units -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6 flex flex-col items-center justify-center relative overflow-hidden">
            <div class="absolute top-0 w-full h-1 bg-[#0A2540]"></div>
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-widest mb-1 text-center">Volumen Activo</p>
            <div class="flex space-x-4 mt-1 text-center">
                <div>
                    <h3 class="text-2xl font-black text-[#0A2540]">{{ number_format($totalKilos, 2) }}</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Kilos</p>
                </div>
                <div class="border-l border-gray-300"></div>
                <div>
                    <h3 class="text-2xl font-black text-[#0A2540]">{{ number_format($totalUnits, 0) }}</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Unidades</p>
                </div>
            </div>
        </div>

        <!-- Ocupancy -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6 flex flex-col items-center justify-center relative overflow-hidden">
            <div class="absolute top-0 w-full h-1 bg-aurofarma-teal"></div>
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-widest mb-1">Estibas Ocupadas</p>
            <h3 class="text-4xl font-black text-[#0A2540]">{{ $totalPallets }}</h3>
            <div class="w-full bg-gray-200 rounded-full h-2.5 mt-3">
                <div class="bg-aurofarma-teal h-2.5 rounded-full" style="width: {{ $occupancyPercentage }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($occupancyPercentage, 0) }}% Capacidad est.</p>
        </div>

        <!-- High Risk -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6 flex flex-col items-center justify-center relative overflow-hidden">
            <div class="absolute top-0 w-full h-1 bg-red-600"></div>
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-widest mb-1">Riesgo Alto</p>
            <h3 class="text-4xl font-black text-red-600">{{ $risk1 }}</h3>
            <p class="text-xs text-gray-400 mt-2">Lotes Nivel 1</p>
        </div>

        <!-- Medium Risk -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6 flex flex-col items-center justify-center relative overflow-hidden">
            <div class="absolute top-0 w-full h-1 bg-yellow-500"></div>
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-widest mb-1">Riesgo Medio</p>
            <h3 class="text-4xl font-black text-yellow-500">{{ $risk2 }}</h3>
            <p class="text-xs text-gray-400 mt-2">Lotes Nivel 2</p>
        </div>
    </div>

    <!-- Quick Actions & Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-[#0A2540] mb-4 flex items-center">
                <i class="fas fa-bolt text-yellow-400 mr-2"></i> Acciones Rápidas
            </h3>
            <div class="space-y-3">
                <a href="{{ route('reconditioning.create') }}" class="block w-full text-left px-4 py-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded text-sm font-medium text-[#0A2540] transition">
                    <i class="fas fa-plus-circle text-aurofarma-teal w-6"></i> Registrar Nueva Entrada
                </a>
                <a href="{{ route('reconditioning.planner') }}" class="block w-full text-left px-4 py-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded text-sm font-medium text-[#0A2540] transition">
                    <i class="fas fa-tasks text-aurofarma-blue w-6"></i> Ver Planificador Semanal
                </a>
            </div>
        </div>

        <div class="bg-[#0A2540] rounded shadow-sm border border-gray-800 p-6 text-white flex flex-col justify-center items-center text-center">
            <img src="{{ asset('img/logo.png') }}" alt="Logo" class="w-32 mb-4 opacity-80 mix-blend-screen brightness-200">
            <h2 class="text-xl font-bold tracking-wider text-aurofarma-teal uppercase mb-2">AuroTrace</h2>
            <p class="text-sm text-gray-400">Módulo Integral de Reacondicionamiento.<br>Gestión CFR 21 Compliant.</p>
        </div>
    </div>

    <!-- Legend Section (Footer) -->
    <div class="bg-gray-50 rounded border border-gray-200 p-4 mt-6 text-sm">
        <h4 class="font-bold text-gray-700 mb-2 uppercase tracking-wide"><i class="fas fa-info-circle text-blue-500 mr-1"></i> Leyenda de Niveles de Riesgo</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center">
                <span class="inline-block w-3 h-3 rounded-full bg-red-600 mr-2 shadow"></span>
                <span class="text-gray-600"><b>Nivel 1 (Rojo):</b> Producto de Terceros o Vencimiento &lt; 3 meses.</span>
            </div>
            <div class="flex items-center">
                <span class="inline-block w-3 h-3 rounded-full bg-yellow-500 mr-2 shadow"></span>
                <span class="text-gray-600"><b>Nivel 2 (Amarillo):</b> Vencimiento entre 3 y 6 meses.</span>
            </div>
            <div class="flex items-center">
                <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2 shadow"></span>
                <span class="text-gray-600"><b>Nivel 3 (Verde):</b> Vencimiento &gt; 6 meses.</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush
