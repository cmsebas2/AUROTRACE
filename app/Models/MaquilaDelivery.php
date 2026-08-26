<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaquilaDelivery extends Model
{
    protected $table = 'maquila_deliveries';

    protected $fillable = [
        'maquila_order_id',
        'lote',
        'numero_entrega',
        'documento_remision',
        'cantidad_entregada',
    ];

    protected $casts = [
        'numero_entrega' => 'integer',
        'cantidad_entregada' => 'decimal:2',
    ];

    /**
     * Get the parent order for this delivery.
     */
    public function maquilaOrder(): BelongsTo
    {
        return $this->belongsTo(MaquilaOrder::class, 'maquila_order_id');
    }
}
