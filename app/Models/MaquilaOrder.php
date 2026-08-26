<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaquilaOrder extends Model
{
    protected $table = 'maquila_orders';

    protected $fillable = [
        'maquilador',
        'fecha_creacion',
        'estatus',
        'ubicacion',
        'op',
        'codigo_item',
        'descripcion',
        'lote',
        'fecha_fabricacion',
        'fecha_vencimiento',
        'cantidad_programada',
        'adicional',
        'devolucion',
        'restante',
        'balance',
        'fecha_balance',
        'pendiente',
        'fecha_despacho_maquila',
        'documento_traslado',
        'fecha_llegada_aurofarma',
        'op_secundaria',
        'observaciones',
        'metadatos',
    ];

    protected $casts = [
        'cantidad_programada' => 'decimal:2',
        'adicional' => 'decimal:2',
        'devolucion' => 'decimal:2',
        'restante' => 'decimal:2',
        'pendiente' => 'decimal:2',
        'metadatos' => 'array',
    ];

    /**
     * Get the partial deliveries for this order.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(MaquilaDelivery::class, 'maquila_order_id')->orderBy('numero_entrega');
    }
}
