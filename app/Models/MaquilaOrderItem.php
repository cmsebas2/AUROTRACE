<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MaquilaOrderItem extends Model
{
    protected $fillable = [
        'maquila_order_id',
        'product_id',
        'cantidad',
        'lote',
        'cantidad_programada',
        'fecha_fabricacion',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'fecha_fabricacion' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    /**
     * Get the maquila order that owns this item.
     */
    public function order()
    {
        return $this->belongsTo(MaquilaOrder::class, 'maquila_order_id');
    }

    /**
     * Get the product details.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Check if the item is near its expiration date (less than 3 months).
     */
    public function isNearExpiry()
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }

        $vencimiento = Carbon::parse($this->fecha_vencimiento);
        $hoy = Carbon::now();

        // Si ya está vencido o vence en menos de 90 días (3 meses)
        return $vencimiento->isPast() || $hoy->diffInDays($vencimiento, false) < 90;
    }
}
