@extends('layouts.app')

@section('header_title', 'Line Clearance - ' . $areaActual)

@section('content')
<div class="max-w-4xl mx-auto py-8">
    
    <!-- Progress Indicator -->
    @include('batch.partials.ebr-navigation')

    <!-- Info Card: Datos de la OP -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
        <div class="bg-slate-50 px-6 py-3 border-b border-gray-100 flex justify-between items-center">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Información de la Orden de Producción</span>
            <span class="px-3 py-1 bg-aurofarma-blue/10 text-aurofarma-blue text-xs font-black rounded-full">{{ $op->op_number }}</span>
        </div>
        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <p class="text-xs text-gray-400 font-bold uppercase mb-1">Producto</p>
                <p class="text-sm font-bold text-slate-800">{{ $op->product->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-bold uppercase mb-1">Lote</p>
                <p class="text-sm font-black text-slate-900">{{ $op->lote }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-bold uppercase mb-1">Tamaño Lote</p>
                <p class="text-sm font-bold text-slate-800">{{ number_format($op->bulk_size_kg, 2, '.', '') }} KG</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-bold uppercase mb-1">Vencimiento</p>
                <p class="text-sm font-bold text-slate-800">{{ \Carbon\Carbon::parse($op->expiration_date)->format('m-Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Main Form Card -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-aurofarma-blue to-blue-700 px-6 py-5 border-b border-gray-200 flex items-center">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg leading-6 font-bold text-white">Despeje de Línea - Área: {{ $areaActual }}</h3>
                <p class="mt-1 text-sm text-blue-100">Complete el checklist de verificación para habilitar el área.</p>
            </div>
        </div>
        
        <div class="p-8">
            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if($areaActual === 'Completado')
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200 text-center">
                    <p class="text-lg font-bold text-green-800">Todos los despejes de línea para esta Orden de Producción han sido completados.</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-xl bg-red-50 p-4 border border-red-200 shadow">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3 font-bold text-red-800">
                            <h3 class="text-sm uppercase tracking-wide">Error al enviar el formulario</h3>
                            <ul class="mt-2 text-xs list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form id="form-checklist-despeje" action="{{ route('batch.despeje.store', $op) }}" method="POST" class="space-y-4">
                @csrf
                @if($areaActual !== 'Completado')
                    <input type="hidden" name="area" value="{{ $areaActual }}">
                @endif

                <div class="overflow-x-auto overflow-y-visible pb-4">
                    <table class="w-full border-collapse border border-gray-300 text-sm">
                        <thead>
                            <tr class="bg-gray-100 text-slate-700">
                                <th class="border border-gray-300 p-3 text-left w-1/3 shadow-[inset_0_2px_0_rgba(0,0,0,0.05)]">Información / Verificación</th>
                                @foreach(['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'] as $areaCol)
                                    <th class="border border-gray-300 p-3 text-center {{ $areaCol === $areaActual ? 'bg-aurofarma-blue text-white ring-2 ring-aurofarma-blue ring-inset shadow-md' : 'shadow-[inset_0_2px_0_rgba(0,0,0,0.05)]' }}">
                                        {{ $areaCol }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Bloque 1: Info Inicial -->
                            <tr>
                                <td colspan="5" class="bg-slate-200 text-slate-700 font-black px-3 py-2 uppercase text-xs tracking-wider border border-gray-300">
                                    Información Inicial
                                </td>
                            </tr>
                            <!-- Fecha de Inicio -->
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold bg-gray-50 text-slate-700">Fecha de Inicio</td>
                                @foreach(['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'] as $areaCol)
                                    @php
                                        $isActive = ($areaCol === $areaActual);
                                        $colDespeje = $despejes->firstWhere('area', $areaCol);
                                        $val = $colDespeje ? (\Carbon\Carbon::parse($colDespeje->fecha_inicio)->format('Y-m-d')) : ($isActive ? \Carbon\Carbon::now()->format('Y-m-d') : '');
                                        
                                        // A column is disabled if it's completely inactive OR if it's the active one but it already has data (we are just here to sign it off)
                                        $isDisabled = (!$isActive && !$colDespeje) || $areaActual === 'Completado';
                                        $isReadonly = $colDespeje ? true : false;
                                    @endphp
                                    <td class="border border-gray-300 p-2 text-center bg-gray-100 {{ ($isDisabled || $isReadonly) ? 'cursor-not-allowed' : '' }}">
                                        @if($isActive)
                                            <input type="hidden" name="fecha_inicio" value="{{ $val }}">
                                        @endif
                                        <input type="text"
                                               value="{{ $val }}"
                                               {{ $isDisabled || $isReadonly ? 'readonly' : '' }}
                                               class="w-full text-center bg-transparent border-none p-1 {{ ($isDisabled || $isReadonly) ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 font-semibold outline-none focus:ring-0' }}">
                                    </td>
                                @endforeach
                            </tr>
                            <!-- Hora de Inicio -->
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold bg-gray-50 text-slate-700">Hora de Inicio</td>
                                @foreach(['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'] as $areaCol)
                                    @php
                                        $isActive = ($areaCol === $areaActual);
                                        $colDespeje = $despejes->firstWhere('area', $areaCol);
                                        // Some DB drivers return H:i:s, we want H:i
                                        $val = $colDespeje ? substr($colDespeje->hora_inicio, 0, 5) : ($isActive ? \Carbon\Carbon::now()->format('H:i') : '');
                                        $isDisabled = (!$isActive && !$colDespeje) || $areaActual === 'Completado';
                                        $isReadonly = $colDespeje ? true : false;
                                    @endphp
                                    <td class="border border-gray-300 p-2 text-center bg-gray-100 {{ ($isDisabled || $isReadonly) ? 'cursor-not-allowed' : '' }}">
                                        @if($isActive)
                                            <input type="hidden" name="hora_inicio" value="{{ $val }}">
                                        @endif
                                        <input type="text"
                                               value="{{ $val }}"
                                               {{ $isDisabled || $isReadonly ? 'readonly' : '' }}
                                               class="w-full text-center bg-transparent border-none p-1 {{ ($isDisabled || $isReadonly) ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 font-semibold outline-none focus:ring-0' }}">
                                    </td>
                                @endforeach
                            </tr>
                            <!-- Producto Anterior -->
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold bg-gray-50 text-slate-700">Producto Anterior</td>
                                @foreach(['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'] as $areaCol)
                                    @php
                                        $isActive = ($areaCol === $areaActual);
                                        $colDespeje = $despejes->firstWhere('area', $areaCol);
                                        $val = $colDespeje ? $colDespeje->producto_anterior : ($isActive ? $productoAnteriorAuto : '');
                                        $isDisabled = (!$isActive && !$colDespeje) || $areaActual === 'Completado';
                                        $isReadonly = $colDespeje ? true : false;
                                    @endphp
                                    <td class="border border-gray-300 p-2 text-center bg-gray-100 {{ ($isDisabled || $isReadonly) ? 'cursor-not-allowed' : '' }}">
                                        @if($isActive)
                                            <input type="hidden" name="producto_anterior" value="{{ $val }}">
                                        @endif
                                        <input type="text"
                                               value="{{ $val }}"
                                               readonly
                                               class="w-full text-center bg-transparent border-none p-1 {{ ($isDisabled || $isReadonly) ? 'text-gray-400 cursor-not-allowed' : 'text-gray-900 font-black focus:ring-0' }}">
                                    </td>
                                @endforeach
                            </tr>
                            <!-- Lote Anterior -->
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold bg-gray-50 text-slate-700">Lote Anterior</td>
                                @foreach(['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'] as $areaCol)
                                    @php
                                        $isActive = ($areaCol === $areaActual);
                                        $colDespeje = $despejes->firstWhere('area', $areaCol);
                                        $val = $colDespeje ? $colDespeje->lote_anterior : ($isActive ? $loteAnteriorAuto : '');
                                        $isDisabled = (!$isActive && !$colDespeje) || $areaActual === 'Completado';
                                        $isReadonly = $colDespeje ? true : false;
                                    @endphp
                                    <td class="border border-gray-300 p-2 text-center bg-gray-100 {{ ($isDisabled || $isReadonly) ? 'cursor-not-allowed' : '' }}">
                                        @if($isActive)
                                            <input type="hidden" name="lote_anterior" value="{{ $val }}">
                                        @endif
                                        <input type="text"
                                               value="{{ $val }}"
                                               readonly
                                               class="w-full text-center bg-transparent border-none p-1 {{ ($isDisabled || $isReadonly) ? 'text-gray-400 cursor-not-allowed' : 'text-gray-900 font-black focus:ring-0' }}">
                                    </td>
                                @endforeach
                            </tr>
                            
                            <!-- Separator -->
                            <tr>
                                <td colspan="5" class="bg-slate-200 text-slate-700 font-black px-3 py-2 uppercase text-xs tracking-wider border border-gray-300 mt-2">
                                    Checklist de Verificación
                                </td>
                            </tr>

                            <!-- Preguntas -->
                            @php
                                $preguntas = [
                                    "1. ¿Se ha realizado la limpieza del producto anterior?",
                                    "2. ¿Se encuentra en el área la documentación completa para ejecutar el proceso?",
                                    "3. ¿El área se encuentra identificada?",
                                    "4. ¿Las paredes se encuentran limpias?",
                                    "5. ¿Los mesones se encuentran limpios?",
                                    "6. ¿El piso se encuentra limpio?",
                                    "7. ¿Se encuentran los equipos identificados?",
                                    "8. ¿Se retiran los materiales del lote anterior?",
                                    "9. ¿El personal utiliza el uniforme respectivo del área?",
                                    "10. ¿El personal cuenta con EPP?",
                                    "11. ¿El personal cuenta con los elementos y utensilios para la realización del proceso?",
                                    "12. ¿Se encuentran las materias primas y los materiales a usar aprobados?"
                                ];
                            @endphp
                            @foreach($preguntas as $index => $pregunta)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="border border-gray-300 p-3 text-sm font-medium text-gray-700 bg-gray-50/50 leading-snug">{{ $pregunta }}</td>
                                    @foreach(['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'] as $areaCol)
                                        @php
                                            $isActive = ($areaCol === $areaActual);
                                            $colDespeje = $despejes->firstWhere('area', $areaCol);
                                            $ans = '';
                                            if ($colDespeje) {
                                                $lista = is_string($colDespeje->respuestas_checklist) ? json_decode($colDespeje->respuestas_checklist, true) : $colDespeje->respuestas_checklist;
                                                $ans = is_array($lista) ? ($lista[$index] ?? '') : '';
                                            }
                                            $isDisabled = (!$isActive && !$colDespeje) || $areaActual === 'Completado';
                                        @endphp
                                        <td class="border border-gray-300 p-2 text-center align-middle {{ $isDisabled ? 'bg-gray-100 cursor-not-allowed' : ($isActive ? 'bg-blue-50/30' : '') }}">
                                            @if($colDespeje)
                                                @if($ans === 'SI')
                                                    <span class="inline-flex items-center justify-center px-2 py-1 rounded bg-teal-50 text-aurofarma-teal font-black text-xs border border-teal-200">SI</span>
                                                @elseif($ans === 'NO')
                                                    <span class="inline-flex items-center justify-center px-2 py-1 rounded bg-red-50 text-red-600 font-black text-xs border border-red-200">NO</span>
                                                @else
                                                    <span class="text-gray-400 font-bold">-</span>
                                                @endif
                                            @else
                                                <div class="flex justify-center space-x-3 {{ $isDisabled ? 'opacity-40' : '' }}">
                                                    <label class="inline-flex items-center {{ $isDisabled || $isReadonly ? 'cursor-not-allowed' : 'cursor-pointer hover:bg-white px-2 py-1 border border-transparent hover:border-gray-200 rounded transition-colors shadow-sm' }}">
                                                        <input type="radio" 
                                                               {{ $isActive ? 'name=respuestas['.$index.']' : '' }} 
                                                               value="SI" 
                                                               {{ $isDisabled || $isReadonly ? 'disabled' : 'required' }}
                                                               class="form-radio text-aurofarma-teal focus:ring-aurofarma-teal h-4 w-4 border-gray-300 cursor-pointer disabled:cursor-not-allowed">
                                                        <span class="ml-1 text-xs font-black text-gray-700">SI</span>
                                                    </label>
                                                    <label class="inline-flex items-center {{ $isDisabled || $isReadonly ? 'cursor-not-allowed' : 'cursor-pointer hover:bg-white px-2 py-1 border border-transparent hover:border-gray-200 rounded transition-colors shadow-sm' }}">
                                                        <input type="radio" 
                                                               {{ $isActive ? 'name=respuestas['.$index.']' : '' }} 
                                                               value="NO" 
                                                               {{ $isDisabled || $isReadonly ? 'disabled' : 'required' }}
                                                               class="form-radio text-red-500 focus:ring-red-500 h-4 w-4 border-gray-300 cursor-pointer disabled:cursor-not-allowed">
                                                        <span class="ml-1 text-xs font-black text-gray-700">NO</span>
                                                    </label>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            
                            <!-- Diferencial de Presión -->
                            <tr>
                                <td colspan="5" class="bg-slate-200 text-slate-700 font-black px-3 py-2 uppercase text-xs tracking-wider border border-gray-300 mt-2">
                                    Parámetros del Área
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold bg-gray-50 text-slate-700">Resultado Diferencial de Presión</td>
                                @foreach(['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'] as $areaCol)
                                    @php
                                        $isActive = ($areaCol === $areaActual);
                                        $colDespeje = $despejes->firstWhere('area', $areaCol);
                                        $val = $colDespeje ? $colDespeje->diferencial_presion : '';
                                        $isDisabled = (!$isActive && !$colDespeje) || $areaActual === 'Completado';
                                        $isReadonly = $colDespeje ? true : false;
                                    @endphp
                                    <td class="border border-gray-300 p-2 text-center {{ ($isDisabled || $isReadonly) ? 'bg-gray-100 cursor-not-allowed' : ($isActive ? 'bg-blue-50/30' : '') }}">
                                        <input type="text"
                                               {{ $isActive ? 'id=input-presion-operario' : '' }}
                                               {{ $isActive && !$isReadonly ? 'name=diferencial_presion' : '' }}
                                               placeholder="{{ $isActive && !$isReadonly ? 'Ej. 15 Pa' : '' }}"
                                               value="{{ $val }}"
                                               {{ $isDisabled || $isReadonly ? 'readonly' : '' }}
                                               class="w-full text-center bg-transparent focus:ring-aurofarma-blue {{ $isDisabled || $isReadonly ? 'border-none p-1 text-gray-400 cursor-not-allowed' : 'border-gray-300 rounded focus:border-aurofarma-blue px-2 py-1 bg-white shadow-sm text-gray-900 font-bold' }}">
                                    </td>
                                @endforeach
                            </tr>

                            <!-- Cierre y Firmas -->
                            <tr>
                                <td colspan="5" class="bg-slate-200 text-slate-700 font-black px-3 py-2 uppercase text-xs tracking-wider border border-gray-300 mt-2">
                                    Cierre y Firmas
                                                    <!-- Realizado Por -->
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold bg-gray-50 text-slate-700">REALIZADO POR</td>
                                @foreach(['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'] as $areaCol)
                                    @php
                                        $colDespeje = $despejes->firstWhere('area', $areaCol);
                                        $isActive = ($areaCol === $areaActual);
                                    @endphp
                                    <td class="border border-gray-300 p-2 text-center shadow-[inset_0_2px_0_rgba(0,0,0,0.02)] {{ $colDespeje ? 'bg-blue-50/50' : 'bg-gray-100' }}">
                                        @if($colDespeje)
                                            <x-cfr21-signature-flow 
                                                :initialSigned="true"
                                                :initialName="$colDespeje->realizadoPor->name ?? ''"
                                                :initialDate="$colDespeje->fecha_fin ? \Carbon\Carbon::parse($colDespeje->fecha_fin)->format('Y-m-d') : ''"
                                                :initialHour="$colDespeje->hora_fin ? \Carbon\Carbon::parse($colDespeje->hora_fin)->format('H:i:s') : ''"
                                            />
                                        @elseif($isActive)
                                            <span class="text-[10px] italic text-gray-400">Firmado al guardar</span>
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <!-- Verificado Por -->
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold bg-gray-50 text-slate-700">VERIFICADO POR</td>
                                @foreach(['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'] as $areaCol)
                                    @php
                                        $colDespeje = $despejes->firstWhere('area', $areaCol);
                                    @endphp
                                    <td class="border border-gray-300 p-2 text-center shadow-[inset_0_2px_0_rgba(0,0,0,0.02)] {{ $colDespeje && $colDespeje->verificadoPor ? 'bg-teal-50' : 'bg-gray-100' }}">
                                        @if($colDespeje && $colDespeje->verificadoPor)
                                            <x-cfr21-signature-flow 
                                                :initialSigned="true"
                                                :initialName="$colDespeje->verificadoPor->name ?? ''"
                                                :initialDate="$colDespeje->fecha_fin ? \Carbon\Carbon::parse($colDespeje->fecha_fin)->format('Y-m-d') : ''"
                                                :initialHour="$colDespeje->hora_fin ? \Carbon\Carbon::parse($colDespeje->hora_fin)->format('H:i:s') : ''"
                                            />
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
          @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if($areaActual !== 'Completado')
                    <!-- BLOQUE 4: Firmas -->
                    <div class="bg-slate-900 p-6 rounded-xl border border-slate-800 text-white relative overflow-hidden mt-6 shadow-xl">
                        <div class="absolute right-0 top-0 opacity-10 p-4">
                            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        </div>
                        <h4 class="text-xs font-black text-aurofarma-teal uppercase tracking-widest mb-3">Firma Electrónica (CFR 21 Parte 11)</h4>
                        <p class="text-sm text-slate-300">
                            Al guardar, este documento quedará firmado electrónicamente por el usuario autenticado: 
                            <span class="text-white font-black underline decoration-aurofarma-teal decoration-2">{{ Auth::user() ? Auth::user()->name : 'Usuario Actual' }}</span>
                        </p>
                        <p class="text-[10px] text-slate-500 mt-2 uppercase">Esta acción es equivalente a una firma manuscrita y tiene validez legal dentro del sistema de gestión de calidad.</p>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex justify-end space-x-4 pt-6 mt-6">
                        <button type="button" onclick="window.history.back()" class="px-8 py-3 bg-white border border-slate-300 rounded-xl text-slate-700 font-bold hover:bg-slate-50 transition-colors shadow-sm">
                            Cancelar
                        </button>
                        <button type="submit" class="px-10 py-3 bg-aurofarma-blue rounded-xl text-white font-black shadow-lg shadow-blue-200 hover:opacity-90 active:scale-95 transition-all flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A10.003 10.003 0 005 20c.908 0 1.787-.121 2.625-.348m4.375-3.652A10.003 10.003 0 0015 20c.908 0 1.787-.121 2.625-.348m-4.375-3.652a10.003 10.003 0 011.125-1.928m-4.375 3.652c-.655-.447-1.255-.983-1.786-1.594m1.786 1.594c.054-.09.117-.183.188-.277a10.016 10.016 0 012.353-2.353c.094-.07.186-.134.277-.188m0 0c.611.531 1.147 1.131 1.594 1.786m-1.594-1.786c.09.054.183.117.277.188A10.016 10.016 0 0114.277 15c.66.417 1.25.914 1.758 1.48m-1.758-1.48c.07.094.134.186.188.277a10.016 10.016 0 012.353 2.353c.09.054.183.117.277.188M12 11c0-2.209-1.791-4-4-4S4 8.791 4 11s1.791 4 4 4 4-1.791 4-4zm6.403 2.12c-.837-1.23-2.311-2.12-4.003-2.12s-3.166.89-4.003 2.12M12 11c0 2.209 1.791 4 4 4s4-1.791 4-4-1.791-4-4-4-4 1.791-4 4z"></path></svg>
                            FIRMAR Y GUARDAR DESPEJE
                        </button>
                    </div>
                @else
                    <div class="flex justify-center pt-6 mt-6 space-x-4">
                        <a href="{{ route('dashboard') }}" class="px-8 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition-all shadow-sm">
                            Volver al Dashboard
                        </a>
                        @php
                            $dispensacionComplete = $despejes->where('area', 'Dispensación')->whereNotNull('hora_fin')->isNotEmpty();
                        @endphp
                        @if ($dispensacionComplete && $areaActual === 'Fabricación')
                            <a href="{{ route('batch.fabricacion', $op) }}" class="px-8 py-3 bg-aurofarma-teal rounded-xl text-white font-black hover:opacity-90 transition-all shadow-lg flex items-center">
                                Ir a Módulo de Fabricación
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        @endif
                        @if ($areaActual === 'Completado')
                            <!-- Other module redirects will go here in the future -->
                        @endif
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>

<!-- Modal QA Verification -->
<div id="qaVerificationModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
            <!-- Header Modal -->
            <div class="bg-gradient-to-r from-aurofarma-blue to-blue-700 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-black text-white flex items-center" id="modal-title">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Doble Verificación de Calidad
                </h3>
                <button type="button" onclick="closeQaModal()" class="text-blue-100 hover:text-white transition-colors cursor-pointer z-50 relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Paso 1: Autenticación QA -->
            <div id="step-1-auth" class="px-8 py-8 relative">
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-50 mb-4 shadow-inner ring-4 ring-blue-50">
                        <svg class="h-8 w-8 text-aurofarma-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-black text-slate-800">Autenticación Requerida</h4>
                    <p class="text-sm text-slate-500 mt-2">Un responsable de Calidad debe validar el formulario antes de guardar.</p>
                </div>
                
                <div id="qa-auth-error" class="hidden mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm font-bold text-center"></div>

                <form id="qa-auth-form" onsubmit="event.preventDefault(); handleQaAuth();" class="space-y-5">
                    <!-- Responsable selector (QA) -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-1">Responsable de Calidad</label>
                        <select id="on_behalf_of_id_modal" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 font-bold text-slate-800 focus:border-aurofarma-blue focus:ring-0 transition-colors">
                            @foreach($calidad as $qa_user)
                                <option value="{{ $qa_user->id }}" {{ Auth::id() == $qa_user->id ? 'selected' : '' }}>{{ $qa_user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Su Contraseña</label>
                        <input type="password" id="qa-password" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-aurofarma-blue focus:border-aurofarma-blue bg-gray-50 text-gray-900 font-medium transition-all" placeholder="••••••••">
                    </div>
                    <div class="pt-4">
                        <button type="submit" id="btn-qa-auth" class="w-full py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-blue-200 text-sm font-black text-white bg-aurofarma-blue hover:opacity-90 active:scale-[0.98] transition-all flex justify-center items-center">
                            VALIDAR CREDENCIALES
                        </button>
                    </div>
                </form>
            </div>

            <!-- Paso 2: Checklist de Verificación -->
            <div id="step-2-checklist" class="hidden">
                <div class="bg-blue-50 px-8 py-5 border-b border-blue-100 flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-black text-slate-800">Verificando: <span id="qa-user-name-display" class="text-aurofarma-blue underline"></span></h4>
                        <p class="text-xs text-slate-500 font-medium mt-1">Por favor revise y confirme los puntos de Despeje de Línea.</p>
                    </div>
                    <div class="bg-green-100 text-green-700 p-2 rounded-full border border-green-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                </div>

                <div class="p-8">
                    <div class="bg-slate-50 rounded-xl p-5 border border-slate-200 mb-6 max-h-60 overflow-y-auto">
                        <h5 class="text-xs font-black uppercase text-slate-500 mb-3 tracking-wider">Puntos a confirmar:</h5>
                        <ul class="space-y-3 text-sm text-slate-700">
                            @foreach($preguntas as $index => $pregunta)
                                <li class="flex items-start">
                                    <div class="flex-shrink-0 mt-0.5 mr-3">
                                        <input type="checkbox" required class="qa-checkbox h-4 w-4 text-aurofarma-teal focus:ring-aurofarma-teal border-gray-300 rounded cursor-pointer">
                                    </div>
                                    <span class="font-medium leading-snug">{{ $pregunta }}</span>
                                </li>
                            @endforeach
                            <!-- Additional Core QA Parameter -->
                            <li class="flex items-start pt-3 mt-3 border-t border-slate-200">
                                <div class="flex-shrink-0 mt-0.5 mr-3">
                                    <input type="checkbox" required name="qa_presion_diferencial_conforme" id="qa_presion_diferencial_conforme" value="1" class="qa-checkbox h-4 w-4 text-aurofarma-teal focus:ring-aurofarma-teal border-gray-300 rounded cursor-pointer">
                                </div>
                                <span class="font-black text-slate-800 leading-snug">
                                    Parámetros del Área: Diferencial de Presión conforme.<br>
                                    <span class="text-xs text-slate-500 font-bold uppercase block mt-1">Valor reportado: <span id="display-presion-operario" class="font-black text-blue-600 px-1 py-0.5 bg-blue-50 rounded border border-blue-100 italic"></span></span>
                                </span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-slate-900 p-5 rounded-xl border border-slate-800 text-white mb-6 relative overflow-hidden">
                        <div class="absolute right-0 top-0 opacity-10 p-2">
                             <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        </div>
                        <h4 class="text-xs font-black text-aurofarma-teal uppercase tracking-widest mb-1">Firma Electrónica (QA)</h4>
                        <p class="text-xs text-slate-300">
                            Al hacer clic en Guardar, usted está firmando electrónicamente la verificación de este despeje de línea.
                        </p>
                    </div>

                    <div id="qa-save-error" class="hidden mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm font-bold text-center"></div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeQaModal()" class="px-6 py-3 border border-gray-300 rounded-xl shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="button" onclick="handleQaVerificationSave()" id="btn-qa-save" class="px-6 py-3 border border-transparent rounded-xl shadow-lg shadow-teal-200 text-sm font-black text-white bg-aurofarma-teal hover:opacity-90 active:scale-[0.98] transition-all flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            GUARDAR VERIFICACIÓN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let mainForm = null;
    let qaVerifiedUserId = null;

    document.addEventListener('DOMContentLoaded', function() {
        mainForm = document.getElementById('form-checklist-despeje');
        
        if (mainForm) {
            mainForm.addEventListener('submit', function(e) {
                e.preventDefault(); // ESTO ES OBLIGATORIO PARA DETENER EL AVANCE
                console.log('Evento submit interceptado y detenido correctamente.');
                
                @if($areaActual !== 'Completado')
                    openQaModal();
                @endif
            });
        }
    });

    function openQaModal() {
        document.getElementById('qaVerificationModal').classList.remove('hidden');
        document.getElementById('step-1-auth').classList.remove('hidden');
        document.getElementById('step-2-checklist').classList.add('hidden');
        document.getElementById('qa-auth-form').reset();
        document.getElementById('qa-auth-error').classList.add('hidden');
        document.getElementById('qa-save-error').classList.add('hidden');
        document.querySelectorAll('.qa-checkbox').forEach(cb => cb.checked = false);
        
        // Dinámica de Presión Diferencial
        let inputPresion = document.getElementById('input-presion-operario');
        let displayPresion = document.getElementById('display-presion-operario');
        if (displayPresion) {
            displayPresion.innerText = (inputPresion && inputPresion.value.trim() !== '') ? inputPresion.value.trim() : '(No ingresado)';
        }
        // Wait for modal transition then focus
        setTimeout(() => {
            document.getElementById('qa-password').focus();
        }, 100);
    }

    function closeQaModal() {
        document.getElementById('qaVerificationModal').classList.add('hidden');
    }


    function handleQaAuth() {
        let psw = document.getElementById('qa-password').value;
        let onBehalfOfId = document.getElementById('on_behalf_of_id_modal').value;
        let btn = document.getElementById('btn-qa-auth');
        let errBox = document.getElementById('qa-auth-error');
        
        btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> VALIDANDO...';
        btn.disabled = true;
        errBox.classList.add('hidden');

        axios.post('{{ route("batch.qa.credentials", $op) }}', {
            password: psw,
            on_behalf_of_id: onBehalfOfId
        })
        .then(res => {
            if (res.data.success) {
                qaVerifiedUserId = res.data.user_id;

                // Capturamos el ID del responsable finales para el save
                qaFinalOnBehalfOfId = onBehalfOfId;

                document.getElementById('qa-user-name-display').innerText = res.data.user_name;
                
                document.getElementById('step-1-auth').classList.add('hidden');
                document.getElementById('step-2-checklist').classList.remove('hidden');
                
                // Focus en el primer check o boton para mejor a11y
                setTimeout(() => {
                    document.querySelector('.qa-checkbox').focus();
                }, 100);
            }
        })
        .catch(err => {
            let msg = err.response && err.response.data && err.response.data.message 
                ? err.response.data.message 
                : 'Error de servidor. Intente nuevamente.';
            errBox.innerText = msg;
            errBox.classList.remove('hidden');
        })
        .finally(() => {
            btn.innerHTML = 'VALIDAR CREDENCIALES';
            btn.disabled = false;
        });
    }

    function handleQaVerificationSave() {
        let checkboxes = document.querySelectorAll('.qa-checkbox');
        let allChecked = true;
        checkboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });

        let errBox = document.getElementById('qa-save-error');
        if (!allChecked) {
            errBox.innerText = "Debe confirmar todos los puntos de inspección marcando las casillas correspondientes.";
            errBox.classList.remove('hidden');
            return;
        }

        errBox.classList.add('hidden');
        let btn = document.getElementById('btn-qa-save');
        let originalText = btn.innerHTML;
        btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> GUARDANDO...';
        btn.disabled = true;

        let formData = new FormData(mainForm);
        formData.append('qa_user_id', qaVerifiedUserId);
        formData.append('on_behalf_of_id', qaFinalOnBehalfOfId);

        // Si el checkbox de presión diferencial está marcado, lo agregamos (FormData solo captura inputs dentro del mainForm originalmente)
        // Por ende, debemos tomar el valor del input del Modal manualmente.
        let diffPressureCheck = document.getElementById('qa_presion_diferencial_conforme');
        if (diffPressureCheck && diffPressureCheck.checked) {
            formData.append('qa_presion_diferencial_conforme', 1);
        } else {
            formData.append('qa_presion_diferencial_conforme', 0);
        }

        axios.post('{{ route("batch.qa.verification", $op) }}', formData)
        .then(res => {
            if (res.data.success) {
                window.location.href = res.data.redirect;
            } else {
                errBox.innerText = res.data.message || 'Error al guardar.';
                errBox.classList.remove('hidden');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            let msg = err.response && err.response.data && err.response.data.message 
                ? err.response.data.message 
                : 'Error al enviar los datos al servidor. Tal vez faltan campos del operario.';
                
            if(err.response && err.response.status === 422) {
                msg = Object.values(err.response.data.errors)[0][0];
            }
            
            errBox.innerText = msg;
            errBox.classList.remove('hidden');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
</script>
@endpush
