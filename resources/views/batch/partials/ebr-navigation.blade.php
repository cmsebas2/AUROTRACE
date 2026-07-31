@php
    $status = isset($op) ? $op->status : 'PLANEADO';
    $lote = isset($op) ? $op->lote : null;
    
    // Logic for accessibility
    $canAccessConciliacion = isset($op); 
    $canAccessDespeje = isset($op); 
    $canAccessDispensacion = isset($op);
    $canAccessFabricacion = isset($op) && ($status !== 'PESAJE' || $op->dispensing?->status === 'COMPLETADO');
    $canAccessEnvase = isset($op) && ($status === 'ACONDICIONAMIENTO' || $status === 'COMPLETADO');
    
    // Check if current page is one of the steps to highlight
    $currentRoute = Route::currentRouteName();
    
    $steps = [
        [
            'id' => 1, 
            'label' => 'Apertura', 
            'route' => 'batch.iniciar', 
            'active' => ($currentRoute == 'batch.iniciar'),
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'is_completed' => isset($op)
        ],
        [
            'id' => 2, 
            'label' => 'Conciliación', 
            'route' => 'batch.conciliacion', 
            'active' => ($currentRoute == 'batch.conciliacion'),
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
            'is_completed' => isset($op) && $op->materialReconciliations()->whereNotNull('signed_at')->exists()
        ],
        [
            'id' => 3, 
            'label' => 'Despeje', 
            'route' => 'batch.despeje', 
            'active' => ($currentRoute == 'batch.despeje'),
            'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
            'is_completed' => isset($op) && $op->lineClearances()->whereNotNull('hora_fin')->exists()
        ],
        [
            'id' => 4, 
            'label' => 'Pesaje', 
            'route' => 'batch.dispensacion', 
            'active' => ($currentRoute == 'batch.dispensacion'),
            'icon' => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
            'is_completed' => isset($op) && $op->dispensing?->status === 'COMPLETADO'
        ],
        [
            'id' => 5, 
            'label' => 'Manufactura', 
            'route' => 'batch.fabricacion', 
            'active' => ($currentRoute == 'batch.fabricacion'),
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z',
            'is_completed' => isset($op) && ($status === 'ACONDICIONAMIENTO' || $status === 'COMPLETADO')
        ],
        [
            'id' => 6, 
            'label' => 'Envase', 
            'route' => 'batch.envase', 
            'active' => ($currentRoute == 'batch.envase'),
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'is_completed' => isset($op) && $status === 'COMPLETADO'
        ],
    ];

    $canAccess = [
        1 => true,
        2 => $canAccessConciliacion,
        3 => $canAccessDespeje,
        4 => $canAccessDispensacion, 
        5 => $canAccessFabricacion,
        6 => $canAccessEnvase
    ];
@endphp

<div class="mb-12">
    <div class="flex items-center justify-between relative px-4 max-w-5xl mx-auto">
        <!-- Connecting Line Background -->
        <div class="absolute left-0 top-1/2 transform -translate-y-[1.5rem] w-full h-1 bg-gray-100 z-0"></div>
        
        @foreach($steps as $step)
            @php
                $isAccessible = $canAccess[$step['id']];
                $active = $step['active'];
                $completed = $step['is_completed'];
                
                // Color Logic
                if ($active) {
                    $circleClass = 'bg-aurofarma-blue text-white ring-8 ring-blue-100 shadow-blue-200';
                    $textClass = 'text-aurofarma-blue font-black scale-110';
                    $iconOpacity = 'opacity-100';
                } elseif ($completed) {
                    $circleClass = 'bg-green-500 text-white shadow-green-100';
                    $textClass = 'text-green-600 font-bold';
                    $iconOpacity = 'opacity-100';
                } else {
                    $circleClass = 'bg-gray-100 text-gray-400 border-2 border-gray-200';
                    $textClass = 'text-gray-400 font-medium';
                    $iconOpacity = 'opacity-40';
                }
            @endphp
            
            <div class="relative z-10 flex flex-col items-center group transition-all duration-500">
                @if($isAccessible)
                    @php
                        $routeParams = ($step['route'] === 'batch.iniciar') ? [] : [$lote];
                    @endphp
                    <a href="{{ route($step['route'], $routeParams) }}" class="flex flex-col items-center">
                @else
                    <div class="flex flex-col items-center opacity-60">
                @endif

                <!-- Icon Circle -->
                <div class="w-12 h-12 rounded-2xl {{ $circleClass }} flex items-center justify-center shadow-lg transition-all duration-500 group-hover:scale-110 relative">
                    <svg class="w-6 h-6 {{ $iconOpacity }} transition-opacity duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"></path>
                    </svg>
                    
                    @if($completed && !$active)
                        <div class="absolute -top-1 -right-1 bg-white rounded-full p-0.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Label -->
                <span class="text-[9px] mt-3 uppercase tracking-[0.15em] {{ $textClass }} transition-all duration-500">{{ $step['label'] }}</span>

                @if($isAccessible)
                    </a>
                @else
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
