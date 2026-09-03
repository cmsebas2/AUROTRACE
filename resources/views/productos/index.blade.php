@extends('layouts.app')

@section('header_title', 'Catálogo de Fórmulas y Productos')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto" x-data="{ search: '', selectedCategory: 'all' }">
    
    <!-- Top Action & Filter Bar (3D Glassmorphic Header) -->
    <div class="card-3d p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border border-slate-200/80">
        <div>
            <div class="flex items-center space-x-3">
                <div class="w-3 h-7 bg-gradient-to-b from-cyan-500 to-aurofarma rounded-full shadow-3d-cyan"></div>
                <div>
                    <h2 class="font-display text-2xl font-black text-slate-800 tracking-tight">Catálogo de Fórmulas Maestras</h2>
                    <p class="text-xs text-slate-500 font-medium">Especificaciones farmacéuticas y registros para producción EBR</p>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <!-- Search Input -->
            <div class="relative min-w-[240px]">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" x-model="search" placeholder="Buscar por nombre o ICA..."
                       class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all shadow-inner">
            </div>

            <!-- Create Product Button -->
            <a href="{{ route('productos.create') }}" 
               class="inline-flex items-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-aurofarma text-white text-xs font-black uppercase tracking-wider shadow-3d-button hover:shadow-3d-cyan transform hover:-translate-y-0.5 transition-all flex-shrink-0">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Producto
            </a>
        </div>
    </div>

    <!-- Species & Category Bar (Inspirado en aurofarma.com) -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        <button @click="selectedCategory = 'all'"
                :class="selectedCategory === 'all' ? 'bg-slate-900 text-white shadow-3d-badge' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center space-x-1.5 flex-shrink-0">
            <span>Todos</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20">{{ count($products) }}</span>
        </button>

        <!-- Species Filters with Emojis -->
        <div class="h-6 w-[1px] bg-slate-300 mx-1 flex-shrink-0"></div>

        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex-shrink-0">Especies:</span>
        <span class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 shadow-sm flex-shrink-0">🐔 Avicultura</span>
        <span class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 shadow-sm flex-shrink-0">🐷 Porcicultura</span>
        <span class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 shadow-sm flex-shrink-0">🐮 Ganadería</span>
        <span class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 shadow-sm flex-shrink-0">🐴 Equinos</span>
        <span class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 shadow-sm flex-shrink-0">🐾 Mascotas</span>
    </div>

    <!-- 3D Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        @foreach($products as $product)
        @php
            // Clasificación heurística de color de categoría de Aurofarma
            $nameLower = strtolower($product['name']);
            if (str_contains($nameLower, 'vit') || str_contains($nameLower, 'fos') || str_contains($nameLower, 'litos')) {
                $categoryName = 'Vitaminas';
                $catColor = '#FBBF24';
                $badgeBg = 'bg-amber-50 text-amber-800 border-amber-300';
            } elseif (str_contains($nameLower, 'coccidiol') || str_contains($nameLower, 'anapiran') || str_contains($nameLower, 'par')) {
                $categoryName = 'Antiparasitarios';
                $catColor = '#DE2021';
                $badgeBg = 'bg-red-50 text-red-800 border-red-300';
            } elseif (str_contains($nameLower, 'doxy') || str_contains($nameLower, 'cipro') || str_contains($nameLower, 'mutin') || str_contains($nameLower, 'tartilo') || str_contains($nameLower, 'sulfa')) {
                $categoryName = 'Antibióticos';
                $catColor = '#005889';
                $badgeBg = 'bg-blue-50 text-blue-800 border-blue-300';
            } elseif (str_contains($nameLower, 'glh') || str_contains($nameLower, 'bio') || str_contains($nameLower, 'cur')) {
                $categoryName = 'Bioseguridad';
                $catColor = '#028838';
                $badgeBg = 'bg-emerald-50 text-emerald-800 border-emerald-300';
            } else {
                $categoryName = 'Fórmula Especial';
                $catColor = '#06B6D4';
                $badgeBg = 'bg-cyan-50 text-cyan-800 border-cyan-300';
            }
        @endphp

        <div x-show="search === '' || '{{ strtolower($product['name']) }}'.includes(search.toLowerCase()) || '{{ strtolower($product['ica_license'] ?? '') }}'.includes(search.toLowerCase())"
             class="card-3d group relative flex flex-col overflow-hidden transition-all duration-300 hover:-translate-y-2 hover:shadow-3d-card-hover border border-slate-200/80 bg-white">
            
            <!-- Top Tag: Categoría con color Aurofarma -->
            <div class="absolute top-3 left-3 z-10">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border shadow-sm {{ $badgeBg }}">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" style="background-color: {{ $catColor }};"></span>
                    {{ $categoryName }}
                </span>
            </div>

            <!-- Botón Eliminar Flotante -->
            <form action="{{ route('productos.destroy', $product['id']) }}" method="POST" 
                  class="absolute top-3 right-3 z-10 opacity-0 group-hover:opacity-100 transition-opacity" 
                  onsubmit="return confirm('¿Confirma la eliminación de este producto y su fórmula maestra EBR?');">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="bg-white hover:bg-red-50 text-slate-400 hover:text-red-600 p-1.5 rounded-xl shadow-md border border-slate-200 transition-colors"
                        title="Eliminar producto">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </form>

            <a href="{{ route('productos.show', $product['id']) }}" class="flex-1 flex flex-col">
                <!-- Zona de Imagen 3D con Fondo Puro y Zoom Suave -->
                <div class="h-44 w-full bg-gradient-to-b from-slate-50 to-white flex items-center justify-center p-4 relative overflow-hidden border-b border-slate-100">
                    <img src="{{ asset('img/productos/' . $product['image']) }}" 
                         alt="{{ $product['name'] }}"
                         class="max-h-36 max-w-full object-contain filter drop-shadow-[0_8px_16px_rgba(0,0,0,0.12)] transition-transform duration-500 group-hover:scale-110"
                         onerror="this.onerror=null; this.outerHTML='<div class=\'w-16 h-16 rounded-2xl bg-cyan-50 border border-cyan-200 flex items-center justify-center text-cyan-600\'><svg class=\'w-8 h-8\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z\'></path></svg></div>';">
                </div>

                <!-- Zona de Información y Metadata -->
                <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                    <div>
                        <h3 class="font-display font-black text-slate-800 text-sm tracking-tight leading-snug group-hover:text-cyan-700 transition-colors line-clamp-2">
                            {{ $product['name'] }}
                        </h3>

                        <!-- Licencia ICA -->
                        <div class="mt-2 flex items-center justify-between">
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[9px] font-bold rounded-md uppercase tracking-wider border border-slate-200">
                                ICA: {{ $product['ica_license'] ?? 'En trámite' }}
                            </span>
                            <span class="text-[9px] font-bold text-cyan-600 uppercase tracking-widest">EBR Activo</span>
                        </div>
                    </div>

                    <!-- Botón de Detalle Técnico con Microinteracción -->
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-black text-slate-600 group-hover:text-cyan-700 transition-colors">
                        <span>Ver Ficha Técnica</span>
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection
