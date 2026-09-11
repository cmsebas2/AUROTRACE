<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'notas'
    ];

    protected $casts = [
        'nivel' => 'integer',
        'archivador_numero' => 'integer',
        'slot' => 'integer',
        'fecha_archivo' => 'date'
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function maquilaOrder()
    {
        return $this->belongsTo(MaquilaProductionOrder::class, 'maquila_production_order_id');
    }

    /**
     * Ubicación formateada para etiquetas físicas
     * Ejemplo: RACK 1 · NIVEL 01 · ARCHIVADOR #17 (FRENTE) · SLOT 2
     */
    public function getUbicacionCompletaAttribute(): string
    {
        return "R 1 N {$this->nivel} A {$this->archivador_numero} S {$this->slot}";
    }
}
