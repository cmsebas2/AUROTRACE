<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ============================================================================
 * UBICACIÓN FÍSICA DE ARCHIVO 3D (BATCH RECORDS) - BatchRecordArchiveLocation
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Representa cada "hueco" o espacio físico (Slot) dentro de las carpetas AZ
 * (Archivadores) del Rack de Archivo Técnico de Aurofarma:
 * - 1 Rack = 5 Niveles (1 al 5).
 * - Cada Nivel tiene 42 Archivadores (impares adelante: cara VISIBLE, pares atrás: cara POSTERIOR).
 * - Cada Archivador tiene 4 Slots (capacidad máxima de 4 Batch Records por carpeta).
 * ============================================================================
 */
class BatchRecordArchiveLocation extends Model
{
    protected $table = 'batch_record_archive_locations';

    protected $fillable = [
        'rack',
        'nivel',
        'archivador_numero',
        'cara',
        'slot',
        'lote',
        'op_number',
        'producto_nombre',
        'tipo_origen',
        'production_order_id',
        'maquila_production_order_id',
        'fecha_archivo',
        'notas',
    ];

    protected $casts = [
        'nivel'             => 'integer',
        'archivador_numero' => 'integer',
        'slot'              => 'integer',
        'fecha_archivo'     => 'date',
    ];

    /** Orden de Producción de planta asociada a esta ubicación física. */
    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    /** Orden de Maquila externa asociada a esta ubicación física. */
    public function maquilaOrder()
    {
        return $this->belongsTo(MaquilaProductionOrder::class, 'maquila_production_order_id');
    }

    /**
     * Ubicación abreviada para etiquetas físicas (Ej: "R 1 N 1 A 17 S 2").
     */
    public function getUbicacionCompletaAttribute(): string
    {
        $rack = $this->rack ?: 1;
        return "R {$rack} N {$this->nivel} A {$this->archivador_numero} S {$this->slot}";
    }
}
