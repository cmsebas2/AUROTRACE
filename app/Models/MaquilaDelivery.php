<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaquilaDelivery extends Model
{
    use SoftDeletes;

    protected $table = 'maquila_deliveries';

    protected $fillable = [
        'maquila_item_id',
        'fecha_recepcion',
        'numero_remision_factura',
        'cantidad_recibida',
        'usuario_registro_id',
        'firma_electronica_id',
        'hash_integridad'
    ];

    protected $casts = [
        'cantidad_recibida' => 'float',
        'fecha_recepcion' => 'date'
    ];

    public function item()
    {
        return $this->belongsTo(MaquilaItem::class, 'maquila_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    public function signature()
    {
        return $this->belongsTo(ElectronicSignature::class, 'firma_electronica_id');
    }

    /**
     * Accessor Porcentaje de Aporte al Lote (%)
     * Formula: (cantidad_recibida / cantidad_programada del item) * 100
     */
    public function getPorcentajeAporteLoteAttribute(): float
    {
        if (!$this->item || $this->item->cantidad_programada <= 0) {
            return 0.0;
        }

        return round(($this->cantidad_recibida / $this->item->cantidad_programada) * 100, 2);
    }
}
